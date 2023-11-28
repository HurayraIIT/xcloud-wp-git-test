<?php

/**
 * CustomNotification Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\CustomNotification;

use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\CustomNotification\CustomNotificationConversions as CustomNotificationConversionsFree;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Types\TypeFactory;
use NotificationXPro\Feature\SalesFeatures;

/**
 * CustomNotification Extension
 */
class CustomNotificationConversions extends CustomNotificationConversionsFree {
    public $_source   = 'woocommerce';
    public $link_type = 'url';

    public function init() {
        parent::init();
        add_filter("nx_get_post_{$this->id}", [$this, 'nx_get_post']);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_source_trigger', [$this, '_source_trigger'], 20);
        add_filter('nx_link_types', [$this, 'nx_link_types'], 15);

    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
        add_filter("nx_theme_preview_{$this->id}", [$this, 'get_theme_preview_image'], 10, 2);
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     * @return void
     */
    public function public_actions() {
        parent::public_actions();

        add_filter('nx_frontend_get_entries', [$this, 'nx_frontend_get_entries'], 10, 2);
        add_filter("nx_filtered_entry_{$this->id}", array($this, 'conversion_data'), 10, 2);
        add_filter("nx_filtered_data_{$this->id}", array($this, 'filtered_data'), 10, 2);
        // add_filter("nx_notification_link_{$this->id}", [$this, 'custom_link'], 10, 3);
    }

    /**
     * Undocumented function
     *
     * @param array $options
     * @return array
     */
    public function nx_link_types($options) {
		if(isset($options['custom'])){
			$options['custom'] = Rules::is('source', $this->id, true, $options['custom']);
		}
		$options['url'] = [
			'value' => 'url',
			'label' => __('URL', 'notificationx-pro'),
			'rules' => Rules::is('source', $this->id),
        ];
        return $options;
    }

	/**
	 * no need for now it's already done in FrontEnd.php
	 *
	 * @param [type] $link
	 * @param [type] $post
	 * @param [type] $entry
	 * @return void
	 */
    public function custom_link($link, $post, $entry) {
        if(isset($entry['link'], $post['link_type'] ) && 'url' === $post['link_type'] ){
            $link = $entry['link'];
        }
        return $link;
    }

    /**
     * Get themes for the extension.
     *
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function _source_trigger($triggers){
        $triggers[$this->id]['combine_multiorder'] = "@combine_multiorder:false";
        return $triggers;
    }

    public function fallback_data($data, $entry, $settings) {
        $settings['source'] = $this->_source;
        $data = apply_filters("nx_fallback_data_$this->_source", $data, $entry, $settings);
        // $data = apply_filters("nx_fallback_data_$type", $data, $entry, $settings);
        // $data = SalesFeatures::get_instance()->fallback_data($data, $entry, $settings);
        $data['1day']        = __('in last 1 day', 'notificationx-pro');
        $data['7days']       = __('in last 7 days', 'notificationx-pro');
        $data['30days']      = __('in last 30 days', 'notificationx-pro');
        return $data;
    }

    // @todo Frontend
    public function conversion_data($entry, $settings) {
        $settings['source'] = $this->_source;
        $entry = apply_filters("nx_filtered_entry_{$this->_source}", $entry, $settings);
        // $entry = SalesFeatures::get_instance()->conversion_data($entry, $settings);
        return $entry;
    }
    // @todo Frontend
    public function filtered_data($entries, $settings) {
        $settings['source'] = $this->_source;
        $entries = apply_filters("nx_filtered_data_{$this->_source}", $entries, $settings);
        return $entries;
    }

    // get_theme_preview_image
    public function get_theme_preview_image($url, $post) {
        $theme  = $post['theme'];
        $theme  = str_replace("conversions_", '', $theme);
        if ($ex = TypeFactory::get_instance()->get('conversions')) {
            $themes = $ex->get_themes();
            if (!empty($themes[$theme]['source'])) {
                $url = $themes[$theme]['source'];
            }
        }
        return $url;
    }

    public function nx_get_post($post) {
        if(isset($post['combine_multiorder'])){
            unset($post['combine_multiorder']);
        }
		if(isset($post['link_type']) && !in_array($post['link_type'], ['none', 'url'])){
			$post['link_type'] = 'url';
		}
        $post['custom_type'] = 'conversions';
        return $post;
    }

    public function notification_image($image_data, $data, $settings) {
        $show_image = $settings['show_notification_image'];
        if (!$settings['show_default_image'] && isset($data['image']['url']) && $show_image === 'featured_image') {
            $image_data['url'] = $data['image']['url'];
        }
        return $image_data;
    }

    /**
     * Undocumented function
     *
     * @param array $entries
     * @param array $ids
     * @return void
     */
    public function nx_frontend_get_entries($entries, $ids) {
        $custom_entries = [];
        $notifications = PostType::get_instance()->get_posts_by_ids( $ids, $this->id );

        foreach ($notifications as $key => $post) {
            if (!empty($post['custom_contents'])) {
                if($post['random_order']){
                    shuffle($post['custom_contents']);
                }
                if($post['display_last']){
                    $post['custom_contents'] = array_splice($post['custom_contents'], 0, $post['display_last']);
                }
                foreach ($post['custom_contents'] as $key => $entry) {
                    try {
                        $entry['updated_at'] = !empty($entry['timestamp']) ? $entry['timestamp'] : $post['updated_at'];
                        // $entry['updated_at'] = strtotime($entry['updated_at']);
                        $entry['updated_at'] = Helper::mysql_time($entry['updated_at']);
                    } catch (\Exception $e) {
                        //throw $th;
                    }
                    $entry = wp_parse_args($entry, [
                        'nx_id'      => $post['nx_id'],
                        'source'     => $post['source'],
                        'entry_key'  => rand(),
                        'timestamp'  => time(),
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'updated_at' => date("Y-m-d H:i:s", time()),
                    ]);
                    $custom_entries[] = apply_filters('nx_get_entry', $entry);
                }
            }
        }
        $custom_entries = apply_filters('nx_get_entries', $custom_entries);
        $entries = array_merge($entries, $custom_entries);
        return $entries;
    }
}

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
use NotificationX\Extensions\CustomNotification\CustomNotification as CustomNotificationFree;
use NotificationX\Extensions\ExtensionFactory as ExtensionFactoryFree;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Types\Conversions;
use NotificationX\Types\TypeFactory;
use NotificationXPro\Extensions\ExtensionFactory;
use NotificationXPro\Feature\SalesFeatures;

/**
 * CustomNotification Extension
 */
class CustomNotification extends CustomNotificationFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    public function init() {
        parent::init();
        add_filter("nx_get_post_{$this->id}", [$this, 'nx_get_post']);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_themes', [$this, 'custom_nx_themes'], 99);
        add_filter('nx_content_fields', [$this, 'content_fields']);
        add_filter('nx_notification_template_dependency', [$this, 'notification_template_dependency']);
        add_filter('nx_source_trigger', [$this, '_source_trigger'], 20);
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

        add_filter('nx_frontend_get_entries', [$this, 'nx_frontend_get_entries'], 10, 3);
        add_filter("nx_filtered_entry_{$this->id}", array($this, 'conversion_data'), 10, 2);
        add_filter("nx_filtered_data_{$this->id}", array($this, 'filtered_data'), 10, 2);
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

    /**
     * Get themes for the extension.
     *
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function get_themes_name() {
        $custom = $this->supported_themes();
        $custom_themes = array_reduce($custom, 'array_merge', array());
        return $custom_themes;
    }

    /**
     * Runs when modules is enabled.
     *
     * @return void
     */
    public function custom_nx_themes($themes) {
        $custom = $this->supported_themes();
        $custom_themes = array_reduce($custom, 'array_merge', array());

        foreach ($themes as $tname => $theme) {
            if (in_array($tname, $custom_themes)) {
                $themes[$tname] = Rules::includes('source', $this->id, false, $theme);
            }
        }
        return $themes;
    }

    public function get_source($settings){
        $custom_type = !empty($settings['custom_type']) ? $settings['custom_type'] : '';
        $source = '';
        switch ($custom_type) {
            case 'conversions':
                $source = 'woocommerce';
                break;
            case 'comments':
                $source = 'wp_comments';
                break;
            case 'reviews':
                $source = 'wp_reviews';
                break;
            case 'download_stats':
                $source = 'wp_stats';
                break;
            case 'email_subscription':
                $source = 'mailchimp';
                break;
        }
        return ['type' => $custom_type, 'source' => $source];
    }

    public function fallback_data($data, $entry, $settings) {
        $_settings = $this->get_source($settings);
        if ($_settings['source']) {
            $settings['type'] = $_settings['type'];
            $settings['source'] = $_settings['source'];
            $data = apply_filters("nx_fallback_data_{$_settings['type']}", $data, $entry, $settings);
            $data = apply_filters("nx_fallback_data_{$_settings['source']}", $data, $entry, $settings);
            // $data = SalesFeatures::get_instance()->fallback_data($data, $entry, $settings;)
        }
        $data['1day']        = __('in last 1 day', 'notificationx-pro');
        $data['7days']       = __('in last 7 days', 'notificationx-pro');
        $data['30days']      = __('in last 30 days', 'notificationx-pro');
        return $data;
    }

    // @todo Frontend
    public function conversion_data($entry, $settings) {
        $_settings = $this->get_source($settings);
        if ($_settings['source']) {
            $settings['type'] = $_settings['type'];
            $settings['source'] = $_settings['source'];
            $entry = apply_filters("nx_filtered_entry_{$_settings['type']}", $entry, $settings);
            $entry = apply_filters("nx_filtered_entry_{$_settings['source']}", $entry, $settings);
            // $entry = SalesFeatures::get_instance()->conversion_data($entry, $settings);
        }
        return $entry;
    }
    // @todo Frontend
    public function filtered_data($entries, $settings) {
        $_settings = $this->get_source($settings);
        if ($_settings['source']) {
            $settings['type'] = $_settings['type'];
            $settings['source'] = $_settings['source'];
            $entries = apply_filters("nx_filtered_data_{$_settings['type']}", $entries, $settings);
            $entries = apply_filters("nx_filtered_data_{$_settings['source']}", $entries, $settings);
        }
        return $entries;
    }

    /**
     * Get data for WooCommerce Extension.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];

        $custom = $this->supported_themes();
        $content_fields['custom_contents'] = [
            'label'    => __('Conversion', 'notificationx-pro'),
            'name'     => 'custom_contents',
            'type'     => 'repeater',
            'priority' => 300,
            'button'  => [
                'label' => __('Add New', 'notificationx-pro')
            ],
            'fields'   => [
                'title' => array(
                    'type'     => 'text',
                    'name'     => 'title',
                    'label'    => __('Title', 'notificationx-pro'),
                    'priority' => 5,
                    'rules'       => $this->make_rule(array_merge(
                        $custom['conversions'],
                        $custom['sales_count'],
                        $custom['maps_theme'],
                        // $custom['reviews'],
                        $custom['subs']
                    )),
                ),
                'post_title' => array(
                    'type'     => 'text',
                    'name'     => 'post_title',
                    'label'    => __('Post Title', 'notificationx-pro'),
                    // 'label'    => __('Title' , 'notificationx-pro'),
                    'priority' => 1,
                    'rules'       => $this->make_rule($custom['comments']),
                ),
                'post_comment' => array(
                    'type'     => 'textarea',
                    'name'     => 'post_comment',
                    'label'    => __('Comment', 'notificationx-pro'),
                    'priority' => 2,
                    'rules'       => $this->make_rule($custom['comments']),
                ),
                'username' => array(
                    'type'     => 'text',
                    'name'     => 'username',
                    'label'    => __('User Name', 'notificationx-pro'),
                    // 'default' => 'A Marketer',
                    'priority' => 0,
                    'rules'       => $this->make_rule($custom['reviews']),
                ),

                'name' => array(
                    'type'     => 'text',
                    'name'     => 'plugin_theme_name',
                    'label'    => __('Plugin/Theme Name', 'notificationx-pro'),
                    'priority' => 10,
                    'rules'       => $this->make_rule($custom['stats']),
                ),
                'first_name' => array(
                    'type'     => 'text',
                    'name'     => 'first_name',
                    'label'    => __('First Name', 'notificationx-pro'),
                    'priority' => 10,
                    'rules'       => $this->make_rule(array_merge(
                        $custom['conversions'],
                        $custom['maps_theme'],
                        $custom['comments'],
                        $custom['subs']
                    )),
                ),
                'last_name' => array(
                    'type'     => 'text',
                    'name'     => 'last_name',
                    'label'    => __('Last Name', 'notificationx-pro'),
                    'priority' => 10,
                    'rules'       => $this->make_rule(array_merge(
                        $custom['conversions'],
                        $custom['maps_theme'],
                        $custom['comments'],
                        $custom['subs']
                    )),
                ),
                'email' => array(
                    'type'     => 'text',
                    'name'     => 'email',
                    'label'    => __('Email Address', 'notificationx-pro'),
                    'priority' => 15,
                    'rules'       => $this->make_rule(array_merge(
                        $custom['conversions'],
                        $custom['subs']
                    )),
                ),
                'city' => array(
                    'type'     => 'text',
                    'name'     => 'city',
                    'label'    => __('City', 'notificationx-pro'),
                    'priority' => 20,
                    'rules'       => $this->make_rule($custom['maps_theme']),
                ),
                'country' => array(
                    'type'     => 'text',
                    'name'     => 'country',
                    'label'    => __('Country', 'notificationx-pro'),
                    'priority' => 25,
                    'rules'       => $this->make_rule($custom['maps_theme']),
                ),
                'sales_count' => array(
                    'type'     => 'text',
                    'name'     => 'sales_count',
                    'label'    => __('Number of Sales', 'notificationx-pro'),
                    'priority' => 2,
                    'rules'       => $this->make_rule($custom['sales_count']),
                ),
                'image' => array(
                    'type'     => 'media',
                    'name'     => 'image',
                    'label'    => __('Image', 'notificationx-pro'),
                    'priority' => 30,
                    'rules'       => $this->make_rule(array_merge(
                        $custom['conversions'],
                        $custom['sales_count'],
                        $custom['maps_theme'],
                        $custom['comments'],
                        $custom['reviews'],
                        $custom['stats'],
                        $custom['subs']
                    )),
                ),
                'link' => array(
                    'type'     => 'text',
                    'name'     => 'link',
                    'label'    => __('URL', 'notificationx-pro'),
                    'priority' => 35,
                    'rules'       => $this->make_rule(array_merge(
                        $custom['conversions'],
                        $custom['sales_count'],
                        $custom['maps_theme'],
                        $custom['comments'],
                        $custom['reviews'],
                        $custom['stats'],
                        $custom['subs']
                    )),
                ),


                'rated' => array(
                    'type'     => 'text',
                    'name'     => 'rated',
                    'label'    => __('Number of Peoples Rated', 'notificationx-pro'),
                    // 'default' => '10K+',
                    'priority' => 1,
                    'rules'       => $this->make_rule($custom['reviews']),
                ),
                'plugin_name' => array(
                    'type'     => 'text',
                    'name'     => 'plugin_name',
                    'label'    => __('Plugin Name', 'notificationx-pro'),
                    // 'default' => 'My Plugin or Theme Name',
                    'priority' => 2,
                    'rules'       => $this->make_rule($custom['reviews']),
                ),
                'plugin_review' => array(
                    'type'     => 'textarea',
                    'name'     => 'plugin_review',
                    'label'    => __('Review Text', 'notificationx-pro'),
                    'priority' => 4,
                    'rules'       => $this->make_rule($custom['reviews']),
                ),
                'rating' => array(
                    'type'     => 'number',
                    'name'     => 'rating',
                    'label'    => __('Rating', 'notificationx-pro'),
                    'min'      => 1,
                    'max'      => 5,
                    'default'  => 5,
                    'priority' => 5,
                    'rules'    => $this->make_rule($custom['reviews']),
                ),

                'today' => array(
                    'type'     => 'number',
                    'name'     => 'today',
                    'label'    => __('Todays Download', 'notificationx-pro'),
                    'description'    => __('Number of items downloaded in one day.', 'notificationx-pro'),
                    'priority' => 1,
                    'rules'       => $this->make_rule($custom['stats']),
                ),
                'last_week' => array(
                    'type'     => 'number',
                    'name'     => 'last_week',
                    'label'    => __('7 Days Downloads', 'notificationx-pro'),
                    'description'    => __('Number of items downloaded in last 7 days.', 'notificationx-pro'),
                    'priority' => 2,
                    'rules'       => $this->make_rule($custom['stats']),
                ),
                'all_time' => array(
                    'type'     => 'number',
                    'name'     => 'all_time',
                    'label'    => __('Total Downloads', 'notificationx-pro'),
                    'description'    => __('Number of items downloaded in total.', 'notificationx-pro'),
                    'priority' => 3,
                    'rules'       => $this->make_rule($custom['stats']),
                ),
                'active_installs' => array(
                    'type'     => 'number',
                    'name'     => 'active_installs',
                    'label'    => __('Number of Active Installs', 'notificationx-pro'),
                    'priority' => 4,
                    'rules'       => $this->make_rule($custom['stats']),
                ),
                'timestamp' => array(
                    'type'  => 'date',
                    'name'     => 'timestamp',
                    'label'    => __('Time', 'notificationx-pro'),
                    // 'priority' => 40,
                    // 'rules'       => $this->make_rule(array_merge(
                    //     $custom['conversions'],
                    //     $custom['maps_theme'],
                    //     $custom['comments'],
                    //     $custom['reviews'],
                    //     $custom['subs']
                    // )),
                ),
            ],
            'rules'       => Rules::includes('source', [$this->id, CustomNotificationConversions::get_instance()->id]),
        ];

        return $fields;
    }

    public function make_rule($themes) {
        $rules = [];
        $rules[] = Rules::includes('themes', $themes);
        $rules[] = Rules::includes('source', [$this->id, CustomNotificationConversions::get_instance()->id]);
        return Rules::logicalRule($rules, 'and');
    }

    /**
     * Adds options and dependency for `Notification Template` field from `Content` tab.
     *
     * @param array $templates `Notification Template` fields.
     * @return array
     */
    public function notification_template_dependency($dependency) {
        $dependency[] = $this->id;
        return $dependency;
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
    public function nx_frontend_get_entries($entries, $ids, $notifications) {
        $custom_entries = [];

        foreach ($notifications as $key => $post) {
            if ($post['source'] === $this->id && !empty($post['custom_contents'])) {
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

    public function get_theme_preview_image($url, $post) {
        // get_theme_preview_image
        if(empty($post['theme'])) return $url;
        $theme  = $post['theme'];
        $extension_classes = TypeFactory::get_instance()->types;

        foreach ($extension_classes as $type => $class) {
            if (strpos($theme, $type) === 0) {
                $post['custom_type'] = $type;
                if ($ex = TypeFactory::get_instance()->get($type)) {
                    $themes = $ex->get_themes();
                    $theme  = str_replace("{$type}_", '', $theme);
                    if (!empty($themes[$theme]['source'])) {
                        $url = $themes[$theme]['source'];
                        break;
                    }
                }
            }
        }
        return $url;
    }

    /**
     * Undocumented function
     *
     * @param [type] $post
     * @return void
     */
    public function nx_get_post($post) {
        if(isset($post['combine_multiorder'])){
            unset($post['combine_multiorder']);
        }
        $extension_classes = TypeFactory::get_instance()->types;

        foreach ($extension_classes as $type => $class) {
            if (!empty($post['themes']) && strpos($post['themes'], $type) === 0) {
                $post['custom_type'] = $type;
                break;
            }
        }
        return $post;
    }

    public function get_theme_type($post) {
        $source = $post['source'];
        $theme  = $post['themes'];
        $extension_classes = TypeFactory::get_instance()->types;
        foreach ($extension_classes as $type => $class) {
            if (strpos($theme, $type) === 0) {
                $theme  = str_replace("{$type}-", '', $theme);
                return "{$type}_$theme";
            }
        }
        return "conversions_$theme";
    }

}

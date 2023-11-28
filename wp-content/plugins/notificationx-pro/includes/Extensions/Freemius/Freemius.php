<?php

/**
 * Freemius Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Freemius;

use NotificationX\Admin\Cron;
use NotificationX\Admin\Settings;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;

/**
 * Freemius Extension
 */
trait Freemius {
    public $meta_key = 'freemius_content';

    public function construct() {

        $this->dev_id         = Settings::get_instance()->get('settings.freemius_dev_id');
        $this->dev_public_key = Settings::get_instance()->get('settings.freemius_dev_pk');
        $this->dev_secret_key = Settings::get_instance()->get('settings.freemius_dev_sk');

        // @todo move to a function.
        $this->lists           = get_option('nxpro_freemius_data');
        $this->lists['addons'] = !empty($this->lists) &&!empty($this->lists['addons']) ? $this->lists['addons'] : [];

        add_filter('nxpro_js_scripts', array($this, 'freemius_js_text'), 10, 1);
    }

    /**
     * This functions is hooked
     *
     * @hooked nx_public_action
     *
     * @return void
     */
    public function public_actions() {
        parent::public_actions();

        // add_filter('nx_fields_data', array($this, 'conversion_data'), 10, 2);
        // add_filter('nx_frontend_get_entries', [$this, 'nx_frontend_get_entries'], 10, 2);
    }
    public function admin_actions() {
        parent::admin_actions();
        add_action("nx_cron_update_data_{$this->id}", array($this, 'update_data'), 10, 2);
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    public function init_fields() {
        parent::init_fields();
        add_filter('nx_content_fields', [$this, 'content_fields']);
    }

    public function api_integration_settings($fields) {
        if (empty($fields['freemius_settings_section'])) {
            $fields['freemius_settings_section'] = [
                'type'     => 'section',
                'name'     => 'freemius_settings_section',
                'label'    => __('Freemius Settings', 'notificationx-pro'),
                'type'     => 'section',
                'modules'  => 'modules_freemius',
                'priority' => 30,
                'rules'    => Rules::is('modules.modules_freemius', true),
                'fields'   => array(
                    'freemius_dev_id' => array(
                        'name'        => 'freemius_dev_id',
                        'type'        => 'text',
                        'label'       => __('Developer ID', 'notificationx-pro'),
                        'priority'    => 5,
                        'default'     => '',
                        'description'    => sprintf('<a target="_blank" rel="nofollow" href="%3$s">%1$s</a> %2$s',
                                            __('Click here', 'notificationx-pro'),
                                            __('to get Developer ID.', 'notificationx-pro'),
                                            'https://dashboard.freemius.com'
                                        ),
                    ),
                    'freemius_dev_pk' => array(
                        'name'        => 'freemius_dev_pk',
                        'type'        => 'text',
                        'label'       => __('Developer Public Key', 'notificationx-pro'),
                        'priority'    => 6,
                        'default'     => '',
                        'description'    => sprintf('<a target="_blank" rel="nofollow" href="%3$s">%1$s</a> %2$s',
                                            __('Click here', 'notificationx-pro'),
                                            __('to get Developer Public Key.', 'notificationx-pro'),
                                            'https://dashboard.freemius.com'
                                        ),
                    ),
                    'freemius_dev_sk' => array(
                        'name'        => 'freemius_dev_sk',
                        'type'        => 'text',
                        'label'       => __('Developer Secret Key', 'notificationx-pro'),
                        'priority'    => 7,
                        'default'     => '',
                        'description'    => sprintf('<a target="_blank" rel="nofollow" href="%3$s">%1$s</a> %2$s',
                                            __('Click here', 'notificationx-pro'),
                                            __('to get Developer Secret Key.', 'notificationx-pro'),
                                            'https://dashboard.freemius.com'
                                        ),
                    ),
                    'freemius_cache_duration' => array(
                        'name'        => 'freemius_cache_duration',
                        'type'        => 'text',
                        'label'       => __('Cache Duration', 'notificationx-pro'),
                        'default'     => 5,
                        'priority'    => 5,
                        'description' => __('Minutes, scheduled duration for collecting new data.', 'notificationx-pro'),
                    ),
                    [
                        'name'    => 'freemius_connect',
                        // 'label'   => 'Connect Button',
                        'type'    => 'button',
                        'default' => false,
                        'text'    => [
                            'normal'  => __('Connect', 'notificationx-pro'),
                            'saved'   => __('Refresh', 'notificationx-pro'),
                            'loading' => __('Refreshing...', 'notificationx-pro')
                        ],
                        'ajax' => [
                            'on'   => 'click',
                            'api'  => '/notificationx/v1/api-connect',
                            'data' => [
                                'source' => $this->id,
                                'dev_id' => '@freemius_dev_id',
                                'dev_pk' => '@freemius_dev_pk',
                                'dev_sk' => '@freemius_dev_sk',
                            ],
                        ]
                    ],
                )
            ];
        }
        return $fields;
    }

    public function content_fields($fields) {

        // @todo maybe move to somewhere.
        // if( ! $this->dev_id ) {
        //     $fields['content']['fields']['has_no_freemius_settings'] = array(
        //         'type'     => 'message',
        //         'message'    => __('You have to setup your Dev ID, Public Key, Secret Key from <a href="'. admin_url('admin.php?page=nx-settings#api_integrations_tab') .'">settings</a>.' , 'notificationx-pro'),
        //         'priority' => 0,
        //     );
        // }

        $content_fields = &$fields['content']['fields'];

        if(!isset($content_fields['freemius_item_type'])){
            $content_fields['freemius_item_type'] = array(
                'name'     => 'freemius_item_type',
                'type'     => 'select',
                'label'    => __('Item Type', 'notificationx-pro'),
                'priority' => 1,
                'options'  => GlobalFields::get_instance()->normalize_fields(array(
                    'plugin' => __('Plugin', 'notificationx-pro'),
                    'theme'  => __('Theme', 'notificationx-pro'),
                    'bundle' => __('Bundle', 'notificationx-pro'),
                    'addon' => __('Add-on', 'notificationx-pro'),
                )),
                'default' => 'plugin',
                'rules'   => Rules::includes('source', $this->id),
            );

            $content_fields['freemius_themes'] = array(
                'name'     => 'freemius_themes',
                'type'     => 'select',
                'label'    => __('Select a Theme', 'notificationx-pro'),
                'priority' => 5,
                'options'  => GlobalFields::get_instance()->normalize_fields($this->get_lists('themes')),
                'rules'    => Rules::logicalRule([Rules::includes('source', $this->id), Rules::is('freemius_item_type', 'theme')]),
            );

            $plugins = $this->get_lists('plugins');
            $triggers = [];
            foreach ($plugins as $key => $value) {
                $triggers[$key] = "@freemius_addons:false";
            }
            $content_fields['freemius_plugins'] = array(
                'name'     => 'freemius_plugins',
                'type'     => 'select',
                'label'    => __('Select a Plugin', 'notificationx-pro'),
                'priority' => 10,
                'options'  => GlobalFields::get_instance()->normalize_fields($plugins),
                'rules'    => Rules::logicalRule([
                    Rules::includes('source', $this->id),
                    Rules::includes('freemius_item_type', ['plugin', 'addon'])
                ]),
                'trigger'  => [
                    'defaults' => $triggers,
                ],
            );

            $content_fields['freemius_bundles'] = array(
                'name'     => 'freemius_bundles',
                'type'     => 'select',
                'label'    => __('Select a Bundle', 'notificationx-pro'),
                'priority' => 10,
                'options'  => GlobalFields::get_instance()->normalize_fields($this->get_lists('bundles')),
                'rules'    => Rules::logicalRule([Rules::includes('source', $this->id), Rules::is('freemius_item_type', 'bundle')]),
            );

            $content_fields["freemius_addons"] = [
                'label'    => __("Freemius Add-on", 'notificationx'),
                'name'     => "freemius_addons",
                'type'     => "select",
                'priority' => 20,
                'default'  => '',
                'options'  => GlobalFields::get_instance()->normalize_fields([
                    '' => __('All', 'notificationx'),
                ]),
                'ajax' => [
                    'on'   => 'click',
                    'api'  => "/notificationx/v1/get-data",
                    'data' => [
                        'type'      => "@type",
                        'source'    => "@source",
                        'plugin_id' => "@freemius_plugins",
                    ],
                    'target' => "freemius_plugins",
                ],
                'rules'    => Rules::logicalRule([
                    Rules::includes('source', $this->id),
                    Rules::includes('freemius_item_type', 'addon'),
                ]),
            ];
        }
        else{
            $content_fields['freemius_item_type'] = Rules::includes('source', $this->id, false, $content_fields['freemius_item_type']);
            $content_fields['freemius_themes']    = Rules::includes('source', $this->id, false, $content_fields['freemius_themes']);
            $content_fields['freemius_plugins']   = Rules::includes('source', $this->id, false, $content_fields['freemius_plugins']);
            $content_fields['freemius_bundles']   = Rules::includes('source', $this->id, false, $content_fields['freemius_bundles']);
            $content_fields['freemius_addons']    = Rules::logicalRule([
                Rules::includes('source', $this->id),
                Rules::includes('freemius_item_type', 'addon'),
            ], 'and', $content_fields['freemius_addons']);
        }

        return $fields;
    }

    public function freemius() {
        $connection = Helper::get_instance()->freemius('developer', intval($this->dev_id), $this->dev_public_key, $this->dev_secret_key);
        if (!is_wp_error($connection)) {
            return $connection;
        }
        return false;
    }

    public function connect($params) {
        return Helper::get_instance()->freemius_connect($params);
    }

    public function freemius_js_text($data) {
        $data['mc_on_success'] = __('You have successfully connected with Freemius, Your lists has been recorded for future use.', 'notificationx-pro');
        $data['mc_on_error'] = __('Something went wrong. Try again.', 'notificationx-pro');

        return $data;
    }


    private function get_lists($type = '') {
        $options = [];
        if ($type == '') {
            return $options;
        }
        if (!empty($this->lists[$type])) {
            foreach ($this->lists[$type] as $list) {
                $options[$list['id']] = $list['title'];
            }
        }
        return $options;
    }

    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    public function update_data($nx_id, $settings) {
        if (empty($settings)) {
            $settings = PostType::get_instance()->get_post($nx_id);
        }

        $item_type = $settings['freemius_item_type'];
        $item_id = $this->getItemId($settings);

        $plugin_name = $this->get_plugin_name($settings);
        if ($settings['type'] === 'reviews') {
            $results = $this->get_reviews($item_id, $plugin_name);
        }

        if ($settings['type'] === 'download_stats') {
            $results = [$this->get_stats($item_id, $item_type, $item_id, $plugin_name)];
        }

        if ($settings['type'] === 'conversions') {
            $results = $this->get_conversions($item_id, $item_type, $plugin_name);
        }

        // removing old notifications.
        $this->delete_notification(null, $nx_id);
        $entries = [];
        foreach ($results as $result) {
            $entries[] = [
                'nx_id'      => $nx_id,
                'source'     => $this->id,
                'entry_key'  => $this->meta_key,
                'data'       => $result,
            ];
        }
        $this->update_notifications($entries);
    }

    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image']) {
            $avatar = '';
            if (isset($data['plugin_name'])) {
                $image_data['alt'] = $data['plugin_name'];
            }
            $item_type = $settings['freemius_item_type'];
            $item_id = $item_type === 'plugin' ? $settings['freemius_plugins'] : ($item_type === 'bundle' ? $settings['freemius_bundles'] :  $settings['freemius_themes']);

            if ($settings['type'] === 'download_stats') {
                if (isset($this->lists[$item_type . 's'], $this->lists[$item_type . 's'][$item_id])) {
                    $avatar = $this->lists[$item_type . 's'][$item_id]['icon'];
                }
                if ($settings['show_notification_image'] === 'gravatar' && !empty($data['picture'])) {
                    $avatar = $data['picture'];
                }
            }

            if ($settings['type'] === 'conversions') {
                if ($settings['show_notification_image'] === 'featured_image') {
                    if (isset($this->lists[$item_type . 's'], $this->lists[$item_type . 's'][$item_id])) {
                        $avatar = $this->lists[$item_type . 's'][$item_id]['icon'];
                    }
                }
                if ($settings['show_notification_image'] === 'gravatar' && !empty($data['picture'])) {
                    $avatar = $data['picture'];
                }
            }

            if ($settings['type'] === 'reviews') {
                if ($settings['show_notification_image'] === 'gravatar' && !empty($data['picture'])) {
                    $avatar = $data['picture'];
                }
                if ($settings['show_notification_image'] === 'featured_image') {
                    if (isset($this->lists[$item_type . 's'], $this->lists[$item_type . 's'][$item_id])) {
                        $avatar = $this->lists[$item_type . 's'][$item_id]['icon'];
                    }
                }
            }

            $image_data['url'] = $avatar;
        }

        return $image_data;
    }

    public function get_plugin_name($settings){
        $item_type   = $settings['freemius_item_type'];
        $item_id     = $this->getItemId($settings);
        $plugin_name = isset($this->lists[$item_type . 's'][$item_id]) ? $this->lists[$item_type . 's'][$item_id]['title'] : '';
        return $plugin_name;
    }

    /**
     * Undocumented function
     *
     * @param array $entries
     * @param array $ids
     * @return void
     */
    public function nx_frontend_get_entries($entries, $ids) {
        $freemius_entries = [];
        $notifications = PostType::get_instance()->get_posts_by_ids( $ids, $this->id );

        if(!empty($notifications)){
            foreach ($notifications as $key => $post) {
                if (!empty($post[$this->meta_key])) {
                    foreach ($post[$this->meta_key] as $key => $entry) {
                        $entry = wp_parse_args($entry, [
                            'nx_id'      => $post['nx_id'],
                            'source'     => $post['source'],
                            'entry_key'  => rand(),
                            'timestamp'  => time(),
                            'created_at' => date("Y-m-d H:i:s", time()),
                            'updated_at' => date("Y-m-d H:i:s", time()),
                        ]);
                        $freemius_entries[] = apply_filters('nx_get_entry', $entry);
                    }
                }
            }
            $freemius_entries = apply_filters('nx_get_entries', $freemius_entries);
            $entries          = array_merge($entries, $freemius_entries);
        }
        return $entries;
    }

    public function getItemId($settings) {
        $item_type = $settings['freemius_item_type'];
        switch ($item_type) {
            case 'plugin':
                return $settings['freemius_plugins'];
            case 'bundle':
                return $settings['freemius_bundles'];
            case 'theme':
                return $settings['freemius_themes'];
            case 'addon':
                return $settings['freemius_addons'];
            default:
                return null;
        }
    }

    public function restResponse($args) {
        if (isset($args['plugin_id'])) {
            $transient_key = 'nx_api_data_' . md5(serialize($args));
            $api_data      = false;
            if ($api_data === false) {
                $api_data = Helper::get_instance()->get_addons($args);
                set_transient($transient_key, $api_data, 5 * MINUTE_IN_SECONDS);
            }

            $new_data = array();
            if( ! empty( $api_data['plugins'] ) ) {
                foreach( $api_data['plugins'] as $single_data ) {
                    $this->lists['addons'][$single_data['id']] = $single_data;
                    $new_data[$single_data['id']] = $single_data['title'];
                }
                update_option( 'nxpro_freemius_data', $this->lists);
            }

            return array_values(GlobalFields::get_instance()->normalize_fields($new_data));
        }
        return [];
    }
}

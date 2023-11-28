<?php
/**
 * Envato Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Envato;

use NotificationX\Core\Rules;
use NotificationX\Admin\Cron;
use NotificationX\Admin\Settings;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Extensions\Envato\Envato as EnvatoFree;
use NotificationX\Extensions\GlobalFields;

/**
 * Envato Extension
 */
class Envato extends EnvatoFree {
    public $meta_key  = 'envato_content';
    public $cron_schedule = 'nx_envato_interval';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->_token = Settings::get_instance()->get('settings.envato_token');
    }

    public function source_error_message($messages) {
        if ( empty( $this->_token ) ) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#envato_settings_section');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a>.',
                    __( 'You have to setup your API Token for', 'notificationx-pro' ),
                    $url,
                    __('Envato', 'notificationx-pro')
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function init(){
        parent:: init();
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        // settings page
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
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

        // add_filter('nx_frontend_get_entries', [$this, 'nx_frontend_get_entries'], 10, 2);
    }

    public function admin_actions() {
        parent::admin_actions();
        add_action("nx_cron_update_data_{$this->id}", array($this, 'update_data'), 10, 2);
    }

    public function api_integration_settings($fields){
        $fields['envato_settings_section'] = array(
            'type' => 'section',
            'name' => 'envato_settings_section',
            'label' => __( 'Envato Settings', 'notificationx-pro' ),
            'modules' => 'modules_envato',
            'priority' => 50,
            'rules' => Rules::is('modules.modules_envato', true),
            'fields' => array(
                'envato_token' => array(
                    'name' => 'envato_token',
                    'description'    => sprintf('<a target="_blank" rel="nofollow" href="%3$s">%1$s</a> %2$s',
                                        __('Click here', 'notificationx-pro'),
                                        __('to get your API Access Token.', 'notificationx-pro'),
                                        'https://build.envato.com'
                                    ),
                    'type'      => 'text',
                    'label'     => __('API Access Token' , 'notificationx-pro'),
                    'priority'	=> 5,
                ),
                'envato_cache_duration' => array(
                    'name'        => 'envato_cache_duration',
                    'type'        => 'text',
                    'label'       => __('Cache Duration' , 'notificationx-pro'),
                    'default'     => 5,
                    'priority'    => 5,
                    'description' => __( 'Minutes, scheduled duration for collecting new data.', 'notificationx-pro' ),
                ),
                [
                    'name' => 'envato_connect',
                    // 'label' => 'Connect Button',
                    'type' => 'button',
                    'default' => false,
                    'text' => [
                        'normal' => __('Connect', 'notificationx-pro'),
                        'saved' => __('Refresh', 'notificationx-pro'),
                        'loading' => __('Refreshing...', 'notificationx-pro')
                    ],
                    'ajax' => [
                        'on' => 'click',
                        'api' => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'                => $this->id,
                            'envato_token'          => '@envato_token',
                            'envato_cache_duration' => '@envato_cache_duration',
                        ],
                    ]
                ],
            )
        );
        return $fields;
    }

    public function connect($params) {
        if (!empty($params['envato_token'])) {
            Settings::get_instance()->set('settings.envato_token', $params['envato_token']);
            Settings::get_instance()->set('settings.envato_cache_duration', $params['envato_cache_duration']);
            $envato_token = $params['envato_token'];
            if (!empty($envato_token)) {
                // @todo update existing post with data.
                Settings::get_instance()->set('settings.envato_connect', true);
                return array(
                    'status' => 'success',
                );
            }
        }
        return array(
            'status' => 'error',
            'message' => __('Please insert a valid API key.', 'notificationx-pro')
        );
    }

    public function notification_image($image_data, $data, $settings) {
        if ($settings['show_notification_image'] == 'featured_image') {
            $image_data['url'] = $data['icon_url'];
        }
        return $image_data;
    }

    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
        return $post;
    }

    public function update_data($nx_id, $data = []) {
        if (empty($data)) {
            $data = PostType::get_instance()->get_post($nx_id);
        }

        $sales = $this->get_sales();
        // removing old notifications.
        $this->delete_notification(null, $nx_id);
        $entries = [];
        foreach ($sales as $sale) {
            $entries[] = [
                'nx_id'      => $nx_id,
                'source'     => $this->id,
                'entry_key'  => $this->meta_key,
                'data'       => $sale,
            ];
        }
        $this->update_notifications($entries);
    }

    protected function get_sales() {
        if (empty($this->_token)) {
            return [];
        }
        $request = wp_remote_get(
            'https://api.envato.com/v3/market/author/sales',
            array(
                'headers'     => array(
                    'Authorization' => 'Bearer ' . $this->_token,
                ),
            )
        );

        if (is_wp_error($request)) {
            return [];
        }

        $decoded_body = json_decode(wp_remote_retrieve_body($request), true);

        $data_array = $this->temp_data_array = array();

        $needed_key = array(
            'sold_at', 'id', 'name', 'number_of_sales', 'url', 'rating', 'rating_count', 'published_at', 'icon_url'
        );
        if (!empty($decoded_body)) {
            foreach ($decoded_body as $single) {
                if (!is_array($single)) {
                    continue;
                }
                if (isset($single['item']['attributes'])) {
                    unset($single['item']['attributes']);
                }
                array_walk_recursive($single, array($this, 'walker'), $needed_key);
                $data_array[] = $this->temp_data_array;
                $this->temp_data_array = [];
            }
        }

        return $data_array;
    }

    private function walker($value, $key, $needed_key) {
        if ($this->in_array_r($key, $needed_key, true)) {
            if ($key === 'sold_at') {
                $value = strtotime($value);
                $key = 'timestamp';
            }
            if ($key === 'name') {
                $key = 'title';
            }
            if ($key === 'url') {
                $key = 'link';
            }
            $this->temp_data_array[$key] = $value;
        }
    }

    private function in_array_r($needle, $haystack, $strict = false) {
        foreach ($haystack as $item) {
            if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && $this->in_array_r($needle, $item, $strict))) {
                return true;
            }
        }
        return false;
    }



    /**
     * Undocumented function
     *
     * @param array $entries
     * @param array $ids
     * @return void
     */
    public function nx_frontend_get_entries($entries, $ids) {
        $convertkit_entries = [];
        $notifications = PostType::get_instance()->get_posts_by_ids( $ids, $this->id );

        if (!empty($notifications)) {
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
                        $convertkit_entries[] = apply_filters('nx_get_entry', $entry);
                    }
                }
            }
            $convertkit_entries = apply_filters('nx_get_entries', $convertkit_entries);
            $entries = array_merge($entries, $convertkit_entries);
        }
        return $entries;
    }

}
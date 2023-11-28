<?php

/**
 * ConvertKit Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\ConvertKit;

use NotificationX\Admin\Cron;
use NotificationX\Admin\Settings;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\ConvertKit\ConvertKit as ConvertKitFree;
use NotificationX\Extensions\GlobalFields;

/**
 * ConvertKit Extension
 */
class ConvertKit extends ConvertKitFree {

    public  $api_key = '';
    public  $api_secret = '';
    public  $meta_key = 'convertkit_content';
    public $cron_schedule = 'nx_convertkit_interval';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->api_key = Settings::get_instance()->get('settings.convertkit_api_key');
        $this->api_secret = Settings::get_instance()->get('settings.convertkit_api_secret');
    }

    public function source_error_message($messages) {
        if ( empty( $this->api_key ) || empty( $this->api_secret ) ) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#convertkit_settings_section');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a>.',
                    __( 'You have to setup your API Key for', 'notificationx-pro' ),
                    $url,
                    __('ConvertKit', 'notificationx-pro')
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function init() {
        parent::init();

        // @todo Something
        // add_filter( 'nxpro_js_scripts', array( $this, 'convertkit_js_text' ), 10, 1 );

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


    public function init_fields() {
        parent::init_fields();
        add_filter('nx_content_fields', [$this, 'content_fields']);
        // settings page
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    /**
     * Get data for WooCommerce Extension.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];
        $content_fields['convertkit_form'] = [
            'name' => 'convertkit_form',
            'type'     => 'select',
            'label'    => __('ConvertKit Form', 'notificationx-pro'),
            'priority' => 61,
            'options'  => GlobalFields::get_instance()->normalize_fields($this->get_forms()),
            'rules' => Rules::is('source', $this->id),
        ];
        return $fields;
    }

    /**
     * Undocumented function
     *
     * @param Object $convertkit
     * @return void
     */
    protected function get_forms($convertkit = false) {
        $options = [];
        if ($convertkit !== false) {
            if (isset($convertkit->forms) && !empty($convertkit->forms)) {
                foreach ($convertkit->forms as $form) {
                    $options[$form->id] = $form->name;
                }
                return $options;
            } else {
                return false;
            }
        }


        $results = get_option('nxpro_convertkit_forms');

        if (!empty($results)) {
            foreach ($results as $key => $list) {
                $options[$key] = $list;
            }
        }

        return $options;
    }


    // @todo Something
    public function convertkit_js_text($data) {
        $data['mc_on_success'] = __('You have successfully connected with ConvertKit, Your lists has been recorded for future use.', 'notificationx-pro');
        $data['mc_on_error'] = __('Something went wrong. Try again.', 'notificationx-pro');

        return $data;
    }

    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    public function update_data($nx_id, $data = array()) {
        if (empty($data)) {
            $data = PostType::get_instance()->get_post($nx_id);
        }

        $members = $this->get_member($data, $nx_id);
        // removing old notifications.
        $this->delete_notification(null, $nx_id);
        $entries = [];
        foreach ($members as $member) {
            $entries[] = [
                'nx_id'      => $nx_id,
                'source'     => $this->id,
                'entry_key'  => $this->meta_key,
                'data'       => $member,
            ];
        }
        $this->update_notifications($entries);
    }

    public function get_member($nx_meta, $nx_id) {
        $members   = [];
        $form_id   = $nx_meta['convertkit_form'];
        $limit     = $nx_meta['display_last'];
        $list_name = get_option('nxpro_convertkit_forms');

        // Return of convertkit list field is empty.
        if (!$form_id || empty($form_id)) {
            return $members;
        }

        // Return if api key is empty.
        if (!$this->api_secret || empty($this->api_secret)) {
            return $members;
        }

        if (empty($list_name[$form_id])) {
            return $members;
        }

        // Get limit.

        // Set limit to 100 if empty.
        if (empty($limit) || !$limit) {
            $limit = 20;
        }

        $response = $this->get_members($this->api_secret, $form_id, $limit);

        if (!empty($response['members'])) {

            $api_data = $response['members']->subscriptions;

            foreach ($api_data as $member) {
                $members[] = $this->member($member, $list_name[$form_id]);
            }
        }

        return $members;
    }

    protected function member($data, $title) {
        $member['title'] = $title;
        $member['email'] = $data->subscriber->email_address;

        $first_name = isset($data->subscriber->first_name) ? $data->subscriber->first_name : '';
        $last_name  = isset($data->subscriber->last_name) ? $data->subscriber->last_name : '';

        $member['first_name'] = isset($data->subscriber->first_name) ? $data->subscriber->first_name : '';
        $member['last_name']  = isset($data->subscriber->last_name) ? $data->subscriber->last_name : '';
        $member['name']       = $first_name . ' ' . $last_name;
        $member['timestamp']  = strtotime($data->subscriber->created_at);
        $member['link']       = '';

        return $member;
    }

    protected function convertkit($api_key, $api_secret) {
        if (empty($api_key) || empty($api_secret)) {
            return false;
        }

        $request = $this->remote_get('https://api.convertkit.com/v3/forms', array(
            'body' => array(
                'api_key' => $api_key,
                'api_secret' => $api_secret,
            )
        ));

        return $request;
    }

    protected function get_members($api_secret = '', $form_id = '', $limit = 20) {
        $response = array(
            'error'      => false,
            'members'    => array()
        );

        // Make sure we have an API key.
        if (empty($api_secret)) {
            $response['error'] = __('Error: You must provide an API key.', 'notificationx-pro');
        }

        // Make sure we have list id.
        if (empty($form_id)) {
            $response['error'] = __('Error: You must provide a Form.', 'notificationx-pro');
        }

        $url = "https://api.convertkit.com/v3/forms/$form_id/subscriptions";

        if (!$response['error']) {
            try {
                $request = $this->remote_get($url, array(
                    'body' => array(
                        'api_secret' => $api_secret,
                    )
                ));

                if (isset($request->error)) {
                    $response['error'] = $request->error . '. ' . $request->message;
                } else {
                    $response['members'] = $request;
                }
            } catch (\Exception $e) {
                $response['error'] = __('Error: Something wrong ', 'notificationx-pro');
            }
        }

        return $response;
    }

    public function api_integration_settings($fields) {

        $fields['convertkit_settings_section'] = [
            'name' => 'convertkit_settings_section',
            'type' => 'section',
            'label' => __('ConvertKit Settings', 'notificationx-pro'),
            'modules' => 'modules_convertkit',
            'priority' => 20,
            'rules' => Rules::is('modules.modules_convertkit', true),
            'fields' => [
                'convertkit_api_key' => [
                    'name' => 'convertkit_api_key',
                    'type'        => 'text',
                    'label'       => __('API Key', 'notificationx-pro'),
                    'default'     => '',
                    'priority'    => 5,
                    'description' => '<a target="_blank" rel="nofollow" href="https://developers.convertkit.com">' . __('Click here', 'notificationx-pro') . '</a> ' . __('to get API Key.', 'notificationx-pro'),
                ],
                'convertkit_api_secret' => [
                    'name' => 'convertkit_api_secret',
                    'type'        => 'text',
                    'label'       => __('API Secret', 'notificationx-pro'),
                    'default'     => '',
                    'priority'    => 5,
                    'description' => '<a target="_blank" rel="nofollow" href="https://developers.convertkit.com">' . __('Click here', 'notificationx-pro') . '</a> ' . __('to get API Secret.', 'notificationx-pro'),
                ],
                'convertkit_cache_duration' => [
                    'name' => 'convertkit_cache_duration',
                    'type'        => 'text',
                    'label'       => __('Cache Duration', 'notificationx-pro'),
                    'default'     => 3,
                    'priority'    => 5,
                    'description' => __('Minutes, scheduled duration for collecting new data.', 'notificationx-pro'),
                ],
                [
                    'name' => 'convertkit_connect',
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
                            'source'         => $this->id,
                            'api_key'        => '@convertkit_api_key',
                            'api_secret'     => '@convertkit_api_secret',
                            'cache_duration' => '@convertkit_cache_duration',
                        ],
                    ]
                ],
            ],
        ];
        return $fields;
    }

    public function connect($params) {
        if (isset($params['api_key'], $params['api_secret'])) {
            Settings::get_instance()->set('settings.convertkit_api_key', $params['api_key']);
            Settings::get_instance()->set('settings.convertkit_api_secret', $params['api_secret']);
            Settings::get_instance()->set('settings.convertkit_cache_duration', $params['cache_duration']);

            $api_key = $params['api_key'];
            $api_secret = $params['api_secret'];
            if (!empty($api_key) && !empty($api_secret)) {
                $connection = $this->convertkit($api_key, $api_secret);
                if ($connection) {
                    if (!isset($connection->error)) {
                        $forms = $this->get_forms($connection);
                        if ($forms) {
                            update_option('nxpro_convertkit_forms', $forms);
                            Settings::get_instance()->set('settings.convertkit_connect', true);
                        }
                        return array(
                            'status' => 'success',
                        );
                    } else {
                        return array(
                            'status' => 'error',
                            'message' => $connection->error . '. ' . $connection->message
                        );
                    }
                } else {
                    return array(
                        'status' => 'error',
                        'message' => $connection->error . '. ' . $connection->message
                    );
                }
            }
        }
        return array(
            'status' => 'error',
            'message' => __('Please insert a valid API key.', 'notificationx-pro')
        );
    }
}

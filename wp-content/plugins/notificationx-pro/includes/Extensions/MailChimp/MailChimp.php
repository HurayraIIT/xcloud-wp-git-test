<?php

/**
 * MailChimp Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\MailChimp;

use NotificationX\Admin\Cron;
use NotificationX\Admin\Settings;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\MailChimp\MailChimp as MailChimpFree;
use NotificationX\Extensions\GlobalFields;

/**
 * MailChimp Extension
 */
class MailChimp extends MailChimpFree {
    public $api_key  = '';
    public $meta_key = 'mailchimp_content';
    public $cron_schedule = 'nx_mailchimp_interval';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->api_key = Settings::get_instance()->get('settings.mailchimp_api_key');
    }

    public function source_error_message($messages) {
        if ( empty( $this->api_key ) ) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#mailchimp_settings_section');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a>.',
                    __( 'You have to setup your API Key for', 'notificationx-pro' ),
                    $url,
                    __('MailChimp', 'notificationx-pro')
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
        add_action("nx_cron_update_data_{$this->id}", array($this, 'update_data'), 10, 2);
    }

    /**
     * common init function for admin and frontend.
     */
    public function init_settings_fields() {
        parent::init_settings_fields();
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    /**
     * common init function for admin and frontend.
     */
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_content_fields', [$this, 'content_fields']);
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

    }

    /**
     * Get data for WooCommerce Extension.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_fields($fields) {
        $content_fields = &$fields['content']['fields'];

        $content_fields['mailchimp_list'] = [
            'label'    => __('MailChimp List', 'notificationx-pro'),
            'name'     => 'mailchimp_list',
            'type'     => 'select',
            'priority' => 80,
            'options'  => GlobalFields::get_instance()->normalize_fields($this->get_lists()),
            'rules'    => Rules::is('source', $this->id),
        ];
        return $fields;
    }


    protected function get_lists() {
        $options = [];

        $results = get_option('nxpro_mailchimp_lists');

        if (!empty($results)) {
            foreach ($results as $key => $list) {
                $options[$key] = $list;
            }
        }

        return $options;
    }

    public function api_integration_settings($fields) {

        $fields['mailchimp_settings_section'] = array(
            'type'     => 'section',
            'name'     => 'mailchimp_settings_section',
            'modules'  => $this->module,
            'label'    => __('MailChimp Settings', 'notificationx-pro'),
            'rules'    => Rules::is('modules.modules_mailchimp', true),
            'priority' => 10,
            'fields'   => array(
                'mailchimp_api_key' => array(
                    'name'        => 'mailchimp_api_key',
                    'type'        => 'text',
                    'label'       => __('MailChimp API Key', 'notificationx-pro'),
                    'default'     => '',
                    'priority'    => 5,
                    // translators: %1$s: MailChimp API docs link.
                    'description' => sprintf(__('<a target="_blank" rel="nofollow" href="%s">Click here</a> to get your API Key.', 'notificationx-pro'), 'https://mailchimp.com/help/about-api-keys/'),
                ),
                'mailchimp_cache_duration' => array(
                    'name'        => 'mailchimp_cache_duration',
                    'type'        => 'text',
                    'label'       => __('Cache Duration', 'notificationx-pro'),
                    'default'     => 5,
                    'priority'    => 5,
                    'description' => __('Minutes, scheduled duration for collecting new data.', 'notificationx-pro'),
                ),
                [
                    'name' => 'mailchimp_connect',
                    // 'label' => 'Connect Button',
                    'type' => 'button',
                    'default' => false,
                    'text' => [
                        'normal'  => __('Connect', 'notificationx-pro'),
                        'saved'   => __('Refresh', 'notificationx-pro'),
                        'loading' => __('Refreshing...', 'notificationx-pro')
                    ],
                    'ajax' => [
                        'on' => 'click',
                        'api' => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'         => $this->id,
                            'api_key'        => '@mailchimp_api_key',
                            'cache_duration' => '@mailchimp_cache_duration',
                        ],
                    ]
                ],
            ),
        );
        return $fields;
    }



    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    public function update_data($nx_id, $data = []) {
        if (empty($data)) {
            $data = PostType::get_instance()->get_post($nx_id);
        }

        $members = $this->get_members($data);

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

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array  $data
     * @return void
     */
    public function get_notification_ready( $post, $nx_id ) {
        $this->update_data($nx_id, $post);
    }

    private function member($data, $title) {
        $firstName = $lastName = $name = '';
        if (!empty($data['merge_fields'])) {
            $firstName = isset($data['merge_fields']['FNAME']) ? $data['merge_fields']['FNAME'] : '';
            $lastName = isset($data['merge_fields']['LNAME']) ? $data['merge_fields']['LNAME'] : '';

            if (!empty($firstName)) {
                $member['first_name'] = $firstName;
            }
            if (!empty($lastName)) {
                $member['last_name'] = $lastName;
            }

            $name = $firstName . ' ' . $lastName;
            $trimed_val = trim($name);
            if (!empty($trimed_val)) {
                $member['name'] = $name;
            }
        }

        if (!empty($data['email_address'])) {
            $member['email'] = $data['email_address'];
        }
        if (!empty($title)) {
            $member['title'] = $title;
        }
        if (!empty($data['status'])) {
            $member['status'] = $data['status'];
        }
        if (!empty($data['timestamp_opt'])) {
            $member['timestamp'] = get_gmt_from_date($data['timestamp_opt']);
        }
        else if (!empty($data['timestamp_signup'])) {
            $member['timestamp'] = get_gmt_from_date($data['timestamp_signup']);
        }

        $member['link'] = '';
        $member['ip'] = $data['ip_opt'];

        if(!empty($data['location']['latitude']) && !empty($data['location']['longitude'])){
            $member['lat'] = $data['location']['latitude'];
            $member['lon'] = $data['location']['longitude'];
        }

        $member['country'] = isset($data['location']['country_code']) ? $data['location']['country_code'] : '';

        return $member;
    }

    public function get_members($data) {
        $members = [];

        $list_id = $data['mailchimp_list'];
        $limit = $data['display_last'];

        $list_name = get_option('nxpro_mailchimp_lists');

        if (empty($list_name[$list_id])) {
            return $members;
        }

        // Return of mailchimp list field is empty.
        if (!$list_id || empty($list_id)) {
            return $members;
        }

        // Return if api key is empty.
        if (!$this->api_key || empty($this->api_key)) {
            return $members;
        }
        // Get limit.
        // Set limit to 100 if empty.
        if (empty($limit) || !$limit) {
            $limit = 20;
        }

        $response = Helper::get_members($this->api_key, $list_id, $limit);

        if ($response['error']) {
            return $members;
        }

        if (is_array($response['members']['members'])) {
            foreach ($response['members']['members'] as $parent_key => $member) {
                $members[] = $this->member($member, $list_name[$list_id]);
            }
        }

        return $members;
    }

    public function connect($params) {
        return Helper::connect($params);
    }

}

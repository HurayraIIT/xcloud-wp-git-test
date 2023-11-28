<?php

/**
 * Google_Analytics Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Google_Analytics;

use NotificationX\Admin\Cron;
use NotificationX\Admin\Settings;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Google_Analytics\Google_Analytics as Google_AnalyticsFree;
use NotificationX\Extensions\GlobalFields;
use NotificationXPro\Admin\Entries;
use NxProGA\Google\Service\Analytics;

/**
 * Google_Analytics Extension
 */
class Google_Analytics extends Google_AnalyticsFree {

    /**
     * meta key for saving google analytics data in the notificationx post
     * @var string
     */
    public $meta_key = 'google_analytics_data';
    /**
     * notificationx google client helper instance
     * @var object
     */
    public $nx_google_client = null;
    /**
     * token information of google client, includes refresh token and timestamp
     * @var array
     */
    public $token_info;
    /**
     * page analytics options
     * @var array
     */
    public $pa_options;

    /**
     * Cache duration for nx google app
     * 3 minute
     */
    private $nx_app_min_cache_duration = 3;

    /**
     * Async request object
     */
    public $is_ga_connected = false;
    public $request;

    /**
     * GA connection error.
     */
    public static $error_message;
    public $cron_schedule = 'nx_ga_cache_duration';


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_action('admin_init', array($this, 'init_google_client'));
        $this->get_options();
        $this->get_token_info();
    }

    public function source_error_message($messages) {
        if ( empty( $this->pa_options ) ) {
            $url = admin_url('admin.php?page=nx-settings');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a> %s.',
                    __( 'You have to connect your', 'notificationx-pro' ),
                    $url,
                    __('Google Analytics Account', 'notificationx-pro'),
                    __('first', 'notificationx-pro')
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
        // add_action("nx_saved_post_{$this->id}", [$this, 'saved_post'], 10, 3);
        add_action("nx_cron_update_data_{$this->id}", array($this, 'update_data'), 10, 2);
        add_filter('nx_settings', [$this, 'nx_settings']);
    }

    public function init_fields() {
        parent::init_fields();
        add_filter('nx_content_fields', [$this, 'content_fields_pro'], 20);
        add_filter('nx_customize_fields', [$this, 'customize_fields_pro'], 20);
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        // settings page
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();

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
        add_filter('nx_frontend_get_entries',array( $this,'frontend_filter_ga_entries'), 10, 4 );
        add_filter("nx_get_entries_query_part_{$this->id}", array($this,'frontend_filter_ga_query'),10,3 );
    }

    public function frontend_filter_ga_query( $global_query, $notification, $params ) {
        if( $notification['theme'] == 'page_analytics_pa-theme-four' ) {
            $global_query .=  " AND entry_key = '" . esc_sql($params['extra']['url']) . "' ";
        }
        return $global_query;
    }

    /**
     * Filter Google Analytics data 
     * @param $entries
     * @param $ids 
     * @param $notifications
     * @param $param
     * 
     * @return array
     */
    public function frontend_filter_ga_entries( $entries, $ids, $notifications, $params = [] ) {
        if( empty( $params['extra']['url'] ) ) {
            return $entries;
        }

        $cache_duration =  Settings::get_instance()->get('settings.ga_cache_duration', 20);
        foreach ($notifications as $key => $notification) {
            if( $notification['source'] == $this->id ) {
                $is_expired = false;
                
                foreach ($entries as $entry_key => $entry) {
                    if( $entry['nx_id'] == $notification['nx_id'] && $entry['link'] == $params['extra']['url'] ) {
                        if( time() - ( $cache_duration * MINUTE_IN_SECONDS) <= strtotime( $entry['created_at'] ) ) {
                            $entry['ga_page_title'] = preg_replace('/\s*–\s*.*/', '', $params['extra']['page_title']);
                            $entries[$entry_key] = $entry;
                        }else{
                            $is_expired = true;
                            unset( $entries[$entry_key] );
                        }
                    }
                }
                if( $is_expired || empty( $entries ) ) {
                    Entries::get_instance()->delete_entries([
                        'nx_id' => $notification['nx_id'],
                        'source'    => $this->id,
                        'entry_key' => $params['extra']['url'],
                    ]);
                    $notification_settings = PostType::get_instance()->get_post($notification['nx_id']);
                    $global_settings = Settings::get_instance()->get('settings');
                    $args['notification_settings']  = $notification_settings;
                    $args['global_settings']        = $global_settings;
                    $args['page_url']               = $params['extra']['url'];
                    new Google_Analytics_Updater($args);
                    $db_entries = Entries::get_instance()->get_entries([
                        'nx_id' => $notification['nx_id'],
                        'source'    => $this->id,
                        'entry_key' => $params['extra']['url'],
                    ]);
                    foreach ($db_entries as $entry_key => $entry) {
                        if( $entry['nx_id'] == $notification['nx_id'] && $entry['link'] == $params['extra']['url'] ) {
                            $entry['ga_page_title'] = preg_replace('/\s*–\s*.*/', '', $params['extra']['page_title']);
                            $db_entries[$entry_key] = $entry;
                        }
                    }
                   $entries = array_merge( $entries, $db_entries );
                } 
            }
        }
      return $entries;
    }


    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
        return $post;
    }

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    /**
     * Update post analytics data if required
     * @hooked in 'wp'
     * @return void
     */
    public function update_data($nx_id, $notification_meta = []) {
        if (empty($notification_meta)) {
            $notification_meta = PostType::get_instance()->get_post($nx_id);
        }

        $global_settings = Settings::get_instance()->get('settings');
        if (!isset($global_settings['ga_profile']) || empty($global_settings['ga_profile'])) {
            return;
        }
        $this->insert_data($notification_meta, $global_settings);
    }

    /**
     * insert post analytics data
     * @param object $post
     * @param object $notification_settings
     * @param array $saved_data
     * @return void
     */
    public function insert_data($notification_settings, $global_settings, $saved_data = array()) {
        new Google_Analytics_Updater(array(
            'notification_settings' => $notification_settings,
            'global_settings' => $global_settings,
            'saved_data' => $saved_data
        ));
    }


    /**
     * This method adds google analytics settings section in admin settings
     * @param array $sections
     * @return array
     */
    public function api_integration_settings($sections) {
        $nx_google_client = $this->get_google_client();

        $sections['google_analytics_settings_section'] = array(
            'name'     => 'google_analytics_settings_section',
            'type'     => 'section',
            'label'    => __('Google Analytics Settings', 'notificationx-pro'),
            'modules'  => 'modules_google_analytics',
            'priority' => 6,
            'rules'    => Rules::is('modules.modules_google_analytics', true),
            'fields'   => [
                'ga_connect' => array(
                    'name'      => 'ga_connect',
                    'type'      => 'button',
                    'text'      => __('Connect your account', 'notificationx-pro'),
                    'label'     => __('Connect with Google Analytics', 'notificationx-pro'),
                    'className' => 'ga-btn connect-analytics',
                    'href'      => 'https://accounts.google.com/o/oauth2/auth?client_id=' . $nx_google_client->client_id . '&response_type=code&access_type=offline&approval_prompt=force&redirect_uri=' . urlencode($nx_google_client->redirect_uri) . '&scope=' . urlencode('https://www.googleapis.com/auth/analytics.readonly') . '&state=' . urlencode(admin_url('admin.php?page=nx-settings&tab=tab-api-integrations')),
                    // 'rules'    => Rules::logicalRule([Rules::is('is_ga_connected', true, true), Rules::is('ga_disconnect', true)], 'or'),
                    'rules'    => Rules::logicalRule([Rules::is('is_ga_connected', true, true), Rules::is('ga_own_app', false)], 'and'),
                    // 'rules'    => Rules::is('is_ga_connected', true, true),
                ),
                'ga_own_app' => array(
                    'name'    => 'ga_own_app',
                    'type'    => 'toggle',
                    'default' => false,
                    'label'   => __('Setup your Google App', 'notificationx-pro'),
                    // 'link_text' => __('Setup Now', 'notificationx-pro'),
                    'className' => 'ga-btn setup-google-app',
                    // translators: %s: Google Analytics docs link.
                    'help' => sprintf(__('By setting up your app, you will be disconnected from current account. See our <a target="_blank" rel="nofollow" href="%s">Creating Google App in Cloud</a> documentation for help', 'notificationx-pro'), 'https://notificationx.com/docs/google-analytics/'),
                    // 'rules'    => Rules::logicalRule([Rules::is('is_ga_connected', true, true), Rules::is('ga_disconnect', true)], 'or'),
                    'rules'    => Rules::is('is_ga_connected', true, true),
                ),

                'ga_disconnect' => array(
                    'name'      => 'ga_disconnect',
                    'type'      => 'button',
                    'label'     => __('Disconnect from google analytics', 'notificationx-pro'),
                    'text'      => __('Logout from account', 'notificationx-pro'),
                    'className' => 'ga-btn disconnect-analytics',
                    'rules'     => Rules::is('is_ga_connected', true),
                    'ajax'      => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'    => $this->id,
                            'type'      => 'ga_disconnect',
                        ],
                        'trigger' => '@is_ga_connected:false',
                        'hideSwal' => true,
                    ],
                ),
                'ga_profile' => array(
                    'name'     => 'ga_profile',
                    'type'     => 'select',
                    'label'    => __('Select Profile', 'notificationx-pro'),
                    'options'  => $this->get_profiles(),
                    'default'  => 'everyone',
                    'priority' => 0,
                    'rules'    => Rules::is('is_ga_connected', true),
                ),
                'ga_cache_duration' => array(
                    'name'        => 'ga_cache_duration',
                    'type'        => 'number',
                    'label'       => __('Cache Duration', 'notificationx-pro'),
                    // 'default'     => $this->nx_app_min_cache_duration,
                    // 'min'         => $this->pa_options['ga_app_type'] == 'nx_app' ? $this->nx_app_min_cache_duration : 1,
                    'priority'    => 5,
                    'description' => __('Minutes, scheduled duration for collecting new data.', 'notificationx-pro'),
                    'rules'       => Rules::is('is_ga_connected', true),
                ),

                'ga_redirect_uri' => array(
                    'name'        => 'ga_redirect_uri',
                    'type'        => 'text',
                    'label'       => __('Redirect URI', 'notificationx-pro'),
                    'className'   => 'ga-client-id ga-hidden',
                    'default'     => admin_url('admin.php?page=nx-admin'),
                    'readOnly'    => true,
                    'help'        => __('Copy this and paste it in your google app redirect uri field', 'notificationx-pro'),
                    'description' => __('Keep it in your google cloud project app redirect uri.', 'notificationx-pro'),
                    'rules'       => Rules::logicalRule([Rules::is('ga_own_app', true), Rules::is('is_ga_connected', true, true)]),
                ),
                'ga_client_id' => array(
                    'name'        => 'ga_client_id',
                    'type'        => 'text',
                    'label'       => __('Client ID', 'notificationx-pro'),
                    'className'   => 'ga-client-id ga-hidden',
                    // translators: %1$s: Google API dashboard link, %2$s: Google Analytics docs link.
                    'description' => sprintf(__('<a target="_blank" rel="nofollow" href="%1$s">Click here</a> to get Client ID by Creating a Project or you can follow our <a rel="nofollow" target="_blank" href="%2$s">documentation</a>.', 'notificationx-pro'), 'https://console.cloud.google.com/apis/dashboard', 'https://notificationx.com/docs/google-analytics/'),
                    'rules'       => Rules::logicalRule([Rules::is('ga_own_app', true), Rules::is('is_ga_connected', true, true)]),
                ),
                'ga_client_secret' => array(
                    'name'        => 'ga_client_secret',
                    'type'        => 'text',
                    'label'       => __('Client Secret', 'notificationx-pro'),
                    'className'   => 'ga-client-secret ga-hidden',
                    // translators: %1$s: Google API dashboard link, %2$s: Google Analytics docs link.
                    'description' => sprintf(__('<a target="_blank" rel="nofollow" href="%1$s">Click here</a> to get Client Secret by Creating a Project or you can follow our <a target="_blank" rel="nofollow" href="%2$s">documentation</a>.', 'notificationx-pro'), 'https://console.cloud.google.com/apis/dashboard', 'https://notificationx.com/docs/google-analytics/'),
                    'rules'       => Rules::logicalRule([Rules::is('ga_own_app', true), Rules::is('is_ga_connected', true, true)]),
                ),
                'ga_user_app_connect' => array(
                    'name'      => 'ga_user_app_connect',
                    'type'      => 'button',
                    'label'     => ' ',
                    'className' => 'ga-btn connect-user-app',
                    'text'      => [
                        'normal'  => __('Connect your account', 'notificationx-pro'),
                        'saved'   => __('Connected', 'notificationx-pro'),
                        'loading' => __('Connecting...', 'notificationx-pro')
                    ],
                    'ajax' => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'           => $this->id,
                            'type'             => 'user-app',
                            'ga_redirect_uri'  => '@ga_redirect_uri',
                            'ga_client_id'     => '@ga_client_id',
                            'ga_client_secret' => '@ga_client_secret',
                        ],
                        'trigger' => '@is_ga_connected:true',
                    ],
                    'rules' => Rules::logicalRule([Rules::is('ga_own_app', true), Rules::is('is_ga_connected', true, true)]),
                ),
                'ga_save_selected_profile' => array(
                    'name'      => 'ga_save_selected_profile',
                    'type'      => 'button',
                    // 'label'     => __('Save', 'notificationx-pro'),
                    'className' => 'ga-btn connect-user-app',
                    'text'      => [
                        'normal'  => __('Save', 'notificationx-pro'),
                        'saved'   => __('Saved', 'notificationx-pro'),
                        'loading' => __('Saving...', 'notificationx-pro')
                    ],
                    'ajax' => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'            => $this->id,
                            'type'              => 'save',
                            'ga_profile'        => '@ga_profile',
                            'ga_cache_duration' => '@ga_cache_duration',
                        ],
                        'swal' => [
                            'text'  => __('Changes Saved!', 'notificationx-pro'),
                            'icon'  => 'success',
                        ],
                    ],
                    'rules' => Rules::is('is_ga_connected', true),
                ),

                // 'token_info' => array(
                //     'name'      => 'token_info',
                //     'type'      => 'hidden',
                //     'default'   => $this->pa_options['token_info'],
                // ),

                'is_ga_connected' => array(
                    'name'      => 'is_ga_connected',
                    'type'      => 'hidden',
                    'default'   => $this->is_ga_connected(),
                ),

            ],
        );
        return $sections;
    }

    public function nx_settings($settings){
        if(isset($settings['is_ga_connected'])){
            unset($settings['is_ga_connected']);
        }
        return $settings;
    }

    /**
     * Render image link and alt title for conversions
     * Set default image if post featured image is not found
     * @hooked nx_notification_image
     * @todo PHP Warning: Undefined array key "id" in
     * @return void
     */
    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image']) {
            $image_data['url'] = get_the_post_thumbnail_url($data['id'], 'thumbnail');
        }
        return $image_data;
    }

    public function connect($params) {
        if (isset($params['ga_client_id'], $params['ga_client_secret'])) {
            Settings::get_instance()->set('settings.ga_redirect_uri', $params['ga_redirect_uri']);
            Settings::get_instance()->set('settings.ga_client_id', $params['ga_client_id']);
            Settings::get_instance()->set('settings.ga_client_secret', $params['ga_client_secret']);

            return array(
                'status' => 'success',
                'redirect' => 'https://accounts.google.com/o/oauth2/auth?&scope=' . urlencode('https://www.googleapis.com/auth/analytics.readonly') . '&response_type=code&access_type=offline&approval_prompt=force&redirect_uri=' . urlencode($params['ga_redirect_uri']) . '&client_id=' . $params['ga_client_id']
            );
        }

        if (isset($params['type']) && $params['type'] == 'ga_disconnect') {
            Settings::get_instance()->set("settings.{$this->option_key}", false);
            Settings::get_instance()->set("settings.is_ga_connected", false);
            return array(
                'status'          => 'success',
                'context' => [
                    $this->option_key => false,
                ]
            );
        }
        if (isset($params['type']) && $params['type'] == 'save') {
            Settings::get_instance()->set('settings.ga_profile', $params['ga_profile']);
            Settings::get_instance()->set('settings.ga_cache_duration', $params['ga_cache_duration']);
            return array('status' => 'success');
        }
    }

    /**
     * Init Google client with auth code
     * @return void
     */
    public function init_google_client() {
        if (isset($_GET['code']) && 'nx-settings' == $_GET['page']) {
            if (!empty($this->pa_options['auth_code'])) {
                if ($this->pa_options['auth_code'] === $_GET['code']) {
                    return;
                }
            }
            try {
                $this->authenticate($_GET['code']);
            } catch (\Exception $e) {
                // self::$error_message = $e->getMessage();
                // Helper::write_log(['error' => $e->getMessage()]);
            }
        }
        if (!empty($this->get_token_info())) {
            if (empty($this->pa_options['ga_profiles'])) {
                try {
                    $this->set_profiles();
                } catch (\Exception $e) {
                    // self::$error_message = $e->getMessage();
                    Helper::write_log(['error' => 'Set Profile failed. Details: ' . $e->getMessage()]);
                }
            }
        }
    }

    /**
     * Set page analytics options
     * @return void
     */
    public function get_options() {
        if(empty($this->pa_options)){
            $options          = Settings::get_instance()->get("settings.{$this->option_key}");
            $this->pa_options = !empty($options) ? $options : [];
        }
        return $this->pa_options;
    }

    public function set_option($options){
        $this->pa_options = $options;
        Settings::get_instance()->set("settings.{$this->option_key}", $options);
    }

    /**
     * Set page analytics options
     * @return void
     */
    public function get_token_info() {
        if(empty($this->token_info)){
            $pa_options = $this->get_options();
            $this->token_info = !empty($pa_options['token_info']) ? $pa_options['token_info'] : [];
        }
        return $this->token_info;
    }
    /**
     * Set page analytics options
     * @return Google_Client
     */
    public function get_google_client() {
        if(empty($this->nx_google_client)){
            $this->nx_google_client = Google_Client::getInstance();
        }
        return $this->nx_google_client;
    }
    /**
     * Set page analytics options
     * @return Google_Client
     */
    public function is_ga_connected() {
        if (empty($this->is_ga_connected)) {
            $nx_google_client = $this->get_google_client();
            $this->is_ga_connected = $nx_google_client->setAccessToken($this->get_token_info());
        }
        return $this->is_ga_connected;
    }

    /******** Class helper methods **********/

    /**
     * Google client instance
     * @return \NxPro_Google_Client|bool
     */
    private function client() {
        $nx_google_client = $this->get_google_client();
        $client = $nx_google_client->getClient();
        if (null == $client->getAccessToken()) {
            $token_info = $this->get_token_info();
            if (empty($token_info)) {
                Helper::write_log('Token not found in DB. Account connected but refresh token is removed from db. In: ' . __FILE__ . ', at line ' . __LINE__);
                return false;
            }
            $this->is_ga_connected = $nx_google_client->setAccessToken($token_info);
        }
        return $client;
    }

    /**
     * Authenticate user with auth code
     * Set access token for get data
     * Save token information in database
     * @throws \Exception
     */
    private function authenticate($code) {
        $nx_google_client = $this->get_google_client();
        if (!empty($_GET['token_info']))
            $token_info = $_GET['token_info'];
        else
            $token_info = $nx_google_client->getTokenWithAuthCode($code);

        if (!array_key_exists('error', $token_info)) {
            $nx_google_client->setAccessToken($token_info);
            $pa_options               = [];
            $pa_options['token_info'] = $token_info;
            $pa_options['auth_code']  = $code;
            if ($nx_google_client->getRedirectUri() == admin_url('admin.php?page=nx-admin')) {
                $pa_options['ga_app_type'] = 'user_app';
            } else {
                $pa_options['ga_app_type'] = 'nx_app';
            }

            $this->token_info = $token_info;
            $this->set_option($pa_options);

            Settings::get_instance()->set("settings.is_ga_connected", true);

            wp_redirect(admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_analytics_settings_section'));
        } else {
            throw new \Exception('Get token with auth code failed.' . $token_info['error']);
        }
    }

    /**
     * Get profiles in user google analytics account
     * Save profiles in database
     * @throws \Exception
     */
    private function set_profiles() {
        $client = $this->client();
        $analytics = new Analytics($client);
        $views = Google_Analytics_V4::get_property_list($this->pa_options['token_info']);
        $this->pa_options['ga_profiles'] = $views;
        Settings::get_instance()->set("settings.{$this->option_key}", $this->pa_options);

        $accounts = $analytics->management_accounts->listManagementAccounts();
        $items = $accounts->getItems();
        if (!empty($items)) {
            foreach ($items as $account) {
                if (is_array($account)) {
                    $account = (object) ($account);
                }
                $properties = $analytics->management_webproperties
                    ->listManagementWebproperties($account->id);
                $pItems = $properties->getItems();
                if (!empty($pItems)) {
                    foreach ($pItems as $property) {
                        $profiles = $analytics->management_profiles
                            ->listManagementProfiles($account->id, $property->id);
                        $profileItems = $profiles->getItems();
                        if (!empty($profileItems)) {
                            foreach ($profileItems as $profile) {
                                $views[$profile->getId()] = $account->name . ' => ' . $property->name . ' (' . $profile->name . ': ' . $profile->webPropertyId . ')';
                            }
                            $this->pa_options['ga_profiles'] = $views;
                            Settings::get_instance()->set("settings.{$this->option_key}", $this->pa_options);
                        } else {
                            // throw new \Exception('No views (profiles) found for this user.');
                        }
                    }
                } else {
                    // throw new \Exception('No properties found for this user.');
                }
            }
        } else {
            throw new \Exception('No accounts found for this user.');
        }
    }

    public function get_profiles() {
        return GlobalFields::get_instance()->normalize_fields(!empty($this->pa_options['ga_profiles']) ? $this->pa_options['ga_profiles'] : []);
    }

    /**
     * Set data for frontend
     * @param array $data
     * @param array $saved_data
     * @param array $settings
     * @return array
     */
    public function fallback_data($data, $saved_data, $settings) {
        $duration = isset( $settings['notification-template']['ga_fifth_param'] ) ? $settings['notification-template']['ga_fifth_param'] : 7;

        $data['title']     = __('this page', 'notificationx-pro');
        $data['this_page'] = __('this page', 'notificationx-pro');
        $data['day']       = sprintf(_n('day',  'days', $duration, 'notificationx-pro'), $duration);
        $data['month']     = sprintf(_n('month', 'months', $duration, 'notificationx-pro'), $duration);
        $data['year']      = sprintf(_n('year', 'years', $duration, 'notificationx-pro'), $duration);
        $data['ga_title']  = $saved_data['title'];
        $data['siteview']  = $saved_data['views'];
        $data['realtime_siteview'] = $saved_data['views'];
        $data['current_page_view'] = $saved_data['views'];

        $template_adv = !empty($settings['template_adv']);
        if($template_adv && ($settings['themes'] == 'page_analytics_pa-theme-two' || $settings['themes'] == 'page_analytics_pa-theme-one')){
            $advanced_template = $settings['advanced_template'];
            $matches           = [];
            preg_match('/{{((day|month|year):(\d+))}}/', $advanced_template, $matches);
            if(!empty($matches[0])){
                switch ($matches[2]) {
                    case 'day':
                        $data[$matches[1]] = sprintf(_n('%d day', '%d days', $matches[3], 'notificationx-pro'), number_format_i18n($matches[3]));
                        break;
                    case 'month':
                        $data[$matches[1]] = sprintf(_n('%d month', '%d months', $matches[3], 'notificationx-pro'), number_format_i18n($matches[3]));
                        break;
                    case 'year':
                        $data[$matches[1]] = sprintf(_n('%d year', '%d years', $matches[3], 'notificationx-pro'), number_format_i18n($matches[3]));
                        break;
                    default:
                        # code...
                        break;
                }
            }
        }
        return $data;
    }

    /**
     * Undocumented function
     *
     * @param array $options
     * @return array
     */
    public function content_fields_pro($fields) {
        $fields['content']['fields']['random_order'] = Rules::is('source', $this->id, true, $fields["content"]['fields']['random_order']);
        // $fields["utm_options"]    = //Rules::is('source', $this->id, true, $fields["utm_options"]);
        //     Rules::logicalRule([
        //         Rules::is('source', $this->id, true),
        //         Rules::is('source', $this->id, true),
        //     ], 'and', $fields["utm_options"]);
        return $fields;
    }

    /**
     * Undocumented function
     *
     * @param array $options
     * @return array
     */
    public function customize_fields_pro($fields) {
        $behaviour = &$fields['behaviour']['fields'];
        $behaviour['display_last'] = Rules::is('source', $this->id, true, $behaviour['display_last']);
        $behaviour['display_from'] = Rules::is('source', $this->id, true, $behaviour['display_from']);
        // $behaviour['link_open'] = Rules::is('source', $this->id, true, $behaviour['link_open']);
        return $fields;
    }
}

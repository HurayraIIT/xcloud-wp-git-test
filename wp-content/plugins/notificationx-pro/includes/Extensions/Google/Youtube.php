<?php
/**
 * YouTube Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Google;

use NotificationX\Admin\Settings;
use NotificationX\Core\GetData;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\GetInstance;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Google\Youtube as YoutubeFree;
use UsabilityDynamics\Settings as UsabilityDynamicsSettings;

/**
 * YouTube Extension
 * @method static YouTube get_instance($args = null)
 */
class YouTube extends YoutubeFree {

    private $api_key;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
        $this->api_key = Settings::get_instance()->get('settings.google_youtube_api_key');
    }

    public function init() {
        parent::init();
        // add_filter('nx_settings', [$this, 'nx_settings']);
    }

    public function admin_actions() {
        parent::admin_actions();
        add_action("nx_cron_update_data_{$this->id}", array($this, 'update_data'), 10, 2);
    }

    public function public_actions() {
        parent::public_actions();
        add_action("nx_frontend_keep_entry_{$this->id}", array($this, 'keep_entry'), 10, 4);
        add_filter("nx_notification_link_{$this->id}", [$this, 'youtube_link'], 10, 3);
    }

    public function youtube_link($link, $post, $entry) {
        if( ( !empty($entry['yt_channel_link']) || !empty( $entry['yt_video_link'] ) ) && !empty( $post['nx_subscribe_button_type'] ) && $post['nx_subscribe_button_type'] === 'nx_custom' ){
            $link =  !empty( $entry['yt_video_link'] ) ? $entry['yt_video_link'] : $entry['yt_channel_link'];
        }
        return $link;
    }

    public function init_settings_fields() {
        parent::init_settings_fields();
        // settings page
        add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
    }

    public function init_fields() {
        parent::init_fields();
        add_filter( 'nx_notification_template', [ $this, 'youtube_templates' ], 7 );
        add_filter('nx_content_fields', [$this, 'content_fields']);
        add_filter('nx_show_image_options', [$this, 'show_image_options']);
        add_filter('nx_customize_fields', [$this, 'customize_fields']);
        add_filter('nx_link_types', [$this, 'link_types']);
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types( $options ) {
        $options[] = [
            'value' => 'yt_video_link',
            'label' => __( 'Video Link', 'notificationx' ),
            'rules' => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::includes( 'themes', $this->video_themes ),
            ]),
        ];
        $options[] = [
            'value' => 'yt_channel_link',
            'label' => __( 'Channel Link', 'notificationx' ),
            'rules' => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::includes( 'themes', $this->channel_themes ),
            ]),
        ];

        return $options;
    }

    public function customize_fields($fields){
        $behaviour = &$fields["behaviour"]['fields'];
        $behaviour['display_from'] = Rules::is('source', $this->id, true, $behaviour['display_from']);
        $behaviour['display_last'] = Rules::is('source', $this->id, true, $behaviour['display_last']);
        return $fields;
    }
    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function youtube_templates( $template ) {
        $template['yt_third_label'] = [
            'name'     => 'yt_third_label',
            'type'     => 'text',
            'priority' => 27,
            'default'  => '',
            'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        ];
        $template['yt_fourth_label'] = [
            'name'     => 'yt_fourth_label',
            'type'     => 'text',
            'priority' => 37,
            'default'  => '',
            'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        ];
        $template['yt_fifth_label'] = [
            'name'     => 'yt_fifth_label',
            'type'     => 'text',
            'priority' => 47,
            'default'  => '',
            'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        ];
        // $template['yt_sixth_label'] = [
        //     'name'     => 'yt_sixth_label',
        //     'type'     => 'text',
        //     'priority' => 57,
        //     'default'  => '',
        //     'rules'    => Rules::includes('themes', "{$this->id}_channel-1"),
        // ];
        return $template;
    }

    /**
     * @param array $sections
     * @return array
     */
    public function api_integration_settings($sections) {
        $sections['google_youtube_settings_section'] = array(
            'name'     => 'google_youtube_settings_section',
            'type'     => 'section',
            'label'    => __('YouTube Settings', 'notificationx-pro'),
            'modules'  => 'modules_google_youtube',
            'priority' => 70,
            'rules'    => Rules::is('modules.modules_google_youtube', true),
            'fields'   => [
                // 'yt_connect' => array(
                //     'name'      => 'yt_connect',
                //     'type'      => 'button',
                //     'text'      => __('Connect your account', 'notificationx-pro'),
                //     'label'     => __('Connect with YouTube', 'notificationx-pro'),
                //     'className' => 'ga-btn connect-analytics',
                //     'href'      => 'https://accounts.google.com/o/oauth2/auth?client_id=928694219401-b9njpjh55ha3vgepku2269kas5kd9a5c.apps.googleusercontent.com&response_type=code&access_type=offline&approval_prompt=force&redirect_uri=' . urlencode("https://devapi.notificationx.com/youtube/") . '&scope=' . urlencode('https://www.googleapis.com/auth/youtube.readonly') . '&state=' . urlencode(admin_url('admin.php?page=nx-settings&tab=tab-api-integrations')),
                //     // 'rules'    => Rules::logicalRule([Rules::is('is_yt_connected', true, true), Rules::is('yt_disconnect', true)], 'or'),
                //     'rules'    => Rules::logicalRule([Rules::is('is_yt_connected', true, true), Rules::is('yt_own_app', false)], 'and'),
                //     // 'rules'    => Rules::is('is_yt_connected', true, true),
                // ),
                // 'yt_own_app' => array(
                //     'name'    => 'yt_own_app',
                //     'type'    => 'toggle',
                //     'default' => false,
                //     'label'   => __('Setup your Google App', 'notificationx-pro'),
                //     // 'link_text' => __('Setup Now', 'notificationx-pro'),
                //     'className' => 'ga-btn setup-google-app',
                //     // translators: %s: Google Analytics docs link.
                //     'help' => sprintf(__('By setting up your app, you will be disconnected from current account. See our <a target="_blank" rel="nofollow" href="%s">Creating Google App in Cloud</a> documentation for help', 'notificationx-pro'), 'https://notificationx.com/docs/google-analytics/'),
                //     // 'rules'    => Rules::logicalRule([Rules::is('is_yt_connected', true, true), Rules::is('yt_disconnect', true)], 'or'),
                //     'rules'    => Rules::is('is_yt_connected', true, true),
                // ),

                // 'yt_disconnect' => array(
                //     'name'      => 'yt_disconnect',
                //     'type'      => 'button',
                //     'label'     => __('Disconnect from google analytics', 'notificationx-pro'),
                //     'text'      => __('Logout from account', 'notificationx-pro'),
                //     'className' => 'ga-btn disconnect-analytics',
                //     'rules'     => Rules::is('is_yt_connected', true),
                //     'ajax'      => [
                //         'on'   => 'click',
                //         'api'  => '/notificationx/v1/api-connect',
                //         'data' => [
                //             'source'    => $this->id,
                //             'type'      => 'yt_disconnect',
                //         ],
                //         'trigger' => '@is_yt_connected:false',
                //         'hideSwal' => true,
                //     ],
                // ),
                // 'yt_cache_duration' => array(
                //     'name'        => 'yt_cache_duration',
                //     'type'        => 'number',
                //     'label'       => __('Cache Duration', 'notificationx-pro'),
                //     // 'default'     => $this->nx_app_min_cache_duration,
                //     // 'min'         => $this->pa_options['yt_app_type'] == 'nx_app' ? $this->nx_app_min_cache_duration : 1,
                //     'priority'    => 5,
                //     'description' => __('Minutes, scheduled duration for collect new data', 'notificationx-pro'),
                //     'rules'       => Rules::is('is_yt_connected', true),
                // ),

                // 'yt_redirect_uri' => array(
                //     'name'        => 'yt_redirect_uri',
                //     'type'        => 'text',
                //     'label'       => __('Redirect URI', 'notificationx-pro'),
                //     'className'   => 'ga-client-id ga-hidden',
                //     'default'     => admin_url('admin.php?page=nx-admin'),
                //     'readOnly'    => true,
                //     'help'        => __('Copy this and paste it in your google app redirect uri field', 'notificationx-pro'),
                //     'description' => __('Keep it in your google cloud project app redirect uri.', 'notificationx-pro'),
                //     'rules'       => Rules::logicalRule([Rules::is('yt_own_app', true), Rules::is('is_yt_connected', true, true)]),
                // ),
                // 'yt_client_id' => array(
                //     'name'        => 'yt_client_id',
                //     'type'        => 'text',
                //     'label'       => __('Client ID', 'notificationx-pro'),
                //     'className'   => 'ga-client-id ga-hidden',
                //     // translators: %1$s: Google API dashboard link, %2$s: Google Analytics docs link.
                //     'description' => sprintf(__('<a target="_blank" rel="nofollow" href="%1$s">Click here</a> to get Client ID by Creating a Project or you can follow our <a rel="nofollow" target="_blank" href="%2$s">documentation</a>.', 'notificationx-pro'), 'https://console.cloud.google.com/apis/dashboard', 'https://notificationx.com/docs/google-analytics/'),
                //     'rules'       => Rules::logicalRule([Rules::is('yt_own_app', true), Rules::is('is_yt_connected', true, true)]),
                // ),
                // 'yt_client_secret' => array(
                //     'name'        => 'yt_client_secret',
                //     'type'        => 'text',
                //     'label'       => __('Client Secret', 'notificationx-pro'),
                //     'className'   => 'ga-client-secret ga-hidden',
                //     // translators: %1$s: Google API dashboard link, %2$s: Google Analytics docs link.
                //     'description' => sprintf(__('<a target="_blank" rel="nofollow" href="%1$s">Click here</a> to get Client Secret by Creating a Project or you can follow our <a target="_blank" rel="nofollow" href="%2$s">documentation</a>.', 'notificationx-pro'), 'https://console.cloud.google.com/apis/dashboard', 'https://notificationx.com/docs/google-analytics/'),
                //     'rules'       => Rules::logicalRule([Rules::is('yt_own_app', true), Rules::is('is_yt_connected', true, true)]),
                // ),
                // 'yt_user_app_connect' => array(
                //     'name'      => 'yt_user_app_connect',
                //     'type'      => 'button',
                //     'label'     => ' ',
                //     'className' => 'ga-btn connect-user-app',
                //     'text'      => [
                //         'normal'  => __('Connect your account', 'notificationx-pro'),
                //         'saved'   => __('Connected', 'notificationx-pro'),
                //         'loading' => __('Connecting...', 'notificationx-pro')
                //     ],
                //     'ajax' => [
                //         'on'   => 'click',
                //         'api'  => '/notificationx/v1/api-connect',
                //         'data' => [
                //             'source'           => $this->id,
                //             'type'             => 'user-app',
                //             'yt_redirect_uri'  => '@yt_redirect_uri',
                //             'yt_client_id'     => '@yt_client_id',
                //             'yt_client_secret' => '@yt_client_secret',
                //         ],
                //         'trigger' => '@is_yt_connected:true',
                //     ],
                //     'rules' => Rules::logicalRule([Rules::is('yt_own_app', true), Rules::is('is_yt_connected', true, true)]),
                // ),
                // 'yt_save_selected_profile' => array(
                //     'name'      => 'yt_save_selected_profile',
                //     'type'      => 'button',
                //     // 'label'     => __('Save', 'notificationx-pro'),
                //     'className' => 'ga-btn connect-user-app',
                //     'text'      => [
                //         'normal'  => __('Save', 'notificationx-pro'),
                //         'saved'   => __('Saved', 'notificationx-pro'),
                //         'loading' => __('Saving...', 'notificationx-pro')
                //     ],
                //     'ajax' => [
                //         'on'   => 'click',
                //         'api'  => '/notificationx/v1/api-connect',
                //         'data' => [
                //             'source'            => $this->id,
                //             'type'              => 'save',
                //             'yt_profile'        => '@yt_profile',
                //             'yt_cache_duration' => '@yt_cache_duration',
                //         ],
                //         'swal' => [
                //             'text'  => __('Changes Saved!', 'notificationx-pro'),
                //             'icon'  => 'success',
                //         ],
                //     ],
                //     'rules' => Rules::is('is_yt_connected', true),
                // ),

                // 'is_yt_connected' => array(
                //     'name'      => 'is_yt_connected',
                //     'type'      => 'hidden',
                //     'default'   => $this->is_yt_connected(),
                // ),

                'google_youtube_cache_duration' => [
                    'name'        => 'google_youtube_cache_duration',
                    'type'        => 'number',
                    'label'       => __('Cache Duration', 'notificationx-pro'),
                    'default'     => 6 * 60,
                    'min'         => 30,
                    'description' => __('Minutes, scheduled duration for collect new data. Estimated cost per month around $25 for every 30 minute.', 'notificationx-pro'),
                ],
                'google_youtube_api_key' => array(
                    'name'  => 'google_youtube_api_key',
                    'type'  => 'text',
                    'text'  => __('API Key', 'notificationx-pro'),
                    'label' => __('API Key', 'notificationx-pro'),
                    'description' => sprintf('%s <a href="%s" target="_blank">%s</a>.',
                        __('To get an API key, check out', 'notificationx-pro'),
                        'https://notificationx.com/docs/collect-api-key-from-google-console',
                        __(' this doc', 'notificationx-pro')
                    ),
                ),
                [
                    'name' => 'google_youtube_connect',
                    // 'label' => 'Connect Button',
                    'type' => 'button',
                    'default' => false,
                    'text' => [
                        'normal'  => __('Validate', 'notificationx-pro'),
                        'saved'   => __('Refresh', 'notificationx-pro'),
                        'loading' => __('Validating...', 'notificationx-pro')
                    ],
                    'ajax' => [
                        'on' => 'click',
                        'api' => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'                        => $this->id,
                            'google_youtube_cache_duration' => '@google_youtube_cache_duration',
                            'google_youtube_api_key'        => '@google_youtube_api_key',
                        ],
                    ]
                ],
            ],
        );
        return $sections;
    }

    /**
     * Init Google client with auth code
     * @return void
     */
    public function init_google_client() {
        if (isset($_GET['yt_code']) && isset($_GET['token_info']) && 'nx-settings' == $_GET['page']) {

            $options               = [];
            $options['token_info'] = $_GET['token_info'];
            $options['auth_code']  = $_GET['yt_code'];
            // if ($nx_google_client->getRedirectUri() == admin_url('admin.php?page=nx-admin')) {
            //     $options['yt_app_type'] = 'user_app';
            // } else {
                $options['yt_app_type'] = 'nx_app';
            // }

            Settings::get_instance()->set("settings.yt_settings", $options);

            wp_redirect(admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_analytics_settings_section'));
        }

        $settings = Settings::get_instance()->get("settings.yt_settings");
        if (!empty($settings) && empty($settings['channels'])) {
            try {
                $this->get_channels();
            } catch (\Exception $e) {
                // self::$error_message = $e->getMessage();
                Helper::write_log(['error' => 'Set Profile failed. Details: ' . $e->getMessage()]);
            }
        }
    }

    public function nx_settings($settings){
        if(isset($settings['is_yt_connected'])){
            unset($settings['is_yt_connected']);
        }
        return $settings;
    }

    /**
     * Set page analytics options
     * @return Google_Client
     */
    public function is_yt_connected() {
        $settings = Settings::get_instance()->get("settings.yt_settings.token_info.access_token");

        if (empty($settings)) {
            return false;
        }
        return true;
    }

    /**
     * Get data for WooCommerce Extension.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_fields($fields) {
        $settings = Settings::get_instance()->get('settings.yt_settings');
        $content_fields = &$fields['content']['fields'];

        $options = array_merge([
            'custom' => __('Custom', 'notificationx'),
        ], isset($settings['channels']) ? $settings['channels'] : []);

        // $content_fields['youtube_channels'] = [
        //     'label'       => __('Channels', 'notificationx'),
        //     'placeholder' => __('Select a Channel', 'notificationx'),
        //     'name'        => 'youtube_channels',
        //     'type'        => 'select',
        //     'priority'    => 79,
        //     // 'default'  => 'channel',
        //     'options'  => GlobalFields::get_instance()->normalize_fields($options),
        //     'rules'    => ['is', 'source', $this->id],
        // ];

        $content_fields['youtube_channel_id'] = [
            'label'    => __('Channel ID or @Handle', 'notificationx'),
            'help'     => 'Example: https://youtube.com/@Handle',
            'name'     => 'youtube_channel_id',
            'type'     => 'text',
            'priority' => 80,
            'rules'    => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::includes( 'themes', $this->channel_themes ),
            ]),
        ];
        $content_fields['youtube_video_id'] = [
            'label'    => __('Video ID', 'notificationx'),
            'name'     => 'youtube_video_id',
            'type'     => 'text',
            'priority' => 89,
            'help'     => 'Example: https://www.youtube.com/watch?v=<span>yMEgGHbm3k0</span>',
            'rules'    => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::includes( 'themes', $this->video_themes ),
            ]),
        ];
        return $fields;
    }

    public function connect($params) {
        if (!empty($params['google_youtube_api_key'])) {
            Settings::get_instance()->set('settings.google_youtube_cache_duration', $params['google_youtube_cache_duration'] ? $params['google_youtube_cache_duration'] : 30);
            Settings::get_instance()->set('settings.google_youtube_api_key', $params['google_youtube_api_key']);
            $api_key = $params['google_youtube_api_key'];
            if (!empty($api_key)) {
                $query = http_build_query( [
                    'part'        => 'snippet,contentDetails,statistics',
                    'forUsername' => 'GoogleDevelopers',
                    'key'         => $api_key,
                ] );

                $channel_data = Helper::remote_get( $this->api_base . 'channels?' . $query );

                if(isset($channel_data->kind) && "youtube#channelListResponse" === $channel_data->kind){
                    return array(
                        'status' => 'success',
                    );
                }

            }
        }
        if (isset($params['type']) && $params['type'] == 'yt_disconnect') {
            $settings = Settings::get_instance()->get('settings');
            unset($settings['yt_settings']);
            unset($settings['is_yt_connected']);
            Settings::get_instance()->set("settings", $settings);
            return array(
                'status'          => 'success',
                'context' => [
                    'yt_settings' => false,
                ]
            );
        }
        return array(
            'status' => 'error',
            'message' => __('Please insert a valid API key.', 'notificationx-pro')
        );
    }


    public function saved_post($post, $data, $nx_id) {
        $this->update_data($nx_id, $data);
    }

    public function update_data($nx_id, $data = array()) {
        if (empty($nx_id)) {
            return;
        }
        if (empty($data)) {
            $data = PostType::get_instance()->get_post($nx_id);
        }

        if(empty($data['youtube_channel_id']) && empty($data['youtube_video_id'])){
            return;
        }

        if('youtube_channel-1' === $data['themes'] || 'youtube_channel-2' === $data['themes']){
            $channel_id       = $data['youtube_channel_id'];
            $youtube_type     = 'channels';
        }
        else{
            $channel_id       = $data['youtube_video_id'];
            $youtube_type     = 'videos';
        }

        // if('custom' === $channel_id){
        //     $channel_id = $data['youtube_id'];
        // }

        $youtube_stats = $this->get_stats($channel_id, $youtube_type);

        // removing old notifications.
        $this->delete_notification(null, $nx_id);
        $entries = [];
        foreach ($youtube_stats as $stats) {
            // $review = array_merge($review, $plugin_data);
            $entries[] = [
                'nx_id'      => $nx_id,
                'source'     => $this->id,
                'entry_key'  => $stats['kind'] . '_' . $stats['id'],
                'data'       => $stats,
            ];
        }
        $this->update_notifications($entries);
        return $entries;
    }

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
     * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($data = array()) {
        if (!is_null($data['nx_id'])) {
            return $this->update_data($data['nx_id'], $data);
        }
        return [];
    }

    /**
     * Undocumented function
     *
     * @param int $nx_id
     * @param array $data
     * @return array
     */
    public function get_stats($channel_id, $youtube_type){
        $result = [];
        // $api_key = Settings::get_instance()->get('settings.yt_settings');
        // $access_token = $api_key['token_info']['access_token'];
        $api_key = Settings::get_instance()->get('settings.google_youtube_api_key');
        if(empty($api_key)){
            return $result;
        }

        $args = [
            'headers' => [
                // 'Authorization' => 'Bearer ' . $access_token,
                'Accept' => 'application/json',
            ]
        ];

        $query = [
            'part' => 'id,snippet,contentDetails,statistics',
            'key'  => $api_key,
        ];

        if(strpos($channel_id, '@') === 0){
            $query['forUsername'] = ltrim($channel_id, '@');
        }
        else{
            $query['id'] = $channel_id;
        }

        $transient_key = "nx_{$this->id}" . md5(http_build_query($query));
        $place_data    = get_transient($transient_key);

        if(empty($place_data)){
            $place_data =  $this->get_channel_data_by_channel_id( $transient_key, $youtube_type, $query, $args );
        }
        if( empty($place_data['pageInfo']['totalResult']) && empty($place_data['items']) && strpos($channel_id, '@') === 0 ){
            $search_query = [
                'part'       => 'id',
                'key'        => $api_key,
                'type'       => 'channel',
                'maxResults' => 1,
                'q'          => $channel_id,
            ];
            $place_data = Helper::remote_get( $this->api_base . 'search' . '?' . http_build_query($search_query), $args, false, true );
            if( !empty( $place_data['items']['0']['id']['channelId'] ) ) {
                $query['id'] = $place_data['items']['0']['id']['channelId'];
                unset( $query['forUsername'] );
                $place_data = $this->get_channel_data_by_channel_id( $transient_key, $youtube_type, $query, $args );
            }
        }

        if(!empty($place_data['items']) && is_array($place_data['items'])){
            foreach ($place_data['items'] as $item) {
                $item = new UsabilityDynamicsSettings(['data' => $item]);
                $result[] = [
                    'yt_channel_link'  => $this->getChannelUrl($item),
                    'yt_video_link'    => $this->getVideoUrlFromItem($item),
                    'kind'             => $item->get('kind'),
                    'etag'             => $item->get('etag'),
                    'id'               => $item->get('id'),
                    'yt_channel_title' => $item->get('snippet.title'),
                    'description'      => $item->get('snippet.description'),
                    'publishedAt'      => $item->get('snippet.publishedAt'),
                    'image'            => $item->get('snippet.thumbnails.default.url', ''),
                    '_yt_views'        => $item->get('statistics.viewCount', 0),
                    '_yt_subscribers'  => $item->get('statistics.subscriberCount', 0),
                    '_yt_videos'       => $item->get('statistics.videoCount', 0),
                    '_yt_likes'        => $item->get('statistics.likeCount', 0),
                    '_yt_comments'     => $item->get('statistics.commentCount', 0),
                    '_yt_favorites'    => $item->get('statistics.favoriteCount', 0),
                ];
            }
        }
        return $result;
    }

    public function get_channel_data_by_channel_id( $transient_key, $youtube_type, $query, $args )
    {
        $cache_duration = Settings::get_instance()->get('settings.google_youtube_cache_duration', 30) * MINUTE_IN_SECONDS;
        $place_data = Helper::remote_get( $this->api_base . $youtube_type . '?' . http_build_query($query), $args, false, true );
        set_transient($transient_key, $place_data, $cache_duration);
        return $place_data;
    }

    /**
     * A PHP function to get the channel URL from the YouTube Data API's response
     *
     * @param UsabilityDynamicsSettings $item
     * @return string|null
     */
    public function getChannelUrl($item) {
        // Check the type of the item array
        if ($item->get('kind') == 'youtube#channel') {
            // The item is a channel resource
            // Return the channel link with the custom URL or the channel ID
            return 'https://www.youtube.com/' . $item->get('snippet.customUrl', 'channel/' . $item->get('id'));
        } elseif ($item->get('kind') == 'youtube#video') {
            // The item is a video resource
            // Return the channel link with the channel ID
            return 'https://www.youtube.com/channel/' . $item->get('snippet.channelId');
        } else {
            // The item is neither a channel nor a video resource
            // Return null or an error message
            return null;
        }
    }

    /**
     * A PHP function to get the video URL from an item array using the YouTube Data API
     *
     * @param UsabilityDynamicsSettings $item
     * @return string|null
     */
    public function getVideoUrlFromItem($item) {
        // Check the type of the item array
        if ($item->get('kind') == 'youtube#video') {
            // The item is a video resource
            // Return the video link with the video ID
            return 'https://www.youtube.com/watch?v=' . $item->get('id');
        } else {
            // The item is not a video resource
            // Return null or an error message
            return null;
        }
    }


    public function get_channels(){
        $result = [];
        $options = Settings::get_instance()->get('settings.yt_settings');
        $access_token = $options['token_info']['access_token'];

        $args = [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Accept'        => 'application/json',
            ]
        ];

        $query = [
            'part' => 'snippet',
            'mine' => true,
        ];

        $transient_key = "nx_{$this->id}" . md5(http_build_query([$query, $args]));
        $place_data    = get_transient($transient_key);

        if(empty($place_data)){
            $place_data = Helper::remote_get( $this->api_base . 'channels?' . http_build_query($query), $args, false, true );
            set_transient($transient_key, $place_data, HOUR_IN_SECONDS);
        }

        if(!empty($place_data['items']) && is_array($place_data['items'])){
            foreach ($place_data['items'] as $item) {
                $item = new UsabilityDynamicsSettings(['data' => $item]);
                $result[$item->get('id')] = $item->get('snippet.title') . " (" . $item->get('snippet.customUrl') . ")";
            }
            $options['channels'] = $result;
            Settings::get_instance()->set("settings.yt_settings", $options);
        }
        return $result;
    }


    public function preview_settings($settings){
        if('yt_channel_link' === $settings['link_type'] && !empty($settings['link_button'])){
            $settings['nx_subscribe_button_type'] = 'nx_custom';
            $settings['link_button_text_channel'] = 'Subscribe Now';
        }

        return $settings;
    }

    public function preview_entry($entry, $settings){
        if($settings['show_notification_image'] === "yt_thumbnail" ){
            $entry["image_data"] = [
                "url"     => "https://yt3.googleusercontent.com/ytc/AOPolaQCL3t4yyoMQASsr-TgYCItiONVyHEYKATxdRCtSQ=s176-c-k-c0x00ffffff-no-rj",
                "alt"     => "",
                "classes" => "greview_icon"
            ];
        }

        $entry = array_merge($entry, [
            'yt_channel_title' => "WPDeveloper",
            'description'      => "WPDeveloper is a software company that serving 5 Million users from 180+ countries around the world",
            'publishedAt'      => "2007-08-23T00:34:43Z",
            '_yt_subscribers'  => "8970",
            '_yt_videos'       => "659",
            '_yt_views'        => "2400000",
            '_yt_favorites'    => 10,
            '_yt_comments'     => "98",
            '_yt_likes'        => "525",
            'yt_channel_link'  => "https://www.youtube.com/@WPDeveloper",
            'yt_video_link'    => "https://www.youtube.com/watch?v=fzssXUfUFGY",
        ]);
        return $entry;
    }

    public function show_image_options( $options ) {
        $options['yt_thumbnail'] = [
            'value' => 'yt_thumbnail',
            'label' => __( 'Thumbnail', 'notificationx-pro' ),
            'rules' => ['is', 'source', $this->id],
        ];
        return $options;
    }

    /**
     * Image action callback
     * @param array $image_data
     * @param array $data
     * @param stdClass $settings
     * @return array
     */
    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image'] && $settings['show_notification_image'] === 'yt_thumbnail') {
            $image_data['url'] = isset($data['image']) ? $data['image'] : '';
            $image_data['alt'] = isset($data['yt_channel_title']) ? $data['yt_channel_title'] : '';
        }
        return $image_data;
    }

    public function keep_entry($return, $entry, $post, $params){
        if(isset($entry['id'])){
            $return['id'] = $entry['id'];
        }
        if(isset($entry['_yt_subscribers'])){
            $return['yt_subscribers'] =  Helper::nice_number($entry['_yt_subscribers']);
        }
        if(isset($entry['yt_video_link'])){
            $return['yt_video_link'] =  $entry['yt_video_link'];
        }
        return $return;
    }

    public function source_error_message($messages) {
        if ( empty( $this->api_key ) ) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_youtube_settings_section');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a>.',
                    __( 'You have to setup your API Key for', 'notificationx-pro' ),
                    $url,
                    __('Youtube', 'notificationx-pro')
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

}
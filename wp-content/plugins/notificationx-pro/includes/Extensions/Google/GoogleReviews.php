<?php
/**
 * GoogleReviews Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Google;

use NotificationX\Admin\Cron;
use NotificationX\Admin\Entries;
use NotificationX\Admin\Settings;
use NotificationX\Core\Helper;
use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\Extensions\Google\GoogleReviews as GoogleReviewsFree;

/**
 * GoogleReviews Extension
 */
class GoogleReviews extends GoogleReviewsFree {
    public $api_base = 'https://maps.googleapis.com/maps/api/place/';
    public $cron_schedule = 'nx_google_review_cache_duration';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct(){
        parent::__construct();
    }



    public function init_fields() {
        parent::init_fields();
        add_filter('nx_content_fields', [$this, 'content_fields_pro'], 20);
        // add_filter('nx_customize_fields', [$this, 'customize_fields_pro'], 20);
        add_filter('nx_link_types', [$this, 'link_types'], 22);
        add_filter('nx_display_fields', [$this, 'display_fields']);
        add_filter('nx_customize_fields', [$this, 'customize_fields']);
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
        add_filter("nx_notification_link_{$this->id}", [$this, 'product_link'], 10, 3);
        add_filter("nx_entry_display_{$this->id}", '__return_false');
        add_filter("nx_filtered_entry_{$this->id}", array($this, 'conversion_data'), 11, 2);

        // Show only single entry if total-rated theme.
        // add_filter( 'nx_frontend_get_entries', [$this, 'total_rated_theme'], 10, 3);
    }

    public function admin_actions() {
        parent::admin_actions();
        add_action("nx_cron_update_data_{$this->id}", array($this, 'update_data'), 10, 2);
        add_filter("nx_is_map_image_{$this->id}", [$this, 'is_map_image'], 10, 2);
    }

    /**
     * Get data for WooCommerce Extension.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function content_fields_pro($fields) {
        $content_fields = &$fields['content']['fields'];

        $content_fields['google_reviews_place_data'] = [
            'label'    => __('Place Name', 'notificationx'),
            'name'     => 'google_reviews_place_data',
            'type'     => 'select-async',
            'priority' => 10,
            'options'  => GlobalFields::get_instance()->normalize_fields( [
                'custom' => __('Custom', 'notificationx'),
            ] ),
            'rules'  => ['is', 'source', $this->id],
            'ajax'   => [
                'api'  => "/notificationx/v1/get-data",
                'data' => [
                    'type'           => "@type",
                    'source'         => "@source",
                    'selected_place' => "@google_reviews_place_data",
                ],
                'target' => "google_reviews_place_data",
                'rules'  => Rules::is( 'source', $this->id ),
            ],
        ];

        $content_fields['google_reviews_custom_place_id'] = array(
            'name'     => 'google_reviews_custom_place_id',
            'type'     => 'text',
            'label'    => __( 'Custom Place ID', 'notificationx' ),
            'default'  => '',
            'priority' => 20,
            'rules'    => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::is( 'google_reviews_place_data.value', 'custom' ),
            ]),
        );

        $content_fields['google_reviews_sort'] = [
            'label'    => __('Sort By', 'notificationx'),
            'name'     => 'google_reviews_sort',
            'type'     => 'select',
            'priority' => 30,
            'default'  => 'most_relevant',
            'options'  => GlobalFields::get_instance()->normalize_fields( [
                'most_relevant' => __('Most Relevant', 'notificationx'),
                'newest'        => __('Newest', 'notificationx'),
            ] ),
            'rules'    => Rules::logicalRule([
                Rules::is( 'source', $this->id ),
                Rules::is( 'themes', 'google_reviews_total-rated', true ),
            ]),
        ];

        $content_fields['content_trim_length'] = Rules::logicalRule([
            Rules::includes('themes', [
                "{$this->id}_review-comment",
                "{$this->id}_review-comment-2",
                "{$this->id}_review-comment-3",
            ]),
            Rules::is( 'notification-template.third_param', 'tag_place_review' ),
        ], 'or', $content_fields['content_trim_length']);

        // $content_fields['wp_reviews_slug'] = [
        //     'label'    => __('Slug', 'notificationx'),
        //     'name'     => 'wp_reviews_slug',
        //     'type'     => 'text',
        //     'priority' => 80,
        //     'rules'  => ['is', 'source', $this->id]
        // ];
        // print_r($fields);die;
        return $fields;
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function display_fields($fields) {
        $show_image = &$fields['image-section']['fields']['show_notification_image'];
        $show_image = Rules::includes('source', $this->id, false, $show_image);
        // $show_image['options'] = Rules::includes('source', $this->id, false, $show_image);
        $show_image['options'] = GlobalFields::get_instance()->normalize_fields([
            'greview_icon'      => [
                'value' => 'greview_icon',
                'label' => _x('Icon', 'Google Review', 'notificationx'),
                'rules' => Rules::is('source', $this->id),
            ],
            'greview_avatar'    => [
                'value' => 'greview_avatar',
                'label' => _x('Avatar', 'Google Review', 'notificationx'),
                'rules' => Rules::logicalRule([
                    Rules::is('source', $this->id),
                    Rules::is('themes', 'google_reviews_total-rated', true),
                ]),
            ],
            'greview_map_image' => [
                'value' => 'greview_map_image',
                'label' => _x('Map Image', 'Google Review', 'notificationx'),
                'rules' => Rules::logicalRule([
                    Rules::is('source', $this->id),
                    Rules::is('themes', "{$this->id}_maps_theme"),
                ])
            ],
        ], null, null, $show_image['options']);

        $fields['image-section']['fields']['default_avatar']['options'][] = array(
            'value' => 'google-g-icon.png',
            'label' => __('Google G Icon', 'notificationx'),
            'icon'  => NOTIFICATIONX_PUBLIC_URL . 'image/icons/google-g-icon.png',
            'rules' => Rules::is('source', $this->id),
        );

        return $fields;
    }

    public function customize_fields($fields){
        $fields["behaviour"]['fields']['display_from'] = Rules::is('source', $this->id, true, $fields["behaviour"]['fields']['display_from']);
        $fields["behaviour"]['fields']['display_last'] = Rules::is('source', $this->id, true, $fields["behaviour"]['fields']['display_last']);
        return $fields;
    }

    /**
     * Adds option to Link Type field in Content tab.
     *
     * @param array $options
     * @return array
     */
    public function link_types($options) {
        $options = GlobalFields::get_instance()->normalize_fields([
            'map_page' => __('Map Page', 'notificationx'),
        ], 'source', $this->id, $options);
        $options['review_page'] = Rules::includes('source', $this->id, true, $options['review_page']);
        return $options;
    }

    /**
     * This method adds google analytics settings section in admin settings
     * @param array $sections
     * @return array
     */
    public function api_integration_settings($sections) {
        $sections['google_reviews_settings_section'] = array(
            'name'     => 'google_reviews_settings_section',
            'type'     => 'section',
            'label'    => __('Google Reviews Settings', 'notificationx-pro'),
            'modules'  => 'modules_google_reviews',
            'priority' => 80,
            'rules'    => Rules::is('modules.modules_google_reviews', true),
            'fields'   => [
                'google_review_cache_duration' => [
                    'name' => 'google_review_cache_duration',
                    'type'        => 'number',
                    'label'       => __('Cache Duration', 'notificationx-pro'),
                    'default'     => 30,
                    'min'     => 30,
                    'description' => __('Minutes, scheduled duration for collecting new data. Estimated cost per month around $25 for every 30 minutes.', 'notificationx-pro'),
                ],
                'google_review_api_key' => array(
                    'name'  => 'google_review_api_key',
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
                    'name' => 'google_review_connect',
                    // 'label' => 'Connect Button',
                    'type' => 'button',
                    'default' => false,
                    'text' => [
                        'normal' => __('Validate', 'notificationx-pro'),
                        'saved' => __('Refresh', 'notificationx-pro'),
                        'loading' => __('Validating...', 'notificationx-pro')
                    ],
                    'ajax' => [
                        'on' => 'click',
                        'api' => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'                       => $this->id,
                            'google_review_cache_duration' => '@google_review_cache_duration',
                            'google_review_api_key'        => '@google_review_api_key',
                        ],
                    ]
                ],
            ],
        );
        return $sections;
    }

    public function source_error_message($messages) {
        $key = Settings::get_instance()->get('settings.google_review_api_key');
        if ( empty( $key ) ) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#google_reviews_settings_section');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a>.',
                    __( 'You have to setup your API Key for ', 'notificationx-pro' ),
                    $url,
                    __(' Google Review', 'notificationx-pro')
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }


    public function saved_post($post, $data, $nx_id) {
        // $this->delete_notification(null, $nx_id);
        $this->update_data($nx_id, $data);
        $this->delete_UUID();
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

    protected function get_UUID_key(){
        return "{$this->id}-autocomplete-uuid";
    }

    protected function get_UUID(){
        if(!empty($_COOKIE[$this->get_UUID_key()])) {
            return $_COOKIE[$this->get_UUID_key()];
        }
        return null;
    }

    protected function set_UUID(){
        if($this->get_UUID()){
            return $this->get_UUID();
        }

        $uuid36 = wp_generate_uuid4();
        try {
            setcookie($this->get_UUID_key(), $uuid36, time() + HOUR_IN_SECONDS);
        } catch (\Exception $e) {
            error_log("NX {$this->id}:", print_r($e, true));
        }
        return $uuid36;
    }

    protected function delete_UUID(){
        unset($_COOKIE[$this->get_UUID_key()]);
        setcookie($this->get_UUID_key(), null, -1);
        return true;
    }

    /**
     * Lists available tags in the selected form.
     *
     * @return json
     */
    public function restResponse($args) {
        $return = [];
        // if(!empty($args['selected_place'])){
        //     $return[] = $args['selected_place'];
        // }
        if(!empty($args['inputValue'])){
            $key   = Settings::get_instance()->get('settings.google_review_api_key');
            $query = http_build_query( [
                'input'        => $args['inputValue'],
                'key'          => $key,
                'sessiontoken' => $this->set_UUID(),
            ] );
            $transient_key = "nx_{$this->id}" . md5($query);
            $place_data = get_transient($transient_key);

            if(empty($place_data)){
                $place_data = Helper::remote_get( $this->api_base . 'autocomplete/json?' . $query );
                $cache_duration = Settings::get_instance()->get('settings.google_review_cache_duration', 30);
                set_transient($transient_key, $place_data, $cache_duration * MINUTE_IN_SECONDS);
            }
            if(isset($place_data->status) && 'OK' === $place_data->status && !empty($place_data->predictions) && is_array($place_data->predictions)){
                foreach ($place_data->predictions as $key => $place) {
                    $return[] = [
                        'label'   => $place->description,
                        'name'    => $place->structured_formatting->main_text,
                        'address' => isset($place->structured_formatting->secondary_text) ? $place->structured_formatting->secondary_text : '',
                        'value'   => $place->place_id,
                    ];
                }
            }
            if(!empty($place_data->error_message)){
                error_log("NX {$this->id}:", print_r($place_data->error_message, true));
            }
            return $return;
        }
        return $return;
    }

    /**
     * Update post analytics data if required
     * @hooked in 'wp'
     * @return void
     */
    public function update_data($nx_id, $settings = []) {
        if(!empty($settings['google_reviews_place_data']['value'])){
            $place_id       = $settings['google_reviews_place_data']['value'];
            $is_total_rated = 'tag_rated' === $settings['notification-template']['first_param'];
            $api_key        = Settings::get_instance()->get('settings.google_review_api_key');
            $sort           = $settings['google_reviews_sort'];

            if('custom' === $place_id){
                $place_id = $settings['google_reviews_custom_place_id'];
            }
            if(empty($api_key)){
                return;
            }

            $fields = [
                'place_id',
                'name',
                'rating',
                'user_ratings_total',
                'formatted_address',
                'geometry',
                'icon',
                'url',
                'website',
            ];
            if (!$is_total_rated) {
                $fields[] = 'reviews';
            }

            $query = http_build_query( [
                'place_id'     => $place_id,
                'reviews_sort' => $sort, // most_relevant|newest
                'fields'       => implode(',', $fields),
                'key'          => $api_key,
                'sessiontoken' => $this->get_UUID(),
                // 'reviews_no_translations' => true,
            ] );
            $transient_key = "nx_{$this->id}" . md5($query);
            $place_data    = get_transient($transient_key);

            if(empty($place_data)){
                $place_data = Helper::remote_get( $this->api_base . 'details/json?' . $query );
                $cache_duration        = Settings::get_instance()->get('settings.google_review_cache_duration', 30);
                set_transient($transient_key, $place_data, $cache_duration * MINUTE_IN_SECONDS);
            }


            if(isset($place_data->status) && 'OK' === $place_data->status){
                $this->delete_notification(null, $nx_id);

                if($is_total_rated){
                    $review = (array) $place_data->result;

                    $review['timestamp']   = time();
                    $review['place_name'] = $review['name'];
                    $review['rated']       = $review['user_ratings_total'];
                    $review['lat']         = $review['geometry']->location->lat;
                    $review['lon']         = $review['geometry']->location->lng;

                    unset($review['time']);
                    unset($review['geometry']);
                    unset($review['user_ratings_total']);

                    $entries[] = [
                        'nx_id'     => $nx_id,
                        'source'    => $this->id,
                        'entry_key' => $review['place_id'],
                        'data'      => $review,
                    ];
                }
                else if(!empty($place_data->result->reviews) && is_array($place_data->result->reviews)){
                    $entries        = [];
                    $reviews        = $place_data->result->reviews;
                    // $existing_entry = Entries::get_instance()->get_entries((int) $nx_id, "entry_key");
                    // $existing_entry = array_column($existing_entry, 'entry_key');
                    unset($place_data->result->reviews);

                    foreach ($reviews as $review) {
                        $review    = array_merge((array) $place_data->result, (array) $review);
                        $entry_key = md5($review['place_id'] . $review['author_name']);

                        $review['timestamp']    = $review['time'];
                        $review['place_name']   = $review['name'];
                        $review['username']     = $review['author_name'];
                        $review['place_review'] = $review['text'];
                        $review['lat']          = $review['geometry']->location->lat;
                        $review['lon']          = $review['geometry']->location->lng;

                        unset($review['time']);
                        unset($review['text']);
                        unset($review['geometry']);
                        unset($review['relative_time_description']);

                        $entries[] = [
                            'nx_id'      => $nx_id,
                            'source'     => $this->id,
                            'entry_key'  => $entry_key,
                            'data'       => $review,
                        ];
                    }
                }
                $this->update_notifications($entries);
            }
        }
    }

    public function product_link($link, $post, $entry) {
        if(isset( $post['link_type'], $entry['url'] ) && $post['link_type'] === 'map_page' ){
            $link = $entry['url'];
        }
        return $link;
    }

    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image']) {
            $image_url = '';
            switch ($settings['show_notification_image']) {
                case 'greview_icon':
                    if (isset($data['icon'])) {
                        $image_url = $data['icon'];
                    }
                    break;
                case 'greview_avatar':
                    if (isset($data['profile_photo_url'])) {
                        $image_url = $data['profile_photo_url'];
                    }
                    break;
            }
            $image_data['url'] = $image_url;
        }

        $image_data['alt'] = isset($data['author_name']) ? $data['author_name'] : '';

        return $image_data;
    }

    public function is_map_image($is_map_image, $settings){
        return $settings['show_notification_image'] === 'greview_map_image' ? true : $is_map_image;
    }

    // @todo frontend
    public function conversion_data($saved_data, $settings) {
        if (!empty($saved_data['place_review'])) {
            $trim_length = 100;
            if ($settings['themes'] == "{$this->id}_review-comment-2" || $settings['themes'] == "{$this->id}_review-comment-3") {
                $trim_length = 80;
            }
            $nx_trimmed_length = apply_filters('nx_text_trim_length', $trim_length, $settings);
            $review_content = $saved_data['place_review'];
            if (strlen($review_content) > $nx_trimmed_length) {
                $review_content = substr($review_content, 0, $nx_trimmed_length) . '...';
            }
            if ($settings['themes'] == "{$this->id}_review-comment-2") {
                $review_content = '" ' . $review_content . ' "';
            }
            $saved_data['place_review'] = $review_content;
        }

        return $saved_data;
    }

    public function connect($params) {
        if (!empty($params['google_review_api_key'])) {
            Settings::get_instance()->set('settings.google_review_cache_duration', $params['google_review_cache_duration'] ? $params['google_review_cache_duration'] : 30);
            Settings::get_instance()->set('settings.google_review_api_key', $params['google_review_api_key']);
            $api_key = $params['google_review_api_key'];
            if (!empty($api_key)) {
                $api_key        = Settings::get_instance()->get('settings.google_review_api_key');

                $query = http_build_query( [
                    'place_id'     => 'ChIJgUbEo8cfqokR5lP9_Wh_DaM',
                    'fields'       => 'name',
                    'key'          => $api_key,
                ] );

                $transient_key = "nx_{$this->id}" . md5($query);
                $place_data    = get_transient($transient_key);

                if(empty($place_data)){
                    $place_data = Helper::remote_get( $this->api_base . 'details/json?' . $query );
                    set_transient($transient_key, $place_data, MINUTE_IN_SECONDS);
                }
                // $place_data = Helper::remote_get( $this->api_base . 'details/json?' . $query );

                if(isset($place_data->status) && "OK" === $place_data->status){
                    return array(
                        'status' => 'success',
                    );
                }
                else if(isset($place_data->status) && "REQUEST_DENIED" === $place_data->status){
                    $error_message = $place_data->error_message;
                    if(strpos($error_message, 'You must enable Billing on the Google Cloud Project') === 0){
                        $error_message = __(
                            "You must enable Billing on the Google Cloud Project. Please check our doc for more info."
                            , 'notificationx-pro'
                        );
                    }
                    return array(
                        'status' => 'error',
                        'message' => $error_message,
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

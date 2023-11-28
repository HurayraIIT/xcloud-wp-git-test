<?php

/**
 * Maps Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Feature;

use NotificationX\Admin\Settings;
use NotificationX\Core\Database;
use NotificationX\Core\Helper;
use NotificationX\Core\PostType;
use NotificationX\Core\Rules;
use NotificationX\Extensions\ExtensionFactory as ExtensionFactoryFree;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationXPro\Extensions\ExtensionFactory;

/**
 * Maps Extension
 * @method static Maps get_instance($args = null)
 */
class Maps {
    /**
     * Instance of Maps
     *
     * @var Maps
     */
    use GetInstance;

    public $api_base = 'https://api.notificationx.com/maps/v1/';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        add_action( 'admin_init', [ $this, '_admin_init' ] );
        add_filter( 'nx_notification_template', [ $this, 'map_templates' ], 7 );
        add_filter( 'nx_show_image_options', array( $this, 'show_image_options' ) );
        add_filter( 'nx_notification_image', array( $this, 'map_image' ), 13, 3 );
        add_filter( 'nx_fallback_data', array( $this, 'fallback_data' ), 10, 3 );
        // add_filter('nx_settings_tab_api_integration', [$this, 'api_integration_settings']);
        // add_filter('nx_api_connect_map', [$this, 'connect_map'], 10, 2);
        add_filter( 'nx_insert_entry', [ $this, 'save_map_image' ], 10 );
    }

    public function _admin_init() {
    }

    public function show_image_options( $options ) {
        $options['maps_image'] = [
            'value' => 'maps_image',
            'label' => __( 'Map Image', 'notificationx-pro' ),
            'rules' => [
                'includes',
                'source',
                [
                    'cf7',
                    'custom_notification',
                    'edd',
                    'envato',
                    'grvf',
                    'give',
                    'ifttt',
                    'learndash',
                    'njf',
                    'tutor',
                    'wpf',
                    'reviewx',
                    'woocommerce',
                    'woo_reviews',
                    'wp_reviews',
                    'wp_comments',
                    'mailchimp',
                    'convertkit',
                    'zapier_email_subscription',
                ],
            ],
        ];
        return $options;
    }


    public function get_themes( $_data = [] ) {
        $_default = [
            'first_param'         => 'tag_name',
            'custom_first_param'  => __( 'Someone', 'notificationx-pro' ),
            'second_param'        => __( 'from', 'notificationx-pro' ),
            'third_param'         => 'tag_city',
            'custom_third_param'  => __( 'Location', 'notificationx-pro' ),
            'map_fourth_param'    => __( 'subscribed', 'notificationx-pro' ),
            'fourth_param'        => 'tag_title',
            'custom_fourth_param' => __( 'Anonymous Title', 'notificationx-pro' ),
            'fifth_param'         => 'tag_time',
            'custom_fifth_param'  => __( 'Some time ago', 'notificationx-pro' ),
        ];
        return ! empty( $_data ) ? array_merge( $_default, $_data ) : $_default;
    }

    public function get_templates() {
        return [
            'maps_template_new' => [
                'first_param'  => GlobalFields::get_instance()->common_name_fields(),
                'third_param'  => [
                    'tag_city'         => __( 'City', 'notificationx-pro' ),
                    'tag_country'      => __( 'Country', 'notificationx-pro' ),
                    'tag_city_country' => __( 'City, Country', 'notificationx-pro' ),
                ],
                'fourth_param' => [
                    'tag_title'           => __( 'Title', 'notificationx-pro' ),
                    'tag_anonymous_title' => __( 'Anonymous Title', 'notificationx-pro' ),
                ],
                'fifth_param'  => [
                    'tag_time' => __( 'Definite Time', 'notificationx-pro' ),
                    // 'tag_sometime' => __('Some time ago' , 'notificationx-pro'),
                ],
                '_themes'      => apply_filters( 'nx_map_fourth_param_dependency', [] ),
            ],
        ];
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function map_templates( $template ) {
        $template['map_fourth_param'] = [
            // 'label'     => __("Review Fourth Parameter", 'notificationx-pro'),
            'name'     => 'map_fourth_param',
            'type'     => 'text',
            'priority' => 27,
            'default'  => '',
            'rules'    => Rules::includes( 'themes', apply_filters( 'nx_map_fourth_param_dependency', [] ) ),
        ];
        return $template;
    }

    public function is_map_image( $settings ) {
        $is_map_image = false;
        $theme         = str_replace( [ $settings['type'] . '_', $settings['source'] . '_' ], '', $settings['themes'] );
        if ( ! empty( $settings['custom_type'] ) ) {
            $theme = str_replace( $settings['custom_type'] . '_', '', $theme );
        }
        $image_enabled = $settings['show_notification_image'] === 'maps_image';

        if ( ( $theme === 'maps_theme' || $theme === 'conv-theme-six' ) && $image_enabled ) {
            $is_map_image = true;
        }
        return apply_filters("nx_is_map_image_{$settings['source']}", $is_map_image, $settings);
    }

    public function map_image( $image_data, $data, $settings ) {
        if ( ! $settings['show_default_image'] ) {
            $image_enabled = $this->is_map_image( $settings );

            if ( $image_enabled ) {
                if ( ! empty( $data['map_url'] ) ) {
                    $image_data['url'] = $data['map_url'];
                } else {
                    $map_image_url     = NOTIFICATIONX_PRO_URL . 'assets/public/image/maps/' . rand( 1, 10 ) . '.jpg';
                    $image_data['url'] = $map_image_url;
                }

                $city    = isset( $data['city'] ) ? $data['city'] : __( 'Somewhere', 'notificationx-pro' );
                $country = isset( $data['country'] ) ? $data['country'] : __( 'Somewhere', 'notificationx-pro' );

                $image_data['attr'] = [
                    'data-city'    => $city,
                    'data-country' => $country,
                ];
            }
        }
        return $image_data;
    }

    public function save_map_image( $entry ) {
        try {
            $data  = $entry['data'];
            $query = null;

            // $data['ip'] = '27.147.223.142';
            $settings = PostType::get_instance()->get_post( $entry['nx_id'] );

            if ( $this->is_map_image( $settings ) ) {
                // check if city/country empty first.
                if ( ! empty( $data['ip'] ) && $data['ip'] !== '127.0.0.1' && $data['ip'] !== '::1' && empty( $data['lat'] ) && empty( $data['lon'] ) && empty( $data['map_url'] ) ) {
                    $query = [ 'ip' => $data['ip'] ];
                } elseif ( isset( $data['lat'], $data['lon'] ) && empty( $data['map_url'] ) ) {
                    $query = [
                        'lat' => $data['lat'],
                        'lon' => $data['lon'],
                    ];
                }

                if ( ! empty( $query ) ) {
                    $ip_data = $this->get_map_from_api( $query );

                    if ( ! empty( $ip_data ) ) {
                        $entry['data'] = wp_parse_args( array_filter( $entry['data'] ), $ip_data );
                    }
                }
            }
        } catch ( \Exception $th ) {
            // throw $th;
        }
        return $entry;
    }

    public function get_map_from_api( $where ) {
        $map_data = Database::get_instance()->get_map( $where );

        if ( empty( $map_data ) ) {
            $query        = http_build_query( $where );
            $user_ip_data = Helper::remote_get( $this->api_base . '?' . $query );
            if ( ! empty( $user_ip_data->data ) ) {
                $map_data  = (array) $user_ip_data->data;
                $map_url   = $map_data['map_url'];
                $file_name = $this->map_path( $where, 'dir' );
                $this->_save_map_image( $map_url, $file_name );
                $map_data['map_url'] = $this->map_path( $where, 'url' );

                Database::get_instance()->insert_map(
                    [
                        'ip'   => isset($map_data['ip']) ? $map_data['ip'] : '',
                        'lat'  => $map_data['lat'],
                        'lon'  => $map_data['lon'],
                        'data' => $map_data,
                    ]
                );
            }
        } else {
            if ( strpos( $map_data['map_url'], 'https://maps.googleapis.com/maps/' ) === 0 ) {
                $file_name = $this->map_path( $where, 'dir' );
                $this->_save_map_image( $map_data['map_url'], $file_name );
                $map_data['map_url'] = $this->map_path( $where, 'url' );
            }
        }

        return $map_data;
    }

    /**
     * Save image to upload dir.
     *
     * @param string $map_url
     * @param string $file_name
     * @return void
     */
    public function _save_map_image( $map_url, $file_name ) {
        try {
            if ( ! file_exists( $file_name ) ) {
                $result = Helper::remote_get( $map_url, [], true );
                if ( ! empty( $result['body'] ) ) {
                    if ( ! is_dir( dirname( $file_name ) ) ) {
                        wp_mkdir_p( dirname( $file_name ) );
                    }
                    return file_put_contents( $file_name, $result['body'] );
                }
            }
        } catch ( \Exception $th ) {
            // throw $th;
        }
        return false;
    }


    public function map_path( $query, $type = 'dir' ) {
        $uploads = wp_get_upload_dir();

        $uploads = apply_filters( 'nx_map_upload_dir',
            array(
                'dir' => trailingslashit( $uploads['basedir'] . '/nx-map' ),
                'url' => trailingslashit( $uploads['baseurl'] . '/nx-map' ),
            )
        );

        $file_name = implode( ',', $query );

        if ( 'dir' == $type ) {
            return $uploads['dir'] . "$file_name.png";
        } else {
            return $uploads['url'] . "$file_name.png";
        }
    }

    // @todo Something
    public function fallback_data( $data, $saved_data, $settings ) {
        $theme = str_replace( [ $settings['source'], $settings['type'] ], '', $settings['themes'] );
        if ( $theme === '_maps_theme' || $theme === '_conv-theme-six' ) {
            $data['city']    = __( 'Somewhere', 'notificationx-pro' );
            $data['country'] = __( 'Somewhere', 'notificationx-pro' );

            $city                 = ! empty( $saved_data['city'] ) ? $saved_data['city'] : '';
            $country              = ! empty( $saved_data['country'] ) ? $saved_data['country'] : '';
            $data['city_country'] = ( $city . $country ) ? "$city, $country" : __( 'Somewhere', 'notificationx-pro' );
        }
        return $data;
    }

    public function api_integration_settings( $fields ) {
        $fields['map_settings_section'] = array(
            'type'     => 'section',
            'name'     => 'map_settings_section',
            'label'    => __( 'Google Map Settings', 'notificationx-pro' ),
            'priority' => 70,
            'fields'   => array(
                'gmap_token' => array(
                    'name'        => 'gmap_token',
                    'description' => __( '', 'notificationx-pro' ),
                    'type'        => 'text',
                    'label'       => __( 'API key', 'notificationx-pro' ),
                    'priority'    => 1,
                    // translators: %s: Google Map docs link.
                    'description' => sprintf( __( '<a target="_blank" rel="nofollow" href="%s">Click here</a> to get your API key.', 'notificationx-pro' ), 'https://developers.google.com/maps/gmp-get-started' ),
                ),
                [
                    'name'    => 'gmap_connect',
                    // 'label' => 'Connect Button',
                    'type'    => 'button',
                    'default' => false,
                    'text'    => [
                        'normal'  => __( 'Connect', 'notificationx-pro' ),
                        'saved'   => __( 'Refresh', 'notificationx-pro' ),
                        'loading' => __( 'Refreshing...', 'notificationx-pro' ),
                    ],
                    'ajax'    => [
                        'on'   => 'click',
                        'api'  => '/notificationx/v1/api-connect',
                        'data' => [
                            'source'              => 'map',
                            'gmap_token'          => '@gmap_token',
                            'gmap_cache_duration' => '@gmap_cache_duration',
                        ],
                    ],
                ],
            ),
        );
        return $fields;
    }

    public function connect_map( $result, $params ) {
        if ( ! empty( $params['gmap_token'] ) ) {
            Settings::get_instance()->set( 'settings.gmap_token', $params['gmap_token'] );
            Settings::get_instance()->set( 'settings.gmap_cache_duration', $params['gmap_cache_duration'] );

            return array(
                'status' => 'success',
            );
        }
        return $result;
    }
}

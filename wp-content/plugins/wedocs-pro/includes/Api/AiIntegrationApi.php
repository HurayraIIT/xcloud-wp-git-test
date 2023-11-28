<?php

namespace WeDevs\WeDocsPro\Api;

use WeDevs\WeDocsPro\AI\AiIntegration;
use WP_REST_Server;

/**
 * API Class
 */
class AiIntegrationApi {

    /**
     * WP Version Number.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $version = '2';

    /**
     * WP Version Slug.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $namespace = 'wp/v';

    /**
     * Post Type Base.
     *
     * @since 1.0.0
     *
     * @var string
     */
    protected $base = 'docs/settings/ai-integration';

    /**
     * Initialize the class
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_api' ] );
    }

    /**
     * Register the API.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_api() {
        register_rest_route( $this->namespace . $this->version, '/' . $this->base,
            [
                [
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => [ $this, 'check_ai_integration' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'connect_to_ai' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );

        register_rest_route( $this->namespace . $this->version, '/' . $this->base . '/reset-ai',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'reset_ai' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );

        register_rest_route( $this->namespace . $this->version, '/' . $this->base . '/chat',
            [
                [
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => [ $this, 'get_chat' ],
                    'permission_callback' => [ $this, 'get_items_permissions_check' ],
                ],
            ]
        );
    }

    /**
     * Check items creation permission.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return bool
     */
    public function get_items_permissions_check( \WP_REST_Request $request ) {
        return true;
    }

    /**
     * Check AI integration.
     *
     * @since PAY_CHECK_MATE_SINCE
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Response
     */
    public function check_ai_integration( \WP_REST_Request $request ) {
        $ai            = new AiIntegration();
        $response      = $ai->check_in_remote();
        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = json_decode( wp_remote_retrieve_body( $response ) );

        return new \WP_REST_Response( [
            'message' => $body,
        ], $response_code );
    }

    public function connect_to_ai( \WP_REST_Request $request ) {
        $domain = str_replace( [ 'http://', 'https://', 'www.', '/' ], '', get_site_url() );
        $password = hash( 'sha256', trim( get_option( 'admin_email' ) ) . $domain . time() );
        update_option( 'wedocs_settings', [
            'integrate_ai' => [
                'ai_password' => $password,
            ],
        ] );
        $ai            = new AiIntegration();
        $response      = $ai->check_in_remote();
        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = json_decode( wp_remote_retrieve_body( $response ) );

        return new \WP_REST_Response( [
            'message' => $body,
            'aiPassword' => $ai->get_current_ai_password(),
        ], $response_code );
    }

    public function get_chat( \WP_REST_Request $request ) {
        if ( empty( $request->get_param( 'prompt' ) ) ) {
            return new \WP_REST_Response( [
                'message' => __( 'Prompt is required', 'wedocs-pro' ),
            ], 400 );
        }

        $ai            = new AiIntegration();
        $response      = $ai->get_chat( $request->get_param( 'prompt' ) );
        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = wp_remote_retrieve_body( $response );
        $result = json_decode( $body );
        if ( ! empty( $result->status ) && ( $result->status === 'error' || $result->status === 403 ) ) {
            return new \WP_REST_Response( [
                'chat' => __( 'According to my knowledge, I can not answer this question.', 'wedocs-pro' ),
            ], 200 );
        }

        return new \WP_REST_Response( [
            'chat' => $body,
        ], $response_code );
    }

    public function reset_ai( \WP_REST_Request $request ) {
        $ai            = new AiIntegration();
        $response      = $ai->reset_ai();
        $response_code = wp_remote_retrieve_response_code( $response );
        $body          = json_decode( wp_remote_retrieve_body( $response ) );

        return new \WP_REST_Response( [
            'message' => $body,
        ], $response_code );

    }

}

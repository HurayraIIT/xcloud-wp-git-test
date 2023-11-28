<?php

namespace WeDevs\WeDocsPro\Api;

use WP_REST_Server;

/**
 * API Class
 */
class SendMailApi {

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
    protected $base = 'docs';

    /**
     * Initialize the class.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_api' ) );
    }

    /**
     * Register the API.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_api() {
        register_rest_route( $this->namespace . $this->version, '/' . $this->base . '/message',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'send_widget_message' ),
                    'permission_callback' => '__return_true',
                    'args'                => [
                        'name' => [
                            'required'          => true,
                            'type'              => 'string',
                            'description'       => __( 'Sender name.', 'wedocs-pro' ),
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                        'from' => [
                            'required'          => true,
                            'type'              => 'email',
                            'description'       => __( 'Sender email.', 'wedocs-pro' ),
                            'sanitize_callback' => 'sanitize_email',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                        'message' => [
                            'required'          => true,
                            'description'       => __( 'Sending email body.', 'wedocs-pro' ),
                            'type'              => 'string',
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                        'attachment' => [
                            'required'          => false,
                            'description'       => __( 'Send email attachment.', 'wedocs-pro' ),
                            'type'              => 'mixed',
                            'validate_callback' => 'rest_validate_request_arg',
                        ],
                    ],
                ),
            )
        );
    }

    /**
     * Sending documentation messages form assistant widget.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request full data about the request
     *
     * @return mixed
     */
    public function send_widget_message( $request ) {
        $name  = $request->get_param( 'name' );
        $email = $request->get_param( 'from' );
        $body  = $request->get_param( 'message' );

        $attachment = array();
        if ( ! empty( $request->get_param( 'attachment' )['src'] ) ) {
            $attachment = array( $request->get_param( 'attachment' )['src'] );
        }

        $status = wedocs_send_floating_message( $name, $email, $body, $attachment );
        return rest_ensure_response( array( 'success' => $status ) );
    }
}

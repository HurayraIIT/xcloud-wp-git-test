<?php

namespace WeDevs\WeDocsPro\Api;

use WP_REST_Server;

/**
 * API Class
 */
class MetaApi {

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
     * Initialize the class
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_api' ) );
        add_action( 'save_post_docs', array( $this, 'save_documentation_contributors' ), 10, 2 );
    }

    /**
     * Save documentation contributors id.
     *
     * @since 1.0.0
     *
     * @param int      $post_id Post id.
     * @param \WP_Post $post    Post object.
     *
     * @return void|null
     */
    public function save_documentation_contributors( $post_id, $post ) {
        // Check if this is a parent page.
        if ( $post->post_parent === 0 ) {
            $this->save_parent_contributors( $post );
            return;
        }

        // Check if this is a section page.
        $parent_id = wp_get_post_parent_id( $post->post_parent );
        if ( false === $parent_id ) {
            return;
        }

        $user_id      = get_current_user_id();
        $contributors = get_post_meta( $post_id, 'wedocs_contributors', true );
        $contributors = ! empty( $contributors ) ? $contributors : array();
        if ( ! in_array( $user_id, $contributors, true ) ) {
            array_push( $contributors, absint( $user_id ) );
        }

        // Save the doc contributors meta.
        update_post_meta( $post_id, 'wedocs_contributors', (array) $contributors );
        wedocs_update_documentation_contributors( $post_id, $contributors );
    }

    /**
     * Handle contributors data of parent documentation.
     *
     * @since 1.0.0
     *
     * @param \WP_Post $post WP Post object instance.
     *
     * @return void
     */
    public function save_parent_contributors( $post ) {
        $article_ids  = wedocs_get_documentation_children_by_type( $post->ID );
        $contributors = wedocs_get_documentation_contributors( $post->post_author, $article_ids );

        update_post_meta( $post->ID, 'wedocs_contributors', (array) $contributors );
    }

    /**
     * Register the API.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_api() {
        register_rest_route( $this->namespace . $this->version, '/' . $this->base . '/(?P<id>[\d]+)/meta', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_items' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
                'args'                => array(
                    'key' => array(
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                        'description'       => esc_html__( 'Meta key', 'wedocs-pro' ),
                    )
                ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_item' ),
                'permission_callback' => array( $this, 'create_item_permissions_check' ),
                'args'                => array(
                    'key'   => array(
                        'type'              => 'string',
                        'required'          => true,
                        'sanitize_callback' => 'sanitize_key',
                        'description'       => esc_html__( 'Meta key', 'wedocs-pro' ),
                    ),
                    'value' => array(
                        'required'          => true,
                        'description'       => esc_html__( 'Meta value', 'wedocs-pro' ),
                        'validate_callback' => function ( $param ) {
                            return is_string( $param ) || is_numeric( $param ) || is_array( $param );
                        },
                    )
                ),
            ),
        ) );
    }

    /**
     * Check items creation permission.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return true|\WP_Error
     */
    public function get_items_permissions_check( \WP_REST_Request $request ) {
        if ( current_user_can( 'read' ) ) {
            return true;
        }

        return new \WP_Error( 'wedocs_permission_failure', __( "You don't have permission to create post meta", 'wedocs-pro' ) );
    }

    /**
     * Check meta data handling permission.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return true|\WP_Error
     */
    public function create_item_permissions_check( \WP_REST_Request $request ) {
        return $this->get_items_permissions_check( $request );
    }

    /**
     * Collect docs meta data.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function get_items( \WP_REST_Request $request ) {
        $id  = absint( $request->get_param( 'id' ) );
        $key = $request->get_param( 'key' );

        $meta = get_post_meta( $id, $key, true );
        return rest_ensure_response( $meta );
    }

    /**
     * Create docs meta data.
     *
     * @since 1.0.0
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
     */
    public function create_item( \WP_REST_Request $request ) {
        $id    = absint( $request->get_param( 'id' ) );
        $key   = $request->get_param( 'key' );
        $value = $request->get_param( 'value' );

        update_post_meta( $id, $key, $value );

        $meta = $this->get_items( $request );
        return rest_ensure_response( $meta );
    }
}

<?php

namespace WeDevs\WeDocsPro;

use WeDevs\WeDocsPro\AccessControl\AccessControl;

/**
 * The admin class
 */
class Admin {

    /**
     * Initialize the class
     */
    public function __construct() {
        add_action( 'wedocs_publish_cap', array( $this, 'handle_documentation_managing_capabilities' ) );
        add_action( 'wedocs_doc_tag_management_capabilities', array( $this, 'handle_doc_tag_managing_capabilities' ) );
        add_action( 'admin_init', array( $this, 'add_wedocs_settings_handling_capability' ) );

        add_filter( 'wedocs_settings_management_capabilities', array( $this, 'handle_wedocs_settings_manager_capabilities' ) );

        new AccessControl();
    }

    /**
     * Handle weDocs settings manager capabilities.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_wedocs_settings_handling_capability() {
        $users_query         = new \WP_User_Query( array( 'fields' => 'all_with_meta' ) );
        $permission_settings = wedocs_get_permission_settings( 'role_wise_permission', [] );

        $users = $users_query->get_results();
        foreach ( $users as $user ) {
            $user_role = ! empty( $user->roles[0] ) ? $user->roles[0] : '';

            if ( $user_role === 'administrator' || ( in_array( $user_role, $permission_settings ) && empty( $user->has_cap( 'edit_wedocs_settings' ) ) ) ) {
                $user->add_cap( 'edit_wedocs_settings' );
                continue;
            }

            if ( ! in_array( $user_role, $permission_settings ) && $user->has_cap( 'edit_wedocs_settings' ) ) {
                $user->remove_cap( 'edit_wedocs_settings' );
            }
        }
    }

    /**
     * Set weDocs settings handling capabilities.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function handle_wedocs_settings_manager_capabilities() {
        return 'edit_wedocs_settings';
    }

    /**
     * Set documentation managing capabilities.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function handle_documentation_managing_capabilities() {
        return 'read';
    }

    /**
     * Set taxonomy doc tag managing capabilities.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function handle_doc_tag_managing_capabilities() {
        return 'manage_options';
    }
}

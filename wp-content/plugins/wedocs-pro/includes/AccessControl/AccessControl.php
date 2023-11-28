<?php

namespace WeDevs\WeDocsPro\AccessControl;

class AccessControl {

    /**
     * AccessControl constructor.
     *
     * @since WEDOCS_SINCE
     */
    public function __construct() {
        add_filter( 'map_meta_cap', array( $this, 'wedocs_map_meta_caps' ), 10, 4 );
    }

    /**
     * WeDocs map meta caps for doc users.
     *
     * @since 1.0.0
     *
     * @param array  $caps
     * @param string $cap
     * @param int    $user_id
     * @param array  $args
     *
     * @return array
     */
    public function wedocs_map_meta_caps( $caps, $cap, $user_id, $args ) {
        global $post;

        if ( ! is_admin() ) {
            return $caps;
        }

        $post_id   = ! empty( $args[0] ) ? absint( $args[0] ) : ( ! empty( $post->ID ) ? $post->ID : 0 );
        $post_type = get_post_type( $post_id );
        if ( 'docs' !== $post_type ) {
            return $caps;
        }

        $capabilities_array = array( 'edit_post', 'edit_docs', 'publish_docs', 'edit_others_docs', 'read_private_docs', 'edit_private_docs', 'edit_published_docs' );
		if ( ! in_array( $cap, $capabilities_array, true ) ) {
			return $caps;
		}

        // Get user roles form user id.
        $user_roles = get_user_by( 'id', $user_id )->roles;
        if ( in_array( 'administrator', $user_roles, true ) ) {
            return $caps;
        }

        $capabilities = array( 'manage_xyz' );
	    if ( $this->check_doc_is_admin_only_editable( $post_id ) ) {
			return $capabilities;
	    }

        // Check if current user has cap for edit documentation.
		foreach ( $user_roles as $user_role ) {
			if ( $this->current_user_can_edit_doc( $post_id, $user_role ) ) {
                return $caps;
			}
		}

        return $capabilities;
    }

    /**
     * Check current user can edit this documentation.
     *
     * @since 1.0.0
     *
     * @param int    $doc_id
     * @param string $user_role
     *
     * @return bool
     */
    public function current_user_can_edit_doc( $doc_id, $user_role ) {
        if ( ! $this->is_a_parent_doc( $doc_id ) ) {
            $ancestors = get_post_ancestors( $doc_id );
            $doc_id    = end( $ancestors );
        }

        // Check if user role has access from wedocs settings.
        $get_role_settings = get_post_meta( $doc_id, 'wedocs_user_permission', true );
        if ( $get_role_settings !== 'custom' ) {
            $permitted_roles = wedocs_get_permission_settings( 'global_permission', [] );
            return in_array( $user_role, $permitted_roles, true );
        }

        $doc_access_caps = get_post_meta( $doc_id, 'wedocs_access_role_capabilities', true );
        return is_array( $doc_access_caps ) && ! empty( $doc_access_caps[ $user_role ] ) && $doc_access_caps[ $user_role ] === 'edit';
    }

    /**
     * Check this documentation is admin restricted.
     *
     * @since 1.0.0
     *
     * @param int $doc_id
     *
     * @return bool
     */
    public function check_doc_is_admin_only_editable( $doc_id ) {
        return (bool) get_post_meta( $doc_id, 'wedocs_restrict_admin_article_access', true );
    }

    /**
     * Check this documentation is parent.
     *
     * @since 1.0.0
     *
     * @param int $doc_id
     *
     * @return bool
     */
    public function is_a_parent_doc( $doc_id ) {
        return (int) wp_get_post_parent_id( $doc_id ) === 0;
    }
}

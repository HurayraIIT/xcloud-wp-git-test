<?php

namespace WeDevs\WeDocsPro;

/**
 * Class Installer
 *
 * @package WeDevs\WeDocsPro
 */
class Installer {

    /**
     * Run the installer
     *
     * @return void
     */
    public function run() {
        $this->add_version();
        $this->add_article_contributors();
        $this->add_documentation_contributors();
    }

    /**
     * Add time and version on DB.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_version() {
        $installed = get_option( 'wedocs_pro_installed' );

        if ( ! $installed ) {
            update_option( 'wedocs_pro_installed', time() );
        }

        update_option( 'wedocs_pro_installed', WEDOCS_PRO_VERSION );
    }

    /**
     * Add wedocs article contributors list.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_article_contributors() {
        $args = array(
            'post_type'      => 'docs',
            'post_status'    => 'publish,private',
            'posts_per_page' => -1,
        );

        $documentations = get_posts( $args );
        foreach ( $documentations as $documentation ) {
            $contributors = get_post_meta( $documentation->ID, 'wedocs_contributors', true );
            if ( empty( $contributors ) && $documentation->post_parent !== 0 ) {
                update_post_meta( $documentation->ID, 'wedocs_contributors', array( $documentation->post_author ) );
            }
        }
    }

    /**
     * Add wedocs documentation contributors list.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function add_documentation_contributors() {
        $args = array(
            'post_type'      => 'docs',
            'post_status'    => 'publish,private',
            'post_parent'    => 0,
            'posts_per_page' => -1,
        );

        $documentations = get_posts( $args );
        foreach ( $documentations as $documentation ) {
            $contributors = get_post_meta( $documentation->ID, 'wedocs_contributors', true );
            if ( empty( $contributors ) ) {
                $article_ids  = wedocs_get_documentation_children_by_type( $documentation->ID );
                $contributors = wedocs_get_documentation_contributors( $documentation->post_author, $article_ids );
                update_post_meta( $documentation->ID, 'wedocs_contributors', (array) $contributors );
            }
        }
    }
}

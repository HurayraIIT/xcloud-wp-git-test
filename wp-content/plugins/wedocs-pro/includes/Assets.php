<?php

namespace WeDevs\WeDocsPro;

use WeDevs\WeDocsPro\AI\AiIntegration;

/**
 * Scripts and Styles Class
 */
class Assets {

    /**
     * Assets constructor.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register' ), 15 );
        add_action( 'init', array( $this, 'register_translations' ), 15 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ), 5 );
    }

    /**
     * Register plugin assets.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register() {
        $wedocs_pro_asset = require WEDOCS_PRO_PATH . '/assets/build/index.asset.php';
        $dependencies     = $wedocs_pro_asset['dependencies'];

        wp_enqueue_media();

        // Register admin scripts.
        wp_register_script(
            'wedocs-pro-js',
            plugins_url( 'assets/build/index.js', WEDOCS_PRO_FILE ),
            $dependencies,
            $wedocs_pro_asset['version'],
            true
        );
        wp_register_style(
            'wedocs-pro-css',
            plugins_url( 'assets/build/index.css', WEDOCS_PRO_FILE ),
            array(),
            $wedocs_pro_asset['version']
        );

        wp_localize_script( 'wedocs-pro-js', 'weDocsPro_Vars', [
            'canAccessSettings' => wedocs_current_user_can_access_settings(),
            'aiApiBaseUrl'      => ( new AiIntegration() )->get_api_base_url(),
        ] );

        $wedocs_frontend_asset = require WEDOCS_PRO_PATH . '/assets/build/frontend.asset.php';
        $frontend_dependencies = $wedocs_frontend_asset['dependencies'];

        // Register frontend scripts.
        wp_register_script(
            'wedocs-pro-frontend-js',
            plugins_url( 'assets/build/frontend.js', WEDOCS_PRO_FILE ),
            $frontend_dependencies,
            $wedocs_frontend_asset['version'],
            true
        );
        wp_register_style(
            'wedocs-pro-frontend-css',
            plugins_url( 'assets/build/frontend.css', WEDOCS_PRO_FILE ),
            array(),
            $wedocs_frontend_asset['version']
        );

        // Register cloudflare turnstile script.
        wp_register_script(
            'wedocs-cloudflare-turnstile',
            '//challenges.cloudflare.com/turnstile/v0/api.js',
            array(),
            '0.0.0',
            true
        );
    }

    /**
     * Register script translations
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function register_translations() {
        wp_set_script_translations(
            'wedocs-pro-js',
            'wedocs-pro',
            plugin_dir_path( WEDOCS_PRO_FILE ) . 'languages'
        );
    }

    /**
     * Enqueue admin scripts.
     *
     * @since 1.0.0
     *
     * @return void
     */
    public function admin_enqueue() {
        if ( 'toplevel_page_wedocs' === get_current_screen()->id ) {
            wp_enqueue_style( 'wedocs-pro-css' );
            wp_enqueue_script( 'wedocs-pro-js' );
        }
    }
}

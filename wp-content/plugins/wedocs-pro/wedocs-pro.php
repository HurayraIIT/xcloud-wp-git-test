<?php
/*
Plugin Name: weDocs Pro
Plugin URI: https://wedocs.co
Description: Premium version of weDocs
Version: 1.0
Author: weDevs
Author URI: https://wedevs.com
License: GPL-v3
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wedocs-pro
Domain Path: /languages
*/

/**
 * Copyright (c) 2020 weDevs (email: info@wedevs.com). All rights reserved.
 *
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// Don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Appsero\Client;
use WeDevs\WeDocsPro\AI\AiHooks;

/**
 * WeDocs_Pro class
 *
 * @class WeDocs_Pro The class that holds the entire WeDocs_Pro plugin.
 */
final class WeDocs_Pro {

    /**
     * Plugin version
     *
     * @var string
     */
    const version = '1.0';

    /**
     * Holds various class instances
     *
     * @var array
     */
    private $container = [];

    /**
     * The plugin path.
     *
     * @var string
     */
    private $plugin_path;

	/**
	 * The theme directory path.
	 *
	 * @var string
	 */
	private $theme_dir_path;

    /**
     * Constructor for the Plugin class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     */
    private function __construct() {
        $this->define_constants();

        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        add_action( 'plugins_loaded', array( $this, 'init_plugin' ) );

        $this->initiate_appsero();
    }

    /**
     * Initializes the WeDocs_Pro() class
     *
     * Checks for an existing WeDocs_Pro() instance
     * and if it doesn't find one, creates it.
     *
     * @return WeDocs_Pro|bool
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new WeDocs_Pro();
        }

        return $instance;
    }

    /**
     * Magic getter to bypass referencing plugin.
     *
     * @param string $prop
     *
     * @return mixed
     */
    public function __get( $prop ) {
        if ( array_key_exists( $prop, $this->container ) ) {
            return $this->container[ $prop ];
        }

        return $this->{$prop};
    }

    /**
     * Magic isset to bypass referencing plugin.
     *
     * @param string $prop
     *
     * @return mixed
     */
    public function __isset( $prop ) {
        return isset( $this->{$prop} ) || isset( $this->container[ $prop ] );
    }

    /**
     * Define the constants.
     *
     * @return void
     */
    public function define_constants() {
        define( 'WP_AI_DEBUG', true );
        define( 'WEDOCS_PRO_VERSION', self::version );
        define( 'WEDOCS_PRO_FILE', __FILE__ );
        define( 'WEDOCS_PRO_PATH', dirname( WEDOCS_PRO_FILE ) );
        define( 'WEDOCS_PRO_INCLUDES', WEDOCS_PRO_PATH . '/includes' );
        define( 'WEDOCS_PRO_URL', plugins_url( '', WEDOCS_PRO_FILE ) );
        define( 'WEDOCS_PRO_ASSETS', WEDOCS_PRO_URL . '/assets' );
    }

	/**
	 * Load the plugin after all plugins are loaded.
	 *
	 * @since 1.0.0
	 *
	 * @return true|void|null
	 */
    public function init_plugin() {
        if ( ! class_exists( 'WeDocs' ) ) return;

        if ( ! wedocs_is_license_valid() ) {
            return add_action( 'admin_notices', array( $this, 'show_license_activation_notice' ) );
        }

        $this->includes();
        $this->init_hooks();
    }

	/**
	 * Show weDocs pro license activation notice.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function show_license_activation_notice() {
		$message = sprintf(
            __( 'Please enter your valid <a href="%s">weDocs Pro plugin license key</a> to unlock more features, premium support and future updates.', 'wedocs-pro' ),
            admin_url( 'admin.php?page=wedocs_pro_settings' )
		);
		$class   = 'notice notice-info is-dismissible';

		echo '<div class="' . esc_attr( $class ) . '"><p>' . $message . '</p></div>';
	}

    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {
        $installer = new WeDevs\WeDocsPro\Installer();
        $installer->run();
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {

    }

    /**
     * Initiate Appsero telemetry.
     *
     * @since 1.0.0
     *
     * @return void
     */
    private function initiate_appsero() {
        $client = new Client(
            'c8eba6c5-b459-4401-ae9c-6300936f24b6',
            'weDocs Pro',
            WEDOCS_PRO_FILE
        );

        // Active insights
        $client->insights()->init();

        // Active automatic updater
        $client->updater();

        // Active license page and checker.
        $args = array(
            'type'        => 'submenu',
            'menu_title'  => __( 'weDocs Pro', 'wedocs-pro' ),
            'page_title'  => __( 'weDocs Pro Settings', 'wedocs-pro' ),
            'menu_slug'   => 'wedocs_pro_settings',
            'position'    => 99,
            'parent_slug' => 'wedocs',
        );

        $client->license()->add_settings_page( $args );
    }

    /**
     * Include the required files
     *
     * @return void
     */
    public function includes() {
        new AiHooks();
        if ( $this->is_request( 'admin' ) ) {
            $this->container['admin'] = new WeDevs\WeDocsPro\Admin();
        }

        if ( $this->is_request( 'frontend' ) ) {
            $this->container['frontend'] = new WeDevs\WeDocsPro\Frontend();
        }

        if ( $this->is_request( 'ajax' ) ) {
            // require_once WEDOCS_PRO_INCLUDES . '/class-ajax.php';
        }
    }

    /**
     * Initialize the hooks
     *
     * @return void
     */
    public function init_hooks() {
	    $this->theme_dir_path = apply_filters( 'wedocs_theme_dir_path', 'wedocs/' );

        add_action( 'init', [ $this, 'init_classes' ] );

        // Localize our plugin.
        add_action( 'init', [ $this, 'localization_setup' ] );
    }

    /**
     * Instantiate the required classes.
     *
     * @return void
     */
    public function init_classes() {
        if ( $this->is_request( 'ajax' ) ) {
            // $this->container['ajax'] =  new WeDevs\WeDocsPro\Ajax();
        }

        $this->container['api']    = new WeDevs\WeDocsPro\Api();
        $this->container['assets'] = new WeDevs\WeDocsPro\Assets();
    }

    /**
     * Initialize plugin for localization.
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'wedocs-pro', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * What type of request is this?
     *
     * @param string $type admin, ajax, cron or frontend.
     *
     * @return bool
     */
    private function is_request( $type ) {
        switch ( $type ) {
            case 'admin' :
                return is_admin();

            case 'ajax' :
                return defined( 'DOING_AJAX' );

            case 'rest' :
                return defined( 'REST_REQUEST' );

            case 'cron' :
                return defined( 'DOING_CRON' );

            case 'frontend' :
                return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
        }
    }

    /**
     * Get the plugin path.
     *
     * @return string
     */
    public function plugin_path() {
        if ( $this->plugin_path ) {
            return $this->plugin_path;
        }

        return $this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
    }

    /**
     * Get the template path.
     *
     * @return string
     */
    public function template_path() {
        return $this->plugin_path() . '/templates/';
    }

	/**
	 * Get the theme directory path.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function theme_dir_path() {
		return $this->theme_dir_path;
	}

} // WeDocs_Pro

/**
 * Initialize the main plugin
 *
 * @return \WeDocs_Pro|bool
 */
function wedocs_pro() {
    return WeDocs_Pro::init();
}

/**
 *  kick-off the plugin
 */
wedocs_pro();

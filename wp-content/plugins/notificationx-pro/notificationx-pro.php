<?php

/**
 * @package           NotificationX Pro
 * @link              https://wpdeveloper.com
 * @since             2.0.0
 *
 * @wordpress-plugin
 * Plugin Name:       NotificationX Pro
 * Plugin URI:        https://notificationx.com/
 * Description:       Create Email Subscription alerts, connect with Google Analytics, Zapier, Gravity Forms & others with NotificationX PRO. Get access to Analytics dashboard and many others.
 * Version:           2.8.3
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.com
 * License:           GPL-3.0+
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       notificationx-pro
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'NOTIFICATIONX_PRO_FILE', __FILE__ );
define( 'NOTIFICATIONX_PRO_VERSION', '2.8.3' );
define( 'NOTIFICATIONX_PRO_URL', plugins_url( '/', __FILE__ ) );
define( 'NOTIFICATIONX_PRO_PATH', plugin_dir_path( __FILE__ ) );

define( 'NOTIFICATIONX_PRO_ASSETS', NOTIFICATIONX_PRO_URL . 'assets/' );
define( 'NOTIFICATIONX_PRO_ASSETS_PATH', NOTIFICATIONX_PRO_PATH . 'assets/' );

define( 'NOTIFICATIONX_PRO_DEV_ASSETS', NOTIFICATIONX_PRO_URL . 'nxbuild/' );
define( 'NOTIFICATIONX_PRO_DEV_ASSETS_PATH', NOTIFICATIONX_PRO_PATH . 'nxbuild/' );

define( 'NOTIFICATIONX_PRO_EXT_DIR_PATH', NOTIFICATIONX_PRO_PATH . 'includes/Extensions/' );
define( 'NOTIFICATIONX_PRO_ADMIN_PATH', NOTIFICATIONX_PRO_PATH . 'admin/' );

define( 'NOTIFICATIONX_FREE_PLUGIN', NOTIFICATIONX_PRO_PATH . 'assets/library/notificationx.zip' );

// Licensing
define( 'NOTIFICATIONX_PRO_STORE_URL', 'https://api.wpdeveloper.com/' );
define( 'NOTIFICATIONX_PRO_SL_ITEM_ID', 99658 );
define( 'NOTIFICATIONX_PRO_SL_ITEM_SLUG', 'notificationx-pro' );
define( 'NOTIFICATIONX_PRO_SL_ITEM_NAME', 'NotificationX Pro' );

/**
 * The Core Engine of the Plugin
 */
if ( ! class_exists( '\NotificationXPro\NotificationX' ) ) {
    if ( nx_pro_is_free_compatible() && nx_free_is_pro_compatible() ) {
        require_once NOTIFICATIONX_PRO_PATH . 'vendor/autoload.php';
    }
    if ( ! nx_pro_is_plugin_active( 'notificationx/notificationx.php' ) ) {
        require_once NOTIFICATIONX_PRO_PATH . 'includes/CoreInstallerPro.php';
        new NotificationXPro\CoreInstallerPro();
    } else {
        add_action( 'admin_notices', 'compatibility_notice' );
    }
}

function nx_free_is_pro_compatible() {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    if ( isset( $plugins['notificationx-pro/notificationx-pro.php']['Version'] ) && version_compare( $plugins['notificationx-pro/notificationx-pro.php']['Version'], '2.5.0', '>=' ) ) {
        return true;
    }
    return false;
}

function nx_pro_is_free_compatible() {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $plugins = get_plugins();
    if ( isset( $plugins['notificationx/notificationx.php']['Version'] ) && version_compare( $plugins['notificationx/notificationx.php']['Version'], '2.4.0', '>=' ) ) {
        return true;
    }
    return false;
}

function compatibility_notice() {
    if ( nx_pro_is_free_compatible() ) {
        return;
    }
    ?>
        <div class="notice notice-warning is-dismissible">
            <p>
            <strong><?php _e( 'Recommended:', 'notificationx-pro' ); ?></strong>
            <?php // translators: %s: Plugins page link. ?>
            <?php echo sprintf( __( "Seems like you haven't updated the NotificationX Free version. Please make sure to update NotificationX plugin from <a href='%s'><strong>wp-admin -> Plugins</strong></a>.", 'notificationx-pro' ), esc_url( admin_url( 'plugins.php' ) ) ); ?>
            </p>
        </div>
    <?php
}

function nx_pro_is_plugin_active( $plugin ) {
    return in_array( $plugin, (array) get_option( 'active_plugins', array() ), true ) || nx_pro_is_plugin_active_for_network( $plugin );
}

function nx_pro_is_plugin_active_for_network( $plugin ) {
    if ( ! is_multisite() ) {
        return false;
    }

    $plugins = get_site_option( 'active_sitewide_plugins' );
    if ( isset( $plugins[ $plugin ] ) ) {
        return true;
    }

    return false;
}
add_action( 'plugins_loaded', 'nx_pro_licensing_manager' );

function nx_pro_licensing_manager(){
    // in case autoloader not loaded.
    require_once NOTIFICATIONX_PRO_PATH . 'includes/Core/Licensing/Licensing.php';
    require_once NOTIFICATIONX_PRO_PATH . 'includes/Core/Licensing/ProPluginUpdater.php';

    return \NotificationXPro\Core\Licensing\Licensing::get_instance([
        'slug' => 'notificationx-pro',
        'page_slug' => 'nx-settings&tab=tab-go-license',
        'name' => 'NotificationX Pro',
        'text_domain' => 'notificationx-pro',
        'item_id' => 99658,
        'store' => NOTIFICATIONX_PRO_STORE_URL,
        'dev_mode' => false,
    ]);
}

function activate_notificationx_pro() {
    update_option('nx_pro_activation_hook', true);
}
/**
 * Plugin Activator
 */
register_activation_hook( NOTIFICATIONX_PRO_FILE, 'activate_notificationx_pro' );
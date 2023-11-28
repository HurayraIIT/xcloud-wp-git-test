<?php
/**
 * Plugin Name:     Essential Blocks Pro
 * Plugin URI:      https://essential-blocks.com
 * Description:     The ultimate library for free & premium Gutenberg blocks to supercharge your WordPress website. Find exclusive PRO blocks & features such as Woo Product Carousel, Advanced Search, and many more.
 * Author:          WPDeveloper
 * Author URI:      https://wpdeveloper.com
 * Text Domain:     essential-blocks-pro
 * Domain Path:     /languages
 * Version:         1.3.0
 *
 * @package         EssentialBlocks\Pro\Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define things
define( 'ESSENTIAL_BLOCKS_PRO_FILE', __FILE__ );
define( 'ESSENTIAL_BLOCKS_PRO_VERSION', '1.3.0' );
define( 'ESSENTIAL_BLOCKS_REQUIRED_VERSION', '4.3.4' );

//Table Name constants
global $wpdb;
define( 'ESSENTIAL_BLOCKS_FORM_ENTRIES_TABLE', $wpdb->prefix . 'eb_form_entries' );
define( 'ESSENTIAL_BLOCKS_SEARCH_KEYWORD_TABLE', $wpdb->prefix . 'eb_search_keywords' );

require_once __DIR__ . '/vendor/autoload.php';
include_once ABSPATH . 'wp-admin/includes/plugin.php';

use EssentialBlocks\Pro\Core\Maintenance;
use EssentialBlocks\Pro\Core\ConditionalMaintainance;

//If Essential Blocks Free plugin is active, Run Pro Maintainance Function while Activate Pro
if ( function_exists( 'is_plugin_active' ) && is_plugin_active( 'essential-blocks/essential-blocks.php' ) ) {
    Maintenance::get_instance();
} else {
    ConditionalMaintainance::get_instance();
}

//If Essential Blocks Free plugin is loaded, Run Pro
function wpdev_essential_blocks_pro() {
    if ( ! did_action( 'essential_blocks::init' ) ) {
        return;
    }

    return EssentialBlocks\Pro\Plugin::get_instance();
}

add_action( 'plugins_loaded', 'wpdev_essential_blocks_pro' );

<?php
/**
 * Plugin Name:       Better Wishlist
 * Plugin URI:        https://wpdeveloper.com/plugins/better-wishlist
 * Description:       Wishlist functionality for your WooCommerce store.
 * Version:           0.0.3
 * Author:            WPDeveloper
 * Author URI:        https://wpdeveloper.com/
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       better-wishlist
 * Domain Path:       /languages
 *
 * @package           BetterWishlist
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	die;
}

define( 'BETTER_WISHLIST_PLUGIN_FILE', __FILE__ );
define( 'BETTER_WISHLIST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'BETTER_WISHLIST_PLUGIN_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'BETTER_WISHLIST_PLUGIN_URL', trailingslashit( plugins_url( '/', __FILE__ ) ) );
define( 'BETTER_WISHLIST_PLUGIN_VERSION', '0.0.3' );

// Require composer autoloader.
require plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

// Run the plugin.
BetterWishlist\Plugin::instance();

<?php
/**
 * Plugin Name: EmbedPress Pro
 * Description: EmbedPress Pro lets you embed your sources with advanced customization, custom branding, lazy load and many more amazing features.
 * Plugin URI: https://embedpress.com
 * Author: WPDeveloper
 * Version: 3.5.3
 * Author URI: https://wpdeveloper.com
 *
 * Text Domain: embedpress-pro
 */

use Embedpress\Pro\Classes\Bootstrap;
use Embedpress\Pro\Classes\Migration;
use Embedpress\Pro\Classes\Notice;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Defining plugin constants.
 *
 * @since 2.3.0
 */
define('EMBEDPRESS_PRO_PLUGIN_FILE', __FILE__);
define('EMBEDPRESS_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('EMBEDPRESS_PRO_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('EMBEDPRESS_PRO_PLUGIN_URL', plugins_url('/', __FILE__));
define('EMBEDPRESS_PRO_PLUGIN_VERSION', '3.5.3');

define('EMBEDPRESS_STORE_URL', 'https://api.wpdeveloper.com/');
define('EMBEDPRESS_SL_ITEM_ID', 414494);
define('EMBEDPRESS_SL_ITEM_SLUG', 'embedpress-pro');
define('EMBEDPRESS_SL_ITEM_NAME', 'EmbedPress Pro');
define('EMBEDPRESS_DEV_MODE', false);

/**
 * Including autoloader.
 *
 * @since 2.3.0
 */
require_once EMBEDPRESS_PRO_PLUGIN_PATH . 'autoload.php';
/**
 * Run plugin before lite version
 *
 * @since 2.3.0
 */
add_action('embedpress_before_init', function () {
    if(version_compare(EMBEDPRESS_VERSION,'2.4.0','>=')){
        Bootstrap::instance();

        //load gutenberg block
        require_once __DIR__ . '/Gutenberg/index.php';
    }else{
        add_action( 'admin_notices',function(){
            $msg = 'EmbedPress Pro need EmbedPress 2.4.0 or above';
            echo '<div class="notice notice-warning">
				<p>'.$msg.'</p>
			</div>';
        } );
    }

});


/**
 * Plugin updater
 *
 * @since v2.3.0
 */
add_action('plugins_loaded', function () {
	if(EMBEDPRESS_DEV_MODE === false ) {
		$migration = new Migration;
		$migration->plugin_updater();
	}
});



/**
 * Plugin migrator
 *
 * @since v2.3.0
 */
add_action('wp_loaded', function () {
	$migration = new Migration;
	$migration->migrator();
});

/**
 * Activation hook
 *
 * @since v2.0.0
 */
register_activation_hook(__FILE__, function () {
	$migration = new Migration;
	$migration->plugin_activation_hook();
});

/**
 * Admin Notices
 *
 * @since 2.0.0
 */
add_action('admin_notices', function () {
    $notice = new Notice;
    $notice->failed_to_load();
});

if (!function_exists('stringToBoolean')){
    function stringToBoolean($attributes) {
        if(is_array($attributes)) {
            foreach ($attributes as $key => $value) {
                if(!empty($value) && $value === 'true'){
                    $attributes[$key] = true;
                }
				else if(!empty($value) && $value === 'false'){
                    $attributes[$key] = false;
                }
            }
		}
        return $attributes;
    }
}

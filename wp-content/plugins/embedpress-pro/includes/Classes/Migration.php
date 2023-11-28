<?php

namespace Embedpress\Pro\Classes;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

class Migration
{

    /**
     * Plugin activation hook
     *
     * @since 2.0.0
     */
    public function plugin_activation_hook()
    {
        // make free version available
        set_transient('embedpress_install', true, 1800);
    }
    
    /**
     * Plugin upgrader
     *
     * @since v1.0.0
     */
    public function plugin_updater()
    {
        // Disable SSL verification
        add_filter('edd_sl_api_request_verify_ssl', '__return_false');

        // Setup the updater
        $license = get_option(EMBEDPRESS_SL_ITEM_SLUG . '-license-key');

        $updater = new Plugin_Updater(
	        EMBEDPRESS_STORE_URL,
	        EMBEDPRESS_PRO_PLUGIN_BASENAME,
            [
                'version' => EMBEDPRESS_PRO_PLUGIN_VERSION,
                'license' => $license,
                'item_id' => EMBEDPRESS_SL_ITEM_ID,
                'author' => 'WPDeveloper',
            ]
        );

    }

    /**
     * Plugin migrator
     *
     * @since 2.0.0
     */
    public function migrator()
    {
        // migration trick
        if (get_option('embedpress_pro_version') != EMBEDPRESS_PRO_PLUGIN_VERSION) {
            // set current version to db
            update_option('embedpress_pro_version', EMBEDPRESS_PRO_PLUGIN_VERSION);

            /**
             * Tricky update here
             *
             * @since 2.0.0
             */

            // make lite version available
            set_transient('embedpress_install', true, 1800);

        }

        // check for lite version
        if (get_transient('embedpress_install')) {
            // install lite version
            Helper::make_lite_available();
        }
    }
}

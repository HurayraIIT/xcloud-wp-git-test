<?php

namespace WPSP_PRO;

use WPSP_PRO\Traits\Core;
use WPSP_PRO\Traits\Library;

class Installer
{
    use Library;
    use Core;

    public function __construct()
    {
        add_action('plugins_loaded', array($this, 'plugin_redirect'), 90);
        add_action( 'wpsp_run_deactivate_installer',array( $this, 'after_deactivate_pro_version' ) );
    }

    /**
     * Plugin activation hook
     *
     * @since 2.0.0
     */
    public function plugin_activation_hook()
    {

        // make lite version available
        set_transient('wpscp_install_lite', true, 1800);
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
        $license = get_option(WPSP_PRO_SL_ITEM_SLUG . '-license-key');

        $updater = new Admin\WPDev\Updater(
            WPSP_PRO_STORE_URL,
            WPSP_PRO_PLUGIN_BASENAME,
            [
                'version' => WPSP_PRO_VERSION,
                'license' => $license,
                'item_id' => WPSP_PRO_SL_ITEM_ID,
                'item_name' => "SchedulePress",
                'author' => 'WPDeveloper',
            ]
        );
    }

    public function plugin_redirect()
    {
        if (get_option('wpsp_do_activation_redirect', false) && class_exists('WPSP')) {
            delete_option('wpsp_do_activation_redirect');
            wp_safe_redirect(admin_url('admin.php?page=' . WPSP_PRO_SETTINGS_SLUG));
        }
    }

    /**
     * Plugin migrator
     *
     * @since 2.0.0
     */
    public function migrate()
    {
        // db update if version number is changed
        if (version_compare(get_option('wpscp_pro_version'), WPSP_PRO_VERSION, '<')) {
            do_action('wpsp_save_pro_settings_default_value' );
        }

        if(get_option('manage-schedule.php') || Migration::get_manage_schedule_settings()){
            Migration::version_2_to_4();
        }

         // migration trick
        if (get_option('wpscp_pro_version') != WPSP_PRO_VERSION) {

            // set current version to db
            update_option('wpscp_pro_version', WPSP_PRO_VERSION);
            // make lite version available
            set_transient('wpscp_install_lite', true, 1800);
        }
        // check for lite version
        if (get_transient('wpscp_install_lite')) {
            // install lite version
            $this->make_lite_available();
        }
    }

    /**
     * Status change for social profile when deactive pro version
    */
    public function after_deactivate_pro_version(){
        // $settings = json_decode(get_option(WPSP_SETTINGS_NAME), true);
        // $profile_list_array = ['facebook_profile_list','twitter_profile_list','linkedin_profile_list','pinterest_profile_list'];
        // foreach ($profile_list_array as  $profile_name) {
        //     if( isset( $settings[$profile_name] ) ) {
        //         $i = 0;
        //         foreach ($settings[$profile_name] as $key => &$profile) {
        //             if( $profile_name == 'linkedin_profile_list' && $profile['type'] === 'organization'  ) {
        //                 $profile['__status'] = $profile['status'];
        //                 $profile['status'] = false;
        //                 continue;
        //             }
        //             if( $key !== 0 ) {
        //                 $profile['__status'] = $profile['status'];
        //                 $profile['status'] = false;
        //             }
        //             $i++;
        //         }
        //     }
        // }
        // update_option(WPSP_SETTINGS_NAME, json_encode($settings));
    }
}

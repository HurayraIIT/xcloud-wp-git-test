<?php
namespace BetterLinksPro;

class Migration {
    public static function init(){
        // update plugin version
		if (get_option('betterlinks_pro_version') != BETTERLINKS_PRO_VERSION) {
            if(class_exists('\BetterLinks\Helper')){
                \BetterLinks\Helper::create_cron_jobs_for_json_links();
            }
			update_option('betterlinks_pro_version', BETTERLINKS_PRO_VERSION);

            if( version_compare(BETTERLINKS_PRO_VERSION, '1.6.2' , '>') ) {
                (new \BetterLinksPro\Helper)->add_password_protect_page();
            }
        }
        if(!get_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME)){
			add_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME, array(
				'is_enable_ga' => false,
				'ga_tracking_code' => '',
			));
		}
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name IN( 'betterlinks_autolink_options' )");
    }
}
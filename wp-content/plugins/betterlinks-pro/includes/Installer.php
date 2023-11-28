<?php
namespace BetterLinksPro;

use BetterLinksPro\Traits\DBTables;

class Installer
{
    use DBTables;

    protected $wpdb;
    protected $charset_collate;

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->charset_collate = $wpdb->get_charset_collate();
        
        $this->createBetterClicksRotationsTable();
        $this->set_version_number();
        $this->create_cron_jobs();
        $this->set_default_settings();
        (new \BetterLinksPro\Helper)->add_password_protect_page();
    }

    
    public function set_version_number()
    {
        if (get_option('betterlinks_pro_version') != BETTERLINKS_PRO_VERSION) {
            update_option('betterlinks_pro_version', BETTERLINKS_PRO_VERSION);
        }
    }
    public function create_cron_jobs()
    {
        if (class_exists('\BetterLinks\Helper')) {
            \BetterLinks\Helper::create_cron_jobs_for_json_links();
        }
    }
    public function set_default_settings()
    {
        if (!get_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME)) {
            add_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME, array(
                'is_enable_ga' => false,
                'ga_tracking_code' => '',
                'is_enable_pixel' => false,
                'pixel_id' => '',
                'pixel_access_token' => '',
            ));
        }
        if (!get_option(BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME)) {
            add_option(BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME, wp_json_encode(array(
                'enable_scan' => '',
                'scan_mode' => 'weekly',
                'scan_day' => 'sunday',
                'scan_time' => '12:00',
            )));
        }
        if (!get_option(BETTERLINKS_PRO_REPORTING_OPTION_NAME)) {
            add_option(BETTERLINKS_PRO_REPORTING_OPTION_NAME, wp_json_encode(array(
                'enable_reporting' => true,
                'email' => get_option('admin_email'),
                'email_subject' => 'Summarized Report Of Broken Links On Your Site.',
            )));
        }
        if( !get_option(BETTERLINKS_PRO_AUTO_LINK_CREATE_OPTION_NAME) ) {
            add_option( BETTERLINKS_PRO_AUTO_LINK_CREATE_OPTION_NAME, wp_json_encode([
                'post_shortlinks' => false,
                'post_default_cat' => 0,
                'page_shortlinks' => false,
                'page_default_cat' => 0,
            ]) );
        }
        if( !get_option(BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS) ) {
            add_option( BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS, wp_json_encode([]) );
        }
    }
}

<?php


namespace NotificationXPro\Admin\Reports;

use NotificationX\Admin\Reports\ReportEmail as ReportEmailFree;

class ReportEmail extends ReportEmailFree {
    public function __construct() {
        parent::__construct();
        if( isset( $this->settings['disable_reporting'] ) && ! $this->settings['disable_reporting'] ) {
            add_action('admin_init', array( $this, 'set_reporting_event' ));
            add_action('monthly_email_reporting', array( $this, 'send_email_monthly' ));
            add_action('daily_email_reporting', array( $this, 'send_email_daily' ));
        }
    }


    public function send_email_monthly(){
        return $this->send_email_weekly( 'nx_monthly' );
    }
    public function send_email_daily(){
        return $this->send_email_weekly( 'nx_daily' );
    }

    public function set_reporting_event(){
        if( isset( $this->settings['enable_analytics'] ) && ! $this->settings['enable_analytics'] ) {
            return;
        }

        if( $this->reporting_frequency() === 'nx_daily' ) {
            $datetime = strtotime( "+1days 9AM" );
            $this->mail_report_deactivation( 'weekly_email_reporting' );
            $this->mail_report_deactivation( 'monthly_email_reporting' );
            if ( ! wp_next_scheduled ( 'daily_email_reporting' ) ) {
                wp_schedule_event( $datetime, $this->reporting_frequency(), 'daily_email_reporting' );
            }
        }
        if( $this->reporting_frequency() === 'nx_monthly' ) {
            $datetime = strtotime( "first day of next month 9AM" );
            $this->mail_report_deactivation( 'daily_email_reporting' );
            $this->mail_report_deactivation( 'weekly_email_reporting' );
            if ( ! wp_next_scheduled ( 'monthly_email_reporting' ) ) {
                wp_schedule_event( $datetime, $this->reporting_frequency(), 'monthly_email_reporting' );
            }
        }
    }

}
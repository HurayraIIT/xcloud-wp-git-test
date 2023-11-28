<?php
namespace NotificationXPro\Admin;

use NotificationX\Admin\Cron as CronFree;
use NotificationX\GetInstance;

/**
 * This class is responsible for Cron Jobs
 * for NotificationX & NotificationX Pro
 */
class Cron extends CronFree {

    public function __construct(){
        parent::__construct();

    }

    /**
     * This method is responsible for cron schedules
     *
     * @param array $schedules
     * @return array
     * @since 1.1.3
     */
    public function cron_schedule($schedules) {

        $sales_count_cache_duration      = Settings::get_instance()->get('settings.sales_count_cache_duration', 3);
        $convertkit_cache_duration       = Settings::get_instance()->get('settings.convertkit_cache_duration', 3);
        $freemius_cache_duration         = Settings::get_instance()->get('settings.freemius_cache_duration', 3);
        $mailchimp_cache_duration        = Settings::get_instance()->get('settings.mailchimp_cache_duration', 3);
        $envato_cache_duration           = Settings::get_instance()->get('settings.envato_cache_duration', 3);             // @since 1.1.4
        $ga_cache_duration               = Settings::get_instance()->get('settings.ga_cache_duration', 30);                // @since 1.1.4
        $nx_google_review_cache_duration = Settings::get_instance()->get('settings.nx_google_review_cache_duration', 30);
        $nx_youtube_cache_duration       = Settings::get_instance()->get('settings.google_youtube_cache_duration', 30);

        $schedules['nx_convertkit_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $convertkit_cache_duration,
            // translators: %d: CRON job interval in minute.
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $convertkit_cache_duration, 'notificationx-pro'), $convertkit_cache_duration)
        );

        $schedules['nx_freemius_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $freemius_cache_duration,
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $freemius_cache_duration, 'notificationx-pro'), $freemius_cache_duration)
        );

        $schedules['nx_mailchimp_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $mailchimp_cache_duration,
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $mailchimp_cache_duration, 'notificationx-pro'), $mailchimp_cache_duration)
        );
        // @since 1.1.4
        $schedules['nx_envato_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $envato_cache_duration,
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $envato_cache_duration, 'notificationx-pro'), $envato_cache_duration)
        );
        // @since 1.2.11
        $schedules['nx_ga_cache_duration'] = array(
            'interval'    => MINUTE_IN_SECONDS * $ga_cache_duration,
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $ga_cache_duration, 'notificationx-pro'), $ga_cache_duration)
        );

        $schedules['nx_youtube_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $nx_youtube_cache_duration,
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $nx_youtube_cache_duration, 'notificationx-pro'), $nx_youtube_cache_duration)
        );
        $schedules['nx_google_review_cache_duration'] = array(
            'interval'    => MINUTE_IN_SECONDS * $nx_google_review_cache_duration,
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $nx_google_review_cache_duration, 'notificationx-pro'), $nx_google_review_cache_duration)
        );
        // @since 1.5.0
        $schedules['nx_sales_count_interval'] = array(
            'interval'    => MINUTE_IN_SECONDS * $sales_count_cache_duration,
            'display'    => sprintf(_n('Every %d minute', 'Every %d minutes', $sales_count_cache_duration, 'notificationx-pro'), $sales_count_cache_duration)
        );
        $schedules = parent::cron_schedule($schedules);
        return $schedules;
    }
}

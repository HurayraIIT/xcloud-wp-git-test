<?php

/**
 * Register Global Fields
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Core;

use NotificationX\Core\QuickBuild as QuickBuildFree;

/**
 * QuickBuild Class
 */
class QuickBuild extends QuickBuildFree {
    public function __construct(){
        parent::__construct();
        add_filter('nx_source_types_title', [$this, 'types_title']);
    }

    public function types_title( $titles ){
        $titles['custom'] = __('Custom Notification', 'notificationx-pro');
        $titles['page_analytics'] = __('Page Analytics', 'notificationx-pro');
        $titles['email_subscription'] = __('Email Subscription', 'notificationx-pro');
        $titles['flashing_tab'] = __('Flashing Tab', 'notificationx-pro');
        return $titles;
    }
}
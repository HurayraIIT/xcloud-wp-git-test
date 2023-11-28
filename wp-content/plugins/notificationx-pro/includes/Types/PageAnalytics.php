<?php
/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Types;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\Types\PageAnalytics as PageAnalyticsFree;
/**
 * Extension Abstract for all Extension.
 */
class PageAnalytics extends PageAnalyticsFree {

    public $default_theme = 'page_analytics_pa-theme-one';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_filter('nx_sound_options', [$this, 'sound_options']);
        add_filter('nx_notification_template', [$this, 'pa_custom_fields'], 7);

    }

    public function sound_options($options) {
        $options = GlobalFields::get_instance()->normalize_fields(
                [
                    'sales-one'    => __('Sound One', 'notificationx-pro'),
                    'sales-two'    => __('Sound Two', 'notificationx-pro'),
                ],
                'type',
                $this->id,
                $options
            );
        return $options;
    }

    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function pa_custom_fields($template) {
        $template["ga_fourth_param"] = [
            'name'     => "ga_fourth_param",
            'type'     => "text",
            'priority' => 30,
            'default'  => __('in last ', 'notificationx-pro'),
            // 'rules'    => Rules::is('source', $this->id),
        ];

        $template["ga_fifth_param"] = [
            'name'     => "ga_fifth_param",
            'type'     => "text",
            'priority' => 40,
            'default'  => __('7 ', 'notificationx-pro'),
            'rules'    => Rules::is('notification-template.first_param', 'tag_realtime_siteview', true),
        ];

        $template["sixth_param"] = Rules::is('notification-template.first_param', 'tag_realtime_siteview', true, $template["sixth_param"]);

        return $template;
    }

}
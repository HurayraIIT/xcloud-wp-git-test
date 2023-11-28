<?php

/**
 * Freemius Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Freemius;

use NotificationX\Core\Helper;
use NotificationX\GetInstance;
use NotificationX\Core\Rules;
use NotificationX\Extensions\Extension;
use NotificationX\Extensions\Freemius\FreemiusStats as FreemiusStatsFree;

/**
 * FreemiusStats Extension
 */
class FreemiusStats extends FreemiusStatsFree {
    use Freemius;
    public $cron_schedule = 'nx_freemius_interval';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        $this->construct();
    }

    public function source_error_message($messages) {
        if ( empty( $this->dev_id ) || empty( $this->dev_public_key ) || empty( $this->dev_secret_key )) {
            $url = admin_url('admin.php?page=nx-settings&tab=tab-api-integrations#freemius_settings_section');
            $messages[$this->id] = [
                'message' => sprintf( '%s <a href="%s" target="_blank">%s</a>.',
                    __( 'You have to setup your Dev ID, Public Key, Secret Key from', 'notificationx-pro' ),
                    $url,
                    __( 'settings', 'notificationx-pro' )
                ),
                'html' => true,
                'type' => 'error',
                'rules' => Rules::is('source', $this->id),
            ];
        }
        return $messages;
    }

    public function fallback_data($data, $saved_data, $settings) {
        if (isset($data['name'])) {
            unset($data['name']);
        }
        $data['today']     = __('0 times today', 'notificationx-pro');
        $data['last_week'] = __('0 times in last 7 days', 'notificationx-pro');
        $data['all_time']  = __('0 times', 'notificationx-pro');

        $data['today_text']     = __('Try it out', 'notificationx-pro');
        $data['last_week_text'] = __('Get started free.', 'notificationx-pro');
        $data['all_time_text']  = __('why not you?', 'notificationx-pro');

        if(!empty($saved_data['name'])){
            $data['plugin_theme_name'] = $saved_data['name'];
        }

        return $data;
    }

    public function get_stats($item_id, $type = '', $plugin_name = '') {
        if (!$item_id) {
            return [];
        }
        $item_stats = $this->freemius()->Api("/plugins/$item_id/installs.json");
        $total_stats = $this->freemius()->Api("/plugins.json");

        return $this->get_stats_ready($total_stats, $item_stats, $type, $item_id, $plugin_name);
    }

    public static function get_stats_ready($total_stats, $item_stats, $type, $item_id) {
        $total_stats_results = Helper::get_theme_or_plugin_list($total_stats);
        $total_stats_results = isset($total_stats_results[$type . 's'], $total_stats_results[$type . 's'][$item_id]) ? $total_stats_results[$type . 's'][$item_id] : [];
        if (isset($total_stats_results['active_installs_count'])) {
            $total_stats_results['active_installs'] = $total_stats_results['active_installs_count'];
            unset($total_stats_results['active_installs_count']);
        }
        if (isset($total_stats_results['installs_count'])) {
            $total_stats_results['all_time'] = $total_stats_results['installs_count'];
            unset($total_stats_results['installs_count']);
        }
        if (isset($total_stats_results['title'])) {
            $total_stats_results['name'] = $total_stats_results['title'];
            unset($total_stats_results['title']);
        }
        if (isset($total_stats_results['timestamp'])) {
            unset($total_stats_results['timestamp']);
        }
        $today_to_last_week = Helper::today_to_last_week($item_stats->installs);
        return array_merge($total_stats_results, $today_to_last_week);
    }

}

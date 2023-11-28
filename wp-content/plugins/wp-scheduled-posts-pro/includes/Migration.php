<?php

namespace WPSP_PRO;

class Migration { 
    public static function get_manage_schedule_settings()
    {
        global $wpdb;
        $prefix = 'psm_';
        if ($wpdb->get_var("SHOW TABLES LIKE 'psm_manage_schedule'") !== 'psm_manage_schedule') {
            $prefix = $wpdb->prefix;
        }
        $manage_schedule = $prefix . "manage_schedule";
        if ($wpdb->get_var("SHOW TABLES LIKE '$manage_schedule'") === $manage_schedule) {
            return $wpdb->get_results("SELECT * FROM {$manage_schedule}", "ARRAY_A");
        }
        return;
    }
    public static function version_2_to_4 (){
        if(get_option('wpsp_pro_data_migration_2_to_4') == false){
            $settings = json_decode(get_option(WPSP_PRO_SETTINGS_NAME));
            if (isset($settings->manage_schedule)) {
                // auto schedule
                $old_auto_schedule = get_option('manage-schedule.php');
                if ($old_auto_schedule !== false && is_array($old_auto_schedule) && count($old_auto_schedule) > 0) {
                    if (isset($old_auto_schedule['pts_0'])) {
                        $settings->manage_schedule->auto_schedule[1]->sunday_post_limit = $old_auto_schedule['pts_0'];
                    }
                    if (isset($old_auto_schedule['pts_1'])) {
                        $settings->manage_schedule->auto_schedule[2]->monday_post_limit = $old_auto_schedule['pts_1'];
                    }
                    if (isset($old_auto_schedule['pts_2'])) {
                        $settings->manage_schedule->auto_schedule[3]->tuesday_post_limit = $old_auto_schedule['pts_2'];
                    }
                    if (isset($old_auto_schedule['pts_3'])) {
                        $settings->manage_schedule->auto_schedule[4]->wednesday_post_limit = $old_auto_schedule['pts_3'];
                    }
                    if (isset($old_auto_schedule['pts_4'])) {
                        $settings->manage_schedule->auto_schedule[5]->thursday_post_limit = $old_auto_schedule['pts_4'];
                    }
                    if (isset($old_auto_schedule['pts_5'])) {
                        $settings->manage_schedule->auto_schedule[6]->friday_post_limit = $old_auto_schedule['pts_5'];
                    }
                    if (isset($old_auto_schedule['pts_6'])) {
                        $settings->manage_schedule->auto_schedule[7]->saturday_post_limit = $old_auto_schedule['pts_6'];
                    }
                    // start time & end time
                    if (isset($old_auto_schedule['pts_start'])) {
                        $settings->manage_schedule->auto_schedule[8]->start_time = $old_auto_schedule['pts_start'];
                    }
                    if (isset($old_auto_schedule['pts_end'])) {
                        $settings->manage_schedule->auto_schedule[9]->end_time = $old_auto_schedule['pts_end'];
                    }
                }

                // manual schedule
                $old_manual_schedule = self::get_manage_schedule_settings();
                if (is_array($old_manual_schedule) && count($old_manual_schedule) > 0) {
                    $weekdata = [];
                    foreach ($old_manual_schedule as $schedule_item) {
                        $weekdata[$schedule_item['day']][] = $schedule_item['schedule'];
                    }
                    $settings->manage_schedule->manual_schedule[1]->weekdata = $weekdata;
                }
                // auto schedule and manual schedule active
                $auto_schedule_status = get_option('pub_active_option');
                $manual_schedule_status = get_option('cal_active_option');
                if ($auto_schedule_status == 'ok') {
                    $settings->manage_schedule->activeScheduleSystem = 'auto_schedule';
                } else if ($manual_schedule_status == 'ok') {
                    $settings->manage_schedule->activeScheduleSystem = 'manual_schedule';
                } else {
                    $settings->manage_schedule->activeScheduleSystem = 'auto_schedule';
                }
                update_option( 'wpsp_pro_data_migration_2_to_4', true );
            }
            // miss schedule
            $miss_schedule = get_option('miss_schedule_active_option');
            if ($miss_schedule == 'yes') {
                $settings->is_active_missed_schedule = true;
            }
            if (!empty($settings)) {
                update_option(WPSP_PRO_SETTINGS_NAME, json_encode($settings));
            }
        }
    }
}
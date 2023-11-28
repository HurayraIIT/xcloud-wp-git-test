<?php

namespace WPSP_PRO;


class Helper
{
    /**
     * Number of future schedule dates to show.
     * @var integer
     */
    public static $numberOfListItem = 5;
    /**
     * All future post date
     * @return void
     */
    public static function future_post()
    {
        $future_post_date = array();
        $postType = (isset($_GET['post_type']) ? $_GET['post_type'] : 'post');
        global $wpdb;
        $posts = get_posts(array(
            'post_type' => $postType,
            'posts_per_page' => '-1',
            'post_status' => 'future'
        ));
        $dates = [];
        foreach ($posts as $post) :
            $date = get_the_date('Y-m-d H:i:s', $post->ID);
            $date_timestamp = strtotime($date);
            $dates[$date_timestamp] = $date;
        endforeach;
        wp_reset_postdata();
        wp_reset_query();

        return $dates;
    }
    /**
     * All schedule of current week.
     * @return void
     */
    public static function current_week_schedule()
    {
        $day_schedules     = self::get_settings('manage_schedule');
        $day_schedules = (isset($day_schedules->manual_schedule->weekdata) ? $day_schedules->manual_schedule->weekdata : '');
        $all_day_schedule = array();

        $current_time = current_time('timestamp');
        $today_name = date("l", $current_time);
        $today_date_time = date("Y-m-d H:i:s", $current_time);

        if (is_object($day_schedules)) :
            foreach ($day_schedules as $day => $day_schedule_time_lists) {
                if (strtolower($today_name) === $day) {
                    foreach ($day_schedule_time_lists as $day_schedule_time) {
                        $next_schedule = date("Y-m-d") . " " . $day_schedule_time;
                        $next_schedule_timestamp = strtotime($next_schedule);
                        $next_schedule = date("Y-m-d H:i:s", $next_schedule_timestamp);
                        $today_timestamp = strtotime($today_date_time);

                        if ($next_schedule_timestamp > $today_timestamp) {
                            $all_day_schedule[$next_schedule_timestamp] = [
                                'label' => date('l, F j, Y \a\t g:i a', $next_schedule_timestamp), // //Thursday, January 17, 2019 at 8:00 am
                                'date' => $next_schedule,
                                'status' => 'future',
                                'date_gmt' => get_gmt_from_date($next_schedule, 'Y-m-d H:i:s')
                            ];
                        }
                        else{
                            $next_day_schedule_timestamp = strtotime("Next " . $day . " " . $day_schedule_time);
                            $next_day_schedule = date("Y-m-d H:i:s", $next_day_schedule_timestamp);
                            $all_day_schedule[$next_day_schedule_timestamp] = [
                                'label' => date('l, F j, Y \a\t g:i a', $next_day_schedule_timestamp),
                                'date' => $next_day_schedule,
                                'status' => 'future',
                                'date_gmt' => get_gmt_from_date($next_day_schedule, 'Y-m-d H:i:s')
                            ];
                        }
                    }
                } else {
                    foreach ($day_schedule_time_lists as $day_schedule_time) {
                        $next_day_schedule_timestamp = strtotime("Next " . $day . " " . $day_schedule_time);
                        $next_day_schedule = date("Y-m-d H:i:s", $next_day_schedule_timestamp);
                        $all_day_schedule[$next_day_schedule_timestamp] = [
                            'label' => date('l, F j, Y \a\t g:i a', $next_day_schedule_timestamp),
                            'date' => $next_day_schedule,
                            'status' => 'future',
                            'date_gmt' => get_gmt_from_date($next_day_schedule, 'Y-m-d H:i:s')
                        ];
                    }
                }
            }
            ksort($all_day_schedule);
        endif;

        return $all_day_schedule;
    }

    public static function next_day_manual_schedule_max_posts()
    {
        $day_schedules     = self::get_settings('manage_schedule');
        $day_schedules = (isset( $day_schedules->manual_schedule->weekdata ) ? $day_schedules->manual_schedule->weekdata  : '');
        $all_day_schedule = array();

        $current_time = current_time('timestamp');
        $today_name = date("l", $current_time);
        $today_date_time = date("Y-m-d H:i:s", $current_time);

        if (is_array($day_schedules)) :
            foreach ($day_schedules as $day_schedule) {
                if (strtolower($today_name) === strtolower($day_schedule['day'])) {
                    $next_schedule = date("Y-m-d") . " " . $day_schedule['schedule'];
                    $next_schedule_timestamp = strtotime($next_schedule);
                    $next_schedule = date("Y-m-d H:i:s", $next_schedule_timestamp);
                    $today_timestamp = strtotime($today_date_time);
                    if ($next_schedule_timestamp > $today_timestamp) {
                        $all_day_schedule[$next_schedule_timestamp] = [
                            'label' => date('l, F j, Y \a\t g:i a', $next_schedule_timestamp), // //Thursday, January 17, 2019 at 8:00 am
                            'date' => $next_schedule,
                            'status' => 'future',
                            'date_gmt' => get_gmt_from_date($next_schedule, 'Y-m-d H:i:s')
                        ];
                    }
                } else {
                    $next_day_schedule_timestamp = strtotime("Next " . $day_schedule['day'] . " " . $day_schedule['schedule']);
                    $next_day_schedule = date("Y-m-d H:i:s", $next_day_schedule_timestamp);
                    $all_day_schedule[$next_day_schedule_timestamp] = [
                        'label' => date('l, F j, Y \a\t g:i a', $next_day_schedule_timestamp),
                        'date' => $next_day_schedule,
                        'status' => 'future',
                        'date_gmt' => get_gmt_from_date($next_day_schedule, 'Y-m-d H:i:s')
                    ];
                }
            }
            ksort($all_day_schedule);
        endif;

        return array(
            'max_post'    => count($all_day_schedule),
            'time'        => $current_time
        );
    }

    /**
     * Generate next schedule for schedule post.
     * @return void
     */
    public static function manual_schedule()
    {
        $current_week_new = $deserved_dates = [];
        $current_week = self::current_week_schedule();
        $future_post = self::future_post();
        $future_post_date_keys = array_keys($future_post);
        $future_post_count = count($future_post);
        $future_post_count = ($future_post_count == 0 ? 2 : $future_post_count) * 2;

        for ($i = 1; $i <= $future_post_count; $i++) {
            $days = $i * 7;
            foreach ($current_week as $date_timestamp => $date) {
                $new_date_timestamp = strtotime(\date('Y-m-d H:i:s', $date_timestamp) . ' +' . $days . ' day');
                $new_date = date('Y-m-d H:i:s', $new_date_timestamp);
                $current_week_new[$new_date_timestamp] = [
                    'label' => \date('l, F j, Y \a\t g:i a', $new_date_timestamp),
                    'date' => $new_date,
                    'status' => 'future',
                    'date_gmt' => \get_gmt_from_date($new_date, 'Y-m-d H:i:s')
                ];
            }
        }

        $dateIterator = 1;
        $current_week_new = $current_week + $current_week_new;
        foreach ($current_week_new as $date_timestamp => $date) {
            if (!in_array($date_timestamp, $future_post_date_keys) && $dateIterator <= self::$numberOfListItem) {
                $deserved_dates[$date_timestamp] = $date;
                $dateIterator++;
            }
        }
        return $deserved_dates;
    }

    /**
     * Auto Schedule
     */

    public static function next_day_auto_schedule_max_posts($timestamp, $endMinute)
    {
        $manage_schedule = self::get_settings('manage_schedule');
        $auto_schedule   = $manage_schedule->auto_schedule;
        $day_int_val     = date('w', $timestamp);
        $min_int_val     = date('H', $timestamp) * 60 + date('i', $timestamp);
        $day_name        = strtolower(date('l', $timestamp)) . '_post_limit';
        $max_post        = (isset($auto_schedule->{$day_name}) ? $auto_schedule->{$day_name} : 0);
        $iterator        = 0;
        $date            = date('Y-m-d', $timestamp);
        $startDate       = date('Y-m-d', strtotime(current_time('mysql') . ''));

        //
        if ($date == $startDate && $min_int_val > $endMinute) {
            $iterator++;
            $max_post = 0;
        }
        $_timestamp = $timestamp;
        while ($iterator < 8 && $max_post <= 0) {
            $_timestamp  = strtotime(date('y-m-d', $timestamp) . ' +' . $iterator++ . ' Days ');
            $day_int_val = date('w', $_timestamp);
            $day_name    = strtolower(date('l', $_timestamp)) . '_post_limit';
            $max_post    = (isset($auto_schedule->{$day_name}) ? $auto_schedule->{$day_name} : 0);
        }

        if ($max_post) {
            return array(
                'max_post' => $max_post,
                'time' => $_timestamp,
            );
        }
        return false;
    }

    public static function auto_schedule($timestamp = '', $iterator = 0)
    {
        $manage_schedule = self::get_settings('manage_schedule');
        if (!isset($manage_schedule->auto_schedule)) {
            return false;
        }
        $auto_schedule = $manage_schedule->auto_schedule;
        //# get start and end minutes from 0 to 1440-1
        $startMinute = (isset($auto_schedule->start_time) ? strtotime($auto_schedule->start_time) : strtotime("00:00"));
        $startMinute = date('H', $startMinute) * 60 + date('i', $startMinute);
        $endMinute = (isset($auto_schedule->end_time) ? strtotime($auto_schedule->end_time) : strtotime("23:59"));
        $endMinute = date('H', $endMinute) * 60 + date('i', $endMinute);
        if (empty($timestamp)) {
            $timestamp = strtotime(current_time('mysql'));
        }
        $next_day_posts = self::next_day_auto_schedule_max_posts($timestamp, $endMinute);

        if (!$next_day_posts) {
            return false;
        }

        $max_post = $next_day_posts['max_post'];
        $n_post_in_day = 0;
        $max_post_day_time = $next_day_posts['time'];

        $date = date('Y-m-d', $max_post_day_time);

        $startDate = date('Y-m-d', strtotime(current_time('mysql') . ''));

        $postType = (isset($_GET['post_type']) ? $_GET['post_type'] : 'post');

        $future_posts = \get_posts(array(
            'post_type' => $postType,
            'posts_per_page' => -1,
            'post_status' => array('future', 'publish'),
            'orderby' => 'post_date',
            'order' => 'ASC',
            'date_query' => array(
                'after' => $startDate,
                'inclusive' => true
            )
        ));

        foreach ($future_posts as $future_post) :
            if (get_the_date('Y-m-d', $future_post->ID) === date('Y-m-d', $max_post_day_time)) {
                $n_post_in_day++;
            }
        endforeach;
        wp_reset_postdata();
        wp_reset_query();

        if ($date === $startDate) {
            $nowLocal = current_time('mysql', $gmt = 0);
            $nowTotalMinutes = date('H', strtotime($nowLocal)) * 60 + date('i', strtotime($nowLocal));
            if ($nowTotalMinutes >= $startMinute) {
                $startMinute = date('H:i', strtotime("+" . rand(10, 50) . " minutes", strtotime($nowLocal)));
                $_startMinute = date('H', strtotime($startMinute)) * 60 + date('i', strtotime($startMinute));
                $minutePublish = $_startMinute > $endMinute ? (intval($endMinute / 60) . ':' . $endMinute % 60) : $startMinute;
                $auto_date = date("Y-m-d", $max_post_day_time) . ' ' . $minutePublish;
            } else {
                $minutePublish = rand($startMinute, $endMinute);
                if ($minutePublish == 0) {
                    $minutePublish += 1;
                }
                $auto_date = date("Y-m-d", $max_post_day_time) . ' ' . intval($minutePublish / 60) . ':' . $minutePublish % 60;
            }
        } else {
            $minutePublish = rand($startMinute, $endMinute);
            if ($minutePublish == 0) {
                $minutePublish += 1;
            }
            $auto_date = date("Y-m-d", $max_post_day_time) . ' ' . intval($minutePublish / 60) . ':' . $minutePublish % 60;
        }

        // || ($date == $startDate && $min_int_val > $endMinute)
        if ($n_post_in_day < $max_post) {
            $new_date_timestamp = strtotime($auto_date);

            $new_date = date('Y-m-d H:i:s', $new_date_timestamp);
            return array(
                'label' => date('l, F j, Y \a\t g:i a', $new_date_timestamp),
                'date' => $new_date,
                'status' => 'future',
                'date_gmt' => get_gmt_from_date($new_date, 'Y-m-d H:i:s')
            );
        } else {
            $iterator++;
            $time = strtotime(date('Y-m-d',  $max_post_day_time) . ' +1 Days');
            return self::auto_schedule($time, $iterator);
        }
    }

    public static function get_settings($key)
    {
        global $wpsp_settings_v5;
        if (isset($wpsp_settings_v5->{$key})) {
            return $wpsp_settings_v5->{$key};
        }
        return;
    }
}

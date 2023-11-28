<?php

namespace WPSP_PRO\Scheduled;

class Missed
{
	public function __construct()
	{
		add_action('wp_head', [$this, 'publish_miss_schedule_posts']);
	}
	public function publish_miss_schedule_posts()
	{
		if (\WPSP_PRO\Helper::get_settings('is_active_missed_schedule') !== true) return;
		if (is_front_page() || is_single()) {
			global $wpdb;
			$now = date_i18n('Y-m-d H:i:00', current_time('timestamp'));
			$post_types = get_post_types(array(
				'public'                => true,
				'exclude_from_search'   => false,
				'_builtin'              => false
			), 'names', 'and');

			$post_type = implode('\',\'', $post_types);
			if ($post_type) {
				$sql = "Select ID from $wpdb->posts WHERE post_type in ('post','page','$post_type') AND post_status='future' AND post_date < '$now'";
			} else {
				$sql = "Select ID from $wpdb->posts WHERE post_type in ('post','page') AND post_status='future' AND post_date < '$now'";
			}
			$schedulePosts = $wpdb->get_results($sql);
			if ($schedulePosts) {
				foreach ($schedulePosts as $schedulePost) {
					wp_publish_post($schedulePost->ID);
					do_action('wpsp_publish_future_post', $schedulePost->ID);
				}
			}
		}
	}
}

<?php

namespace WPSP_PRO\Scheduled;

use WPSP_PRO\Helper;

class Manage
{
	public function __construct()
	{
		$this->auto_manual_scheduled();
		$this->schedule_republish_and_unpublish_metabox();
		// new cron job
		add_action('edit_post', array($this, 'create_cron_job_for_reschedule'), 99);
		add_action( 'wpsp_el_action', array( $this, 'create_cron_job_for_reschedule' ), 99);
		add_action('wpscp_pro_schedule_republish', array($this, 'schedule_republish'));
		add_action('wpscp_pro_schedule_unpublish', array($this, 'schedule_unpublish'));
        // the following action fix the issue of cron not getting saved on first post publish.
		add_action('wpsp_pro_update_post', array($this, 'set_cron_for_unpublish_republish'));


        add_filter( 'display_post_states', function($post_states, $post){
			$republish_date = get_post_meta(get_the_ID(), '_wpscp_schedule_republish_date', true);
            if(!empty($republish_date)){
                $post_states['republish'] = _x( 'Republish', 'post status', 'wp-scheduled-posts-pro' );
            }

            return $post_states;
        }, 9, 2 );
	}

	public function set_cron_for_unpublish_republish( $post_id ) {

		if (empty($post_id)) {
			return;
		}
		$post_id_payload = array('post_id' => $post_id);

		// get gmt timezone
		$offset = get_option('gmt_offset');
		$gmtOffset = (($offset == 0) ? $offset : (-get_option('gmt_offset')));

		$unpublish_date = get_post_meta($post_id, '_wpscp_schedule_draft_date', true);

		if (!empty( $unpublish_date)) {
			$new_unpublish_date = date("Y-m-d H:i:s", strtotime("$gmtOffset hours", strtotime($unpublish_date)));

			$unpublish_timestamp = strtotime($new_unpublish_date);
			wp_clear_scheduled_hook('wpscp_pro_schedule_unpublish', $post_id_payload);
			wp_schedule_single_event($unpublish_timestamp, 'wpscp_pro_schedule_unpublish', $post_id_payload);
		}else{
			wp_clear_scheduled_hook('wpscp_pro_schedule_unpublish', $post_id_payload);
			//// if the date unpublish date is empty, then remove cron if already cron is running
			$rm_unpub_timestamp = wp_next_scheduled( 'wpscp_pro_schedule_unpublish' , $post_id_payload);
			if ( !empty( $rm_unpub_timestamp) ) {
				wp_unschedule_event( $rm_unpub_timestamp, 'wpscp_pro_schedule_unpublish', $post_id_payload);

			}
		}

		// republish
		$republish_date = get_post_meta($post_id, '_wpscp_schedule_republish_date', true);
		if (!empty( $republish_date)) {
			$new_republish_date = date("Y-m-d H:i:s", strtotime("$gmtOffset hours", strtotime($republish_date)));
			$republish_timestamp = strtotime($new_republish_date);
			wp_clear_scheduled_hook('wpscp_pro_schedule_republish',  $post_id_payload);
			wp_schedule_single_event($republish_timestamp, 'wpscp_pro_schedule_republish', $post_id_payload);
			// add reshare event for facebook, twitter, linedin and pinterest
			$share_date = new \DateTime($republish_date);
			$share_date->add(new \DateInterval('PT1M'));
			$share_date = $share_date->format('Y-m-d H:i');
			$socialshare_date = date("Y-m-d H:i:s", strtotime("$gmtOffset hours", strtotime($share_date)));
			$socialshare_timestamp = strtotime($socialshare_date);
			wp_clear_scheduled_hook('wpscp_pro_schedule_republish_share',  $post_id_payload);
			wp_schedule_single_event($socialshare_timestamp, 'wpscp_pro_schedule_republish_share', array('post_id' => $post_id));
		}else{
			//if the date republish date is empty, then remove cron if already cron is running
			wp_clear_scheduled_hook('wpscp_pro_schedule_republish',  $post_id_payload);
			wp_clear_scheduled_hook('wpscp_pro_schedule_republish_share',  $post_id_payload);
			$rm_repub_timestamp = wp_next_scheduled( 'wpscp_pro_schedule_republish' , $post_id_payload);
			$rm_social_share_timestamp = wp_next_scheduled( 'wpscp_pro_schedule_republish_share' , $post_id_payload);
			if ( !empty( $rm_repub_timestamp) ) {
				wp_unschedule_event( $rm_repub_timestamp, 'wpscp_pro_schedule_republish', $post_id_payload);
			}
			if ( !empty( $rm_social_share_timestamp) ) {
				wp_unschedule_event( $rm_social_share_timestamp, 'wpscp_pro_schedule_republish_share', $post_id_payload);
			}
		}
    }
	/**
	 * Manual Scheduled
	 */
	public function auto_manual_scheduled()
	{
		// classic editor add submitbox
		add_action('post_submitbox_misc_actions', array($this, 'auto_manual_schedule_optionsbox'));
	}
	public function auto_manual_schedule_optionsbox()
	{
		global $post;
		# do not show info for published posts...
		if ($post->post_status == 'publish') {
			return;
		}
		# do not show info for scheduled posts...
		if ($post->post_status == 'future') {
			return;
		}
		# do not show info if current post type is not in selected option...
		$current_post_type = $post->post_type;
		$allow_post_types = Helper::get_settings('allow_post_types');
		$allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
		if (!in_array($current_post_type, $allow_post_types)) {
			return;
		}
		print '<div id="autoManualScheduleSelectBox"></div>';
	}



	/**
	 * Schedule Republish
	 */
	public function schedule_republish_and_unpublish_metabox()
	{
		add_action('add_meta_boxes', array($this, 'schedule_republish_metabox'));
		add_action('save_post', array($this, 'schedule_republish_data_save'), 10, 2);
	}

	public function schedule_republish_metabox()
	{
		$allow_post_types = Helper::get_settings('allow_post_types');
		$allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
		if( Helper::get_settings('post_republish_unpublish') === true ) {
			add_meta_box('wpscp_pro_schedule_republish_meta_box', __('Scheduling Options', 'wp-scheduled-posts-pro'), array($this, 'schedule_republish_metabox_markup'), $allow_post_types, 'side', 'low');
		}
	}

	public function schedule_republish_data_save($post_id, $post)
	{
		if (!did_action('wpsp_schedule_published') && (!isset($_POST['wpscp_schedule_republish_nonce']) || !wp_verify_nonce($_POST['wpscp_schedule_republish_nonce'], basename(__FILE__)))) {
			return;
		}
		//don't do anything for autosaves
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return;
		}
		//check if user has permission to edit posts otherwise don't do anything
		if (!current_user_can('edit_post', $post_id)) {
			return;
		}

		// save post meta
		if (isset($_POST['wpscp-schedule-republish-date'])) {
			update_post_meta($post_id, '_wpscp_schedule_republish_date', sanitize_text_field($_POST['wpscp-schedule-republish-date']));
		}

		if (isset($_POST['wpscp-schedule-draft-date'])) {
			update_post_meta($post_id, '_wpscp_schedule_draft_date', sanitize_text_field($_POST['wpscp-schedule-draft-date']));
		}
	}

	public function schedule_republish_metabox_markup($data_object, $box)
	{
		wp_nonce_field(basename(__FILE__), 'wpscp_schedule_republish_nonce');
		$draft_date     = get_post_meta(get_the_ID(), '_wpscp_schedule_draft_date', true);
		$republish_date = get_post_meta(get_the_ID(), '_wpscp_schedule_republish_date', true);
		$draft_date     = apply_filters('wpsp_unpublish_date', $draft_date, $data_object);
		$republish_date = apply_filters('wpsp_republish_date', $republish_date, $data_object);

?>
		<div class="wpscp-schedule-republish">
			<!-- skip share -->
            <div>
                <label>
					<?php esc_html_e('Unpublish On', 'wp-scheduled-posts-pro'); ?>
                    <a href="https://wpdeveloper.com/docs/republish-unpublish-wordpress/" class="info" target="_blank"><span class="dashicons dashicons-info"></span></a>
                    <input type="text" id="wpscp_schedule_draft_date" name="wpscp-schedule-draft-date" value="<?php print $draft_date; ?>" placeholder="<?php esc_attr_e('Y/M/D H:M:S', 'wp-scheduled-posts-pro'); ?>" />
                </label>
            </div>

			<div>
				<label>
					<?php esc_html_e('Republish On', 'wp-scheduled-posts-pro'); ?>
					<a href="https://wpdeveloper.com/docs/republish-unpublish-wordpress/" class="info" target="_blank"><span class="dashicons dashicons-info"></span></a>
					<input type="text" id="wpscp_schedule_republish_date" name="wpscp-schedule-republish-date" value="<?php print $republish_date; ?>" placeholder="<?php esc_attr_e('Y/M/D H:M:S', 'wp-scheduled-posts-pro'); ?>" />
				</label>
			</div>

		</div>
<?php
	}


	// new cron job
	public function create_cron_job_for_reschedule($post_id)
	{
		global $wp_current_filter;
		if (empty($post_id) || in_array( 'wpscp_pro_schedule_republish', $wp_current_filter)) {
			return;
		}
		$post_id_payload = array('post_id' => $post_id);
		$post_status = get_post_status($post_id);
		wp_clear_scheduled_hook('wpsp_pro_update_post', $post_id_payload);
		if ( 'trash' === $post_status ) {
			wp_clear_scheduled_hook('wpscp_pro_schedule_unpublish', $post_id_payload);
			wp_clear_scheduled_hook('wpscp_pro_schedule_republish',  $post_id_payload);
			wp_clear_scheduled_hook('wpscp_pro_schedule_republish_share',  $post_id_payload);
			return;
		}

		wp_schedule_single_event(time() + 20, 'wpsp_pro_update_post', $post_id_payload);

	}

	public function schedule_republish($post_id)
	{
        $post_status = get_post_status($post_id);
        if ( 'trash' ===  $post_status ) {
			//delete_post_meta($post_id, '_wpscp_schedule_republish_date');
			return;
        }
		$post_date = get_post_meta($post_id, '_wpscp_schedule_republish_date', true);
        $post_date = str_replace( '/', '-', $post_date);
		$updatePost = wp_update_post(array(
			'ID'    		=>  $post_id,
			'post_status'   =>  'publish',
			'post_date'		=> $post_date,
			//'post_date_gmt'	=> $post_date,
			//'edit_date' 	=> true
		));

		if (!is_wp_error($updatePost)) {
			// reset meta
			delete_post_meta($post_id, '_wpscp_schedule_republish_date');
		}

	}

	public function schedule_unpublish($post_id)
	{
		$post_status = get_post_status($post_id);

		if ( 'trash' ===  $post_status ) {
			delete_post_meta($post_id, '_wpscp_schedule_draft_date');
			return;
		}
		$updatePost = wp_update_post(array(
			'ID'    		=>  $post_id,
			'post_status'   =>  'draft',
		));
		if (!is_wp_error($updatePost)) {
			// reset meta
			delete_post_meta($post_id, '_wpscp_schedule_draft_date');
		}
	}
}

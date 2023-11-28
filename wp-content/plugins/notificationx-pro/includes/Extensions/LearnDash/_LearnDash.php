<?php

/**
 * LearnDash Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\LearnDash;

use NotificationX\Extensions\GlobalFields;
use NotificationXPro\Core\Helper;

/**
 * LearnDash Extension
 */
trait _LearnDash {
    public $post_type = 'sfwd-courses';


    public function init_fields() {
        parent::init_fields();

        add_filter('nx_elearning_course_list', [$this, 'courses']);
    }

    /**
     * Returns a list of courses that are not open access.
     *
     * @param array $course_list An array of course IDs to filter.
     * @return array An indexed array of course IDs and titles.
     */
    public function courses($course_list) {
        // Get all the courses of this post type
        $courses = Helper::get_post_titles_by_search($this->post_type);
        // Filter out the courses that are not open access
        $courses = array_filter($courses, function ($id) {
            $meta = get_post_meta($id, '_sfwd-courses', true);
            if (is_array($meta) && array_key_exists('sfwd-courses_course_price_type', $meta)) {
				return $meta['sfwd-courses_course_price_type'] != 'open';
			} else {
				return false;
			}
        }, ARRAY_FILTER_USE_KEY);
        // Normalize the fields and return as an indexed array
        return array_values(GlobalFields::get_instance()->normalize_fields($courses, 'source', $this->id, $course_list));
    }

    /**
     * Lists available tags in the selected form.
     *
     * @param array $args An array of arguments, including inputValue.
     * @return array An indexed array of form IDs and titles.
     */
    public function restResponse($args) {
        // Check if inputValue is provided
        if ( empty( $args['search_empty']) && empty($args['inputValue'] ) ) {
            return [];
        }
        // Get the forms that match the inputValue
        $forms = Helper::get_post_titles_by_search($this->post_type, $args['inputValue']);
        // Normalize the fields and return as an indexed array
        return array_values(GlobalFields::get_instance()->normalize_fields($forms, 'source', $this->id));
    }

    public function saved_post($post, $data, $nx_id) {
        $this->delete_notification(null, $nx_id);
        $this->get_notification_ready($data);
    }

    /**
     * This function is responsible for making the notification ready for first time we make the notification.
     *
    * @param string $type
     * @param array $data
     * @return void
     */
    public function get_notification_ready($data = array()) {
        if (!class_exists('LDLMS_Post_Types')) {
            return;
        }
        $enrollments = $this->get_course_enrollments($data);
        if (!empty($enrollments)) {
            $entries = [];
            foreach ($enrollments as $key => $enrollment) {
                $entries[] = [
                    'nx_id'      => $data['nx_id'],
                    'source'     => $this->id,
                    'entry_key'  => $enrollment['id'] . '-' . $enrollment['user_id'],
                    'data'       => $enrollment,
                ];
            }
            $this->update_notifications($entries);
        }
    }

    private function get_course_enrollments($data) {
        if (empty($data)) {
            return null;
        }
        global $wpdb;
        $enrollments = [];
        $from = strtotime(date(get_option('date_format'), strtotime('-' . intval($data['display_from']) . ' days')));
        $query = 'SELECT ld.user_id,ld.post_id,ld.activity_started,post.post_title FROM ' . $wpdb->prefix . 'learndash_user_activity AS ld JOIN ' . $wpdb->prefix . 'posts as post ON ld.post_id=post.ID WHERE activity_type="access" AND activity_started >' . $from . ' ORDER BY activity_started DESC';
        $results = $wpdb->get_results($query);

        if (!empty($results)) {
            foreach ($results as $result) {
                $enrollments[] = array_merge(
                    array(
                        'id'         => $result->post_id,
                        'product_id' => $result->post_id,
                        'title'      => $result->post_title,
                        'link'       => get_the_permalink($result->post_id),
                        'timestamp'  => $result->activity_started
                    ),
                    $this->get_enrolled_user($result->user_id)
                );
            }
        }
        return $enrollments;
    }

    /**
     * This function is generate and save a new notification when user enroll in a new course
     * @param int $user_id
     * @param int $course_id
     * @return void
     */
    public function save_new_enrollment($user_id, $course_id) {
        if( empty( $user_id ) || empty( $course_id ) ) {
            return;
        }
        $key = $course_id . '-' . $user_id;

        $enrollment = array_merge( $this->get_enrolled_course( $course_id ),  $this->get_enrolled_user( $user_id ) );

        $entry = [
            'source'     => $this->id,
            'entry_key'  => $key,
            'data'       => $enrollment,
        ];
        $this->save( $entry );
    }

    /**
     * Get enrolled course information
     * @param int $course_id
     * @return array
     */
    private function get_enrolled_course($course_id) {
        return array(
            'id'         => $course_id,
            'product_id' => $course_id,
            'title'      => get_the_title($course_id),
            'link'       => get_the_permalink($course_id),
            'timestamp'  => time()
        );
    }

    /**
     * Get enrolled user information
     * @param $user_id
     * @return array
     */
    private function get_enrolled_user($user_id) {
        $user_data = [];
        $user_meta = get_user_meta($user_id);
        $first_name = $user_meta['first_name'][0];
        $last_name = $user_meta['last_name'][0];
        $user_data['user_id'] = $user_id;
        if (!empty($first_name)) {
            $user_data['first_name'] = $first_name;
        } else {
            $user_data['first_name'] = $user_meta['nickname'][0];
        }
        if (!empty($last_name)) {
            $user_data['last_name'] = $last_name;
        } else {
            $user_data['last_name'] = '';
        }
        $user_data['name'] = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
        $user_data['email'] = get_userdata($user_id)->data->user_email;
        /**
         * User City and Country added
         */
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $user_data['ip'] = $_SERVER['REMOTE_ADDR'];
        }
        $user_data['email'] = get_userdata($user_id)->data->user_email;
        return $user_data;
    }

    /**
     * Image action callback
     * @param array $image_data
     * @param array $data
     * @param stdClass $settings
     * @return array
     */
    public function notification_image($image_data, $data, $settings) {
        if (!$settings['show_default_image']) {
            $image_url = '';
            switch ($settings['show_notification_image']) {
                case 'featured_image':
                    $image_url = get_the_post_thumbnail_url($data['id'], 'thumbnail');
                    break;
                case 'gravatar':
                    $image_url = get_avatar_url($data['user_id'], ['size' => '100']);
            }
            $image_data['url'] = $image_url;
        }
        return $image_data;
    }

    /**
     * @param array    $data
     * @param array    $saved_data
     * @param stdClass $settings
     * @return array
     */
    public function fallback_data($data, $saved_data, $settings) {
        $data['name']            = __('Someone', 'notificationx-pro');
        $data['first_name']      = __('Someone', 'notificationx-pro');
        $data['last_name']       = __('Someone', 'notificationx-pro');
        $data['anonymous_title'] = __('Anonymous Product', 'notificationx-pro');
        $data['course_title']    = $saved_data['title'];
        return $data;
    }

}

<?php

namespace WPSP_PRO\Scheduled;

use stdClass;
use WPSP_PRO\Helper;

class Published
{
	public function __construct()
	{
        add_filter('rest_pre_dispatch', [$this, 'schedule_published_content'], 10, 3);
        add_filter('user_has_cap', [$this, 'schedule_published_data'], 9, 4 );

        add_action('wpscp_pending_schedule', [$this, 'wpscp_pending_schedule_fn'], 10);
        add_action('after_delete_post', [$this, 'delete_post'], 10);
        add_action('trashed_post', [$this, 'delete_post'], 10);
        add_action('wpscp_calender_the_post', [$this, 'calender_the_post'], 10);
        // add_filter('wp_insert_post_empty_content', 'wp_insert_post_empty_content', 10, 2);

		$allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
		$post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));

		foreach ($post_types as $key => $post_type) {
			add_filter("rest_prepare_$post_type", [$this, 'wpscp_rest_prepare'], 10, 3);
            add_action( "rest_delete_$post_type", [$this, 'rest_delete_post'], 10, 3 );
        }


        add_filter('wpsp_admin_bar_menu_posts', [$this, 'admin_bar_menu_posts'], 10, 2 );

        add_filter('wpsp_pre_eventDrop', [$this, 'wpsp_pre_eventDrop'], 10, 4 );
        add_filter('wpsp_eventDrop_posts', [$this, 'wpsp_eventDrop_posts'], 10, 2 );
        add_filter('WPSchedulePostsData', [$this, 'WPSchedulePostsData'] );
		add_action("post_action_wpsp_delete_scheduled_data", [$this, 'delete_scheduled_data']);
        add_filter('is_sticky', [$this, 'is_sticky'], 10, 2);

        add_filter( 'display_post_states', function($post_states, $post){
            $scheduled = (array) get_post_meta($post->ID, 'wpscp_pending_schedule', true);
            if(isset($scheduled['status']) && $scheduled['status'] == 'future'){

                if ( 'publish' === $post->post_status ) {
                    $post_states['scheduled'] = _x( 'Advanced Scheduled', 'post status', 'wp-scheduled-posts-pro' );
                }
            }

            return $post_states;
        }, 9, 2 );
        add_filter( 'filter_block_editor_meta_boxes', function($wp_meta_boxes){
            $current_screen = get_current_screen();
            $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
            $post_types       = (!empty($allow_post_types) ? $allow_post_types : array());
            if(!empty($current_screen->post_type) && in_array($current_screen->post_type, $post_types)){
                add_filter("get_{$current_screen->post_type}_metadata", [$this, 'get_metadata'], 10, 5);
            }
            return $wp_meta_boxes;
        }, 9, 2 );

    }

    /**
     * Filters the pre-calculated result of a REST API dispatch request.
     *
     * Allow hijacking the request before dispatching by returning a non-empty. The returned value
     * will be used to serve the request instead.
     *
     * @since 4.4.0
     *
     * @param mixed           $result  Response to replace the requested version with. Can be anything
     *                                 a normal endpoint can return, or null to not hijack the request.
     * @param WP_REST_Server  $server  Server instance.
     * @param WP_REST_Request $request Request used to generate the response.
     */
    public function schedule_published_content($return, $server, $request){
        // return $return;
        if($request->get_method() !== 'PUT' || !preg_match("@/wp/v2/.+/\d+@", $request->get_route(), $matched)){
            return $return;
        }

        $post = $request->get_json_params();
        if(empty($post['id'])){
            return $return;
        }
        $pid  = $post['id'];

        if(!empty($post['meta']['wpsp_status']) && $post['meta']['wpsp_status'] == 'draft'){
            return $return;
        }
        // if(!empty($post['meta']['wpsp_status']) && $post['meta']['wpsp_status'] == 'delete'){
        //     delete_post_meta($pid, 'wpscp_pending_schedule');
        //     delete_post_meta($pid, 'wpscp_pending_schedule_data');
        //     $request->set_body(json_encode([
        //         'id' => $pid,
        //     ]));
        //     $post = $request->get_json_params();
        //     return $return;
        // }

        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        $post_types       = (!empty($allow_post_types) ? $allow_post_types : array());
        $published_post   = get_post( $pid, ARRAY_A );
        $_scheduled_data  = get_post_meta($pid, 'wpscp_pending_schedule', true);
        $_scheduled_data  = empty($_scheduled_data) ? [] : $_scheduled_data;
        $scheduled_data   = array_merge($_scheduled_data, $post);
        $wpsp_status      = !empty($scheduled_data['meta']['wpsp_status']) ? $scheduled_data['meta']['wpsp_status']: '';

        if($wpsp_status !== 'publish'){
            return $return;
        }

        if(isset($post['status']) && $post['status'] == 'publish'){
            $this->wpscp_pending_schedule_fn($pid);
            wp_clear_scheduled_hook('wpscp_pending_schedule', array((int) $pid));
        }
        // Publish future post immediately
        else if (
            !empty($published_post) &&
            in_array($published_post['post_type'], $post_types) &&
            in_array($published_post['post_status'], ['publish', 'private']) //&&
            // $published_post['post_status'] == 'publish' //&&
            // isset($scheduled_data['status']) &&
            // $scheduled_data['status'] == 'future'
        ) {
            $scheduled_data['user_id'] = get_current_user_id();
            update_post_meta($pid, 'wpscp_pending_schedule', $scheduled_data);

            wp_clear_scheduled_hook('wpscp_pending_schedule', array((int) $pid));
            wp_schedule_single_event(strtotime($scheduled_data['date_gmt']), 'wpscp_pending_schedule', array((int) $pid));
            $result = $this->prepare_response($pid, $scheduled_data);
            if($result){
                $controller = $this->get_controller($request);
                if(!empty($controller)){
                    $response = $this->call_reflection($controller, 'prepare_item_for_database', [$request]);
                    do_action('wpsp_transition_post_status', 'delayed_future', $published_post['post_status'], (object) array_merge($published_post, (array) $response));
                }
                return $result;
            }

        }

        return $return;
    }

    /**
     * Dynamically filter a user's capabilities.
     *
     * @param bool[]   $allcaps Array of key/value pairs where keys represent a capability name and boolean values represent whether the user has that capability.
     * @param string[] $caps    Required primitive capabilities for the requested capability.
     * @param array    $args    { Arguments that accompany the requested capability check.
     * 			@type string    $0 Requested capability.
     * 			@type int       $1 Concerned user ID.
     * 			@type mixed  ...$2 Optional second and further parameters, typically object ID.
     *	}
     * @param \WP_User $user    The user object.
     * @return bool[] Array of key/value pairs where keys represent a capability name and boolean values represent whether the user has that capability.
    */
    public function schedule_published_data( array $allcaps, array $caps, array $args, \WP_User $user ) {
        if(isset($args[0]) && $args[0] == 'edit_post'){
            $postarr          = $_POST;
            $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
            $post_types       = (!empty($allow_post_types) ? $allow_post_types : array());

            if(isset($postarr['post_ID'], $postarr['post_type'], $args[2]) && $args[2] == $postarr['post_ID'] && in_array($postarr['post_type'], $post_types)){
                $pid             = $postarr['post_ID'];
                $old_post_status = get_post_status( $pid );
                $scheduled       = get_post_meta($pid, 'wpscp_pending_schedule', true);
                //  || $old_post_status == 'future'
                if(in_array($old_post_status, ['publish', 'private']) && isset($scheduled['status']) && $scheduled['meta']['wpsp_status'] == 'publish'){
                    update_post_meta($pid, 'wpscp_pending_schedule_data', $postarr);
                    return [];
                }
            }
        }
        return $allcaps;
    }

    public function wp_insert_post_empty_content($maybe_empty, $postarr){
        // return $maybe_empty;
        $pid              = $postarr['ID'];
        $allow_post_types = \WPSP\Helper::get_settings('allow_post_types');
        $post_types       = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        if(in_array($postarr['post_type'], $post_types)){
            $old_post_status  = get_post_status( $pid );
            $scheduled = get_post_meta($pid, 'wpscp_pending_schedule', true);
            $scheduled = wp_parse_args((array) $scheduled, ['post_status' => '']);
            if(($old_post_status == 'publish' || $old_post_status == 'future') && $scheduled['post_status'] == 'future'){
                $scheduled_data = get_post_meta($pid, 'wpscp_pending_schedule_data', true);
                $scheduled_data = $scheduled_data ? $scheduled_data : [];
                // wp_send_json(["xxXxx", $maybe_empty, $postarr, debug_backtrace()]);
                update_post_meta($pid, 'wpscp_pending_schedule_data', array_merge($scheduled_data, $postarr));
                return true;
            }
        }
        // wp_send_json(["xxXxx", $maybe_empty, $postarr, debug_backtrace()]);
        return $maybe_empty;
    }


    /**
     * @todo check is_admin
     *
     * @param [type] $response
     * @param [type] $post
     * @param [type] $request
     * @return void
     */
    public function wpscp_rest_prepare($response, $post, $request){
        if(!in_array($post->post_status, ['publish', 'private'])){
            return $response;
        }
        // schedule published post.
        $scheduled = (array) get_post_meta($post->ID, 'wpscp_pending_schedule', true);
        $scheduled_data = (array) get_post_meta($post->ID, 'wpscp_pending_schedule_data', true);
        if(!empty($scheduled['id'])){
            remove_filter("rest_prepare_{$post->post_type}", [$this, 'wpscp_rest_prepare'], 10, 3);
            add_filter("get_{$post->post_type}_metadata", [$this, 'get_metadata'], 10, 5);

            $result = $this->prepare_response($scheduled['id'], array_merge($scheduled_data, $scheduled));
            if($result){
                return $result;
            }

        }

        return $response;
    }
    /**
     *
	 * @since 5.5.0 Added the `$meta_type` parameter.
     *
     * @param [type] $return
     * @param [type] $object_id
     * @param [type] $meta_key
     * @param [type] $single
     * @param [type] $meta_type
     * @return void
     */
    public function get_metadata($return, $object_id, $meta_key, $single, $meta_type = null){
        if(empty($meta_type)) return $return;
        remove_filter("get_{$meta_type}_metadata", [$this, 'get_metadata'], 10, 5);
        $scheduled         = get_post_meta($object_id, 'wpscp_pending_schedule', true);

        if(empty($scheduled)){
            return $return;
        }

        $scheduled_data    = get_post_meta($object_id, 'wpscp_pending_schedule_data', true);
        if(empty($scheduled_data)){
            return $return;
        }

        $scheduled['meta'] = isset($scheduled['meta']) ? $scheduled['meta'] : [];
        $scheduled_data    = array_merge((array) $scheduled_data, (array) $scheduled, (array) $scheduled['meta']);
        add_filter("get_{$meta_type}_metadata", [$this, 'get_metadata'], 10, 5);

        $meta_key_map = [
            '_wpscppro_custom_social_share_image' => 'wpscppro_custom_social_share_image',
            '_wpscppro_dont_share_socialmedia'    => 'wpscppro-dont-share-socialmedia',
            '_wpscppro_pinterestboardtype'        => 'pinterestboardtype',
            '_wpscppro_pinterest_board_name'      => 'wpscppro-pinterest-board-name',
            '_wpscppro_pinterest_section_name'    => 'wpscppro-pinterest-section-name',

            '_wpscp_schedule_republish_date' => 'wpscp-schedule-republish-date',
            '_wpscp_schedule_draft_date'     => 'wpscp-schedule-draft-date',
        ];
        if(isset($meta_key_map[$meta_key])){
            $meta_key = $meta_key_map[$meta_key];
            // if(!empty($scheduled_data[$meta_key])){
            //     $scheduled_data[$meta_key] = array_filter((array) $scheduled_data[$meta_key], 'sanitize_text_field');
            // }
        }

        $value = isset($scheduled_data[$meta_key]) ? $scheduled_data[$meta_key] : null;
        return array($value);
    }



    public function wpscp_pending_schedule_fn($pid){
        $scheduled      = get_post_meta($pid, 'wpscp_pending_schedule', true);
        $scheduled_data = get_post_meta($pid, 'wpscp_pending_schedule_data', true);
        if(!empty($scheduled['user_id']) && !empty($scheduled_data)){
            // $scheduled_data = array_merge($scheduled_data, $scheduled);
            // @todo maybe do something.
            $user = wp_get_current_user();
            if(empty($user->ID)){
                $user = wp_set_current_user($scheduled['user_id']);
            }

            if(!empty($user->ID)){
                // remove_filter('wp_insert_post_empty_content', 'wp_insert_post_empty_content', 10);
                do_action('wpsp_schedule_published');
                $post_type = isset($scheduled_data['post_type']) ? $scheduled_data['post_type'] : '';
                if(isset($post_type)){
                    remove_filter("rest_prepare_{$post_type}", [$this, 'wpscp_rest_prepare'], 10, 3);

                    unset($scheduled['status']);
                    // $scheduled['status'] = 'publish';
                    $route = rest_get_route_for_post( $pid );
                    $request = new \WP_REST_Request( 'PUT', $route );
                    $request->set_body_params( $scheduled );

                    update_post_meta( $pid, 'prevent_future_post', $scheduled['date'] );

                    $rest = new \WP_REST_Posts_Controller($post_type);
                    $rest->update_item($request);
                    delete_post_meta($pid, 'wpscp_pending_schedule');
                }

                require_once(trailingslashit(ABSPATH) . 'wp-admin/includes/post.php');
                remove_filter('user_has_cap', [$this, 'filter_user_has_cap'], 9, 4);
                $scheduled_data['original_post_status'] = 'publish';
                $__POST = $_POST;
                $_POST  = $scheduled_data;

                edit_post($scheduled_data);
                delete_post_meta($pid, 'wpscp_pending_schedule_data');
                do_action('wpsp_publish_future_post', $pid);

                do_action('wpsp_transition_post_status', 'delayed_publish', get_post_status($pid), $pid);
                $_POST = $__POST;

            }
        }
    }



    public function prepare_response($pid, $post){
        $response = [];
        $route = rest_get_route_for_post( $pid );
        $request = new \WP_REST_Request( 'PUT', $route );
        $published_post   = get_post( $pid, ARRAY_A );
        // unset($post['status']);
        // $post['status'] = 'custom';
        $request->set_body_params( $post );
        $request->set_param( 'context', 'edit' );
        // $request->get_json_params();

        $controller = $this->get_controller($request);
        if(empty($controller)) return null;


        $response = $this->call_reflection($controller, 'prepare_item_for_database', [$request]);
        $response = (object) array_merge($published_post, (array) $response);

        /*
        * Resolve the post date from any provided post date or post date GMT strings;
        * if none are provided, the date will be set to now.
        */
        if( isset($response->post_date, $response->post_date_gmt) ){
            $post_date = wp_resolve_post_date( $response->post_date, $response->post_date_gmt );
            if ( ! $post_date ) {
                return null;
            }

            if ( empty( $response->post_date_gmt ) || '0000-00-00 00:00:00' === $response->post_date_gmt ) {
                $response->post_date_gmt = get_gmt_from_date( $post_date );
            }

            $response->post_modified     = $post_date;
            $response->post_modified_gmt = $response->post_date_gmt;

        }



        $response     = $controller->prepare_item_for_response( $response, $request );

        $data = $response->get_data();

        if(!empty($post['post_title'])){
            $data['title']['rendered'] = apply_filters( 'the_title', $post['post_title'], $pid );
        }
        if(!empty($post['featured_media'])){
            $data['featured_media'] = $post['featured_media'];
        }
        if(!empty($post['template'])){
            $data['template'] = $post['template'];
        }
        if(!empty($post['meta'])){
            $data['meta'] = $post['meta'];
        }
        if(!empty($post['format'])){
            $data['format'] = $post['format'];
        }

		$taxonomies = wp_list_filter( get_object_taxonomies( $published_post['post_type'], 'objects' ), array( 'show_in_rest' => true ) );

		foreach ( $taxonomies as $taxonomy ) {
			$base = ! empty( $taxonomy->rest_base ) ? $taxonomy->rest_base : $taxonomy->name;

			if ( isset($post[$base]) ) {
				$data[ $base ] = $post[$base];
			}
		}

        $response->set_data($data);

        $response = rest_ensure_response( $response );

        return $response;
    }

    public function delete_post($pid){
        wp_clear_scheduled_hook('wpscp_pending_schedule', array((int) $pid));
    }
    public function rest_delete_post($post, $response, $request){
        wp_clear_scheduled_hook('wpscp_pending_schedule', array((int) $post->ID));
    }
    public function wpsp_pre_eventDrop($return, $pid, $postdateformat, $postdate_gmt){
        $scheduled = get_post_meta($pid, 'wpscp_pending_schedule', true);
        if(!empty($scheduled['user_id'])){
            $scheduled['date']     = $postdateformat;
            $scheduled['date_gmt'] = $postdate_gmt;
            update_post_meta($pid, 'wpscp_pending_schedule', $scheduled);
            wp_clear_scheduled_hook('wpscp_pending_schedule', array((int) $pid));
            wp_schedule_single_event(strtotime($postdate_gmt), 'wpscp_pending_schedule', array((int) $pid));
            return $pid;
        }

        return $return;
    }

    public function wpsp_eventDrop_posts($posts, $pid){
        foreach ($posts as $key => $published_post) {
            $post = get_post_meta($published_post->ID, 'wpscp_pending_schedule', true);
            if(!empty($post['user_id'])){
                $route = rest_get_route_for_post( $published_post->ID );
                $request = new \WP_REST_Request( 'PUT', $route );
                $request->set_body_params( $post );
                $request->set_param( 'context', 'edit' );

                $controller = $this->get_controller($request);
                if(empty($controller)) return null;

                $response = $this->call_reflection($controller, 'prepare_item_for_database', [$request]);
                $posts[$key] = (object) array_merge((array) $published_post, (array) $response);
            }
        }

        return $posts;
    }

    public function calender_the_post(){
        global $post;
        $scheduled = (array) get_post_meta(get_the_ID(), 'wpscp_pending_schedule', true);
        if(isset($scheduled['status'], $scheduled['date'], $scheduled['date_gmt'])){
            $post->post_status       = $scheduled['status'];
            $post->post_date         = $scheduled['date'];
            $post->post_date_gmt     = $scheduled['date_gmt'];
            $post->post_modified     = $scheduled['date'];
            $post->post_modified_gmt = $scheduled['date_gmt'];
        }
    }

    public function admin_bar_menu_posts($posts, $post_types){
        $published = get_posts(array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'order'          => 'ASC',
            'meta_key'       => 'wpscp_pending_schedule',
        ));
        foreach ($published as $key => $post) {
            $scheduled = (array) get_post_meta($post->ID, 'wpscp_pending_schedule', true);
            if(!empty($scheduled['date_gmt'])){
                $post->post_date = $scheduled['date'];
                $post->post_date_gmt = $scheduled['date_gmt'];
            }
            else{
                unset($published[$key]);
            }
        }
        $posts = array_merge($posts, $published);
        return $posts;
    }


    /***
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Controller || false
     */
    public function WPSchedulePostsData($return){
        $delayed_schedule = \WPSP\Helper::get_settings('is_delayed_schedule_active');
        $return['schedulePublished'] = wp_create_nonce( 'schedule_published' );
        $return['delayedSchedule'] = $delayed_schedule !== null ? $delayed_schedule : true;
        // var_dump($return);die;
        return $return;
    }

    /***
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Controller || false
     */
    public function delete_scheduled_data($post_id){
        $pid   = isset($_GET['post']) ? $_GET['post'] : '';
        $nonce = isset($_GET['_wpnonce']) ? $_GET['_wpnonce'] : '';
        $can_edit = current_user_can( 'edit_post', $pid );
        if($pid && $nonce && $can_edit && wp_verify_nonce( $nonce, 'schedule_published' )){
            delete_post_meta($pid, 'wpscp_pending_schedule');
            delete_post_meta($pid, 'wpscp_pending_schedule_data');
            wp_clear_scheduled_hook('wpscp_pending_schedule', array((int) $pid));
            wp_redirect( get_edit_post_link( $pid, null ) );
            exit;
        }
    }

    public function is_sticky( $is_sticky, $post_id ){
        $scheduled = get_post_meta($post_id, 'wpscp_pending_schedule', true);
        if(!empty($scheduled['sticky'])){
            return true;
        }
        return $is_sticky;
    }

    /***
     *
     * @param \WP_REST_Request $request
     *
     * @return \WP_REST_Controller || false
     */
    public function get_controller($request){
        $server = rest_get_server();
        $matched = $this->call_reflection($server, 'match_request_to_handler', [$request]);
        return isset($matched[1]['callback'][0]) ? $matched[1]['callback'][0] : false;
    }

    public function call_reflection($object, $method, $args){
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);
        $results = $reflection->invokeArgs($object, $args);
        return $results;
    }

}

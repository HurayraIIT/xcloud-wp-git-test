<?php
namespace BetterLinksPro\Admin;
use BetterLinks\Cron;

class Ajax
{
    use \BetterLinksPro\Traits\BrokenLinks;
    use \BetterLinksPro\Traits\Keywords;
    public static function init()
    {
        $self = new self();
        add_action('wp_ajax_betterlinkspro/admin/get_role_management', [$self, 'get_role_management']);
        add_action('wp_ajax_betterlinkspro/admin/role_management', [$self, 'role_management']);
        add_action('wp_ajax_betterlinkspro/admin/get_external_analytics', [$self, 'get_external_analytics']);
        add_action('wp_ajax_betterlinkspro/admin/external_analytics', [$self, 'external_analytics']);
        add_action('wp_ajax_betterlinkspro/admin/analytics', [$self, 'analytics']);
        add_action('wp_ajax_betterlinkspro/admin/get_auto_link_create_settings', [$self, 'get_auto_link_create_settings']);
        add_action('wp_ajax_betterlinkspro/admin/set_auto_link_create_settings', [$self, 'set_auto_link_create_settings']);
        add_action('wp_ajax_betterlinkspro/admin/get_auto_link_disable_ids', [$self, 'get_auto_link_disable_ids']);
        add_action('wp_ajax_betterlinkspro/admin/set_auto_link_disable_ids', [$self, 'set_auto_link_disable_ids']);
        add_action('wp_ajax_betterlinkspro/admin/get_links', [$self, 'get_links']);
        add_action('wp_ajax_betterlinkspro/admin/get_broken_links_data', [$self, 'get_broken_links_data']);
        add_action('wp_ajax_betterlinkspro/admin/run_instant_broken_link_checker', [$self, 'run_instant_broken_link_checker']);
        add_action('wp_ajax_betterlinkspro/admin/delete_broken_link_checker_logs', [$self, 'delete_broken_link_checker_logs']);
        add_action('wp_ajax_betterlinkspro/admin/run_broken_links_checker', [$self, 'run_broken_links_checker']);
        add_action('wp_ajax_betterlinkspro/admin/run_single_broken_link_checker', [$self, 'run_single_broken_link_checker']);
        add_action('wp_ajax_betterlinkspro/admin/update_broken_link', [$self, 'update_broken_link']);
        add_action('wp_ajax_betterlinkspro/admin/remove_broken_link', [$self, 'remove_broken_link']);
        add_action('wp_ajax_betterlinkspro/admin/remove_multi_broken_link', [$self, 'remove_multi_broken_link']);
        add_action('wp_ajax_betterlinkspro/admin/get_broken_link_settings', [$self, 'get_broken_link_settings']);
        add_action('wp_ajax_betterlinkspro/admin/save_broken_link_settings', [$self, 'save_broken_link_settings']);
        add_action('wp_ajax_betterlinkspro/admin/get_split_test_analytics', [$self, 'get_split_test_analytics']);
        add_action('wp_ajax_betterlinkspro/admin/get_reporting_settings', [$self, 'get_reporting_settings']);
        add_action('wp_ajax_betterlinkspro/admin/saved_reporting_settings', [$self, 'saved_reporting_settings']);
        add_action('wp_ajax_betterlinkspro/admin/test_report_mail', [$self, 'test_report_mail']);
        // filter
        add_filter('betterlinks/admin/current_user_can_edit_settings', [$self, 'current_user_can_edit_settings']);
        add_filter('betterlinkspro/admin/current_user_can_edit_settings', [$self, 'current_user_can_edit_settings']);
        // API Fallbck Ajax
        add_action('wp_ajax_betterlinks/admin/get_all_keywords', [$self, 'get_all_keywords']);
        add_action('wp_ajax_betterlinks/admin/create_keyword', [$self, 'create_keyword']);
        add_action('wp_ajax_betterlinks/admin/update_keyword', [$self, 'update_keyword']);
        add_action('wp_ajax_betterlinks/admin/delete_keyword', [$self, 'delete_keyword']);

        // Password Protection requests
        add_action( 'wp_ajax_betterlinkspro/admin/create_links_password', [$self, 'create_links_password'] );
        add_action( 'wp_ajax_betterlinkspro/admin/fetch_links_password', [$self, 'fetch_links_password'] );

    }


    public function fetch_links_password() {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (!apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this");
        }
        global $wpdb;
        $query_string = "SELECT * FROM {$wpdb->prefix}betterlinks_password";
        $results = $wpdb->get_results($query_string);
        
        wp_send_json_success([
            'links' => $results
        ], 200);
    }

    public function create_links_password() {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');

        if (!apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this");
        }
        global $wpdb;
        $link_id = !empty($_POST['link_id']) ? $_POST['link_id'] : null;
        $password = !empty($_POST['password']) ? $_POST['password'] : '';
        $status = (isset($_POST['status']) && "true" === $_POST['status']);
        $allow_contact = (isset($_POST['allow_contact']) && "true" === $_POST['allow_contact']);

        // 👇 check if the link password is already exists using link_id
        $result = \BetterLinksPro\Helper::get_password_by_link_id($link_id);


        if( count( $result ) === 1 ) { // if result returns true, that means link password already exists
            $update_query = "UPDATE {$wpdb->prefix}betterlinks_password SET `status`=%d, `password`=%s, `allow_contact`=%d WHERE `link_id`=%d";
            $update_query_values = [$status, $password, $allow_contact, $link_id];

            $update = $wpdb->query( $wpdb->prepare( $update_query, $update_query_values ) );
            wp_send_json([
                'updated' => $update ? true : false,
                'message' => $update ? 'Updated successfully' : 'Failed to update'
            ], $update ? 200 : 500);
        }
        // completed update query


        $query_string = "INSERT INTO {$wpdb->prefix}betterlinks_password (`link_id`, `password`, `status`, `allow_contact`) VALUES(%d,%s,%d,%d)";
        $query_value_arr = [$link_id, $password, $status,$allow_contact];

        $wpdb->query( $wpdb->prepare( $query_string,  $query_value_arr ) );
        
        if( $wpdb->insert_id ) {
            wp_send_json_success([
                'success' => true,
                'message' => 'password created successfully.',
                'data' => [
                    'id' => $wpdb->insert_id,
                    'link_id' => $link_id,
                    'status' => $status,
                    'allow_contact' => $allow_contact
                ]
            ], 201);
        }else {
            wp_send_json_error( [
                'success' => false,
                'message' => 'Something went wrong.'
            ], 500 );
        }
    }


    public function get_role_management()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $data = get_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME, '{}');
            wp_send_json_success(json_decode($data, true));
        }
        wp_die("You don't have permission to do this.");
    }
    public function role_management()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (!apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            return false;
        }
        $viewlinks = (isset($_POST['viewlinks']) ? explode(',', sanitize_text_field($_POST['viewlinks'])) : []);
        $writelinks = (isset($_POST['writelinks']) ? explode(',', sanitize_text_field($_POST['writelinks'])) : []);
        $editlinks = (isset($_POST['editlinks']) ? explode(',', sanitize_text_field($_POST['editlinks'])) : []);
        $checkanalytics = (isset($_POST['checkanalytics']) ? explode(',', sanitize_text_field($_POST['checkanalytics'])) : []);
        $editsettings = (isset($_POST['editsettings']) ? explode(',', sanitize_text_field($_POST['editsettings'])) : []);
        $editFavorite = (isset($_POST['editFavorite']) ? explode(',', sanitize_text_field($_POST['editFavorite'])) : []);
        $manageAutoliks = (isset($_POST['manageAutoliks']) ? explode(',', sanitize_text_field($_POST['manageAutoliks'])) : []);
        $update = update_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME, json_encode(array(
            'viewlinks' => $viewlinks,
            'writelinks' => $writelinks,
            'editlinks' => $editlinks,
            'checkanalytics' => $checkanalytics,
            'editsettings' => $editsettings,
            'editFavorite' => $editFavorite,
            'manageAutoliks' => $manageAutoliks,
        )));
        wp_send_json_success($update);
    }

    public function get_external_analytics()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $data = get_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME, []);
            if(is_string($data)){
                $data = json_decode($data, true);
            }
            wp_send_json_success($data);
        }
        wp_die("You don't have permission to do this.");
    }

    public function get_auto_link_create_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $data = get_option(BETTERLINKS_PRO_AUTO_LINK_CREATE_OPTION_NAME, []);
            if(is_string($data)){
                $data = json_decode($data, true);
            }
            wp_send_json_success($data);
        }
        wp_die("You don't have permission to do this.");
    }

    public function get_auto_link_disable_ids() {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $post_id = (isset($_POST['id']) ? intval(sanitize_text_field($_POST['id'])) : '');
            $data = get_post_meta($post_id, BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS);
            wp_send_json_success($data);
        }
        wp_die("You don't have permission to do this.");
    }
    public function set_auto_link_disable_ids() {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $post_id = (isset($_POST['id']) ? intval(sanitize_text_field($_POST['id'])) : '');
            $status = (isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '0');
            $data = get_post_meta($post_id, BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS);

            $update = update_post_meta($post_id, BETTERLINKS_PRO_AUTO_LINK_DISABLE_IDS, $status);
            
            wp_send_json($update);
        }
        wp_die("You don't have permission to do this.");
    }

    public function external_analytics()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $is_enable_ga = filter_var((isset($_POST['is_enable_ga']) ? sanitize_text_field($_POST['is_enable_ga']) : false), FILTER_VALIDATE_BOOLEAN);
            // $is_ga4 = filter_var( ( isset( $_POST['is_ga4'] ) ? sanitize_text_field( $_POST['is_ga4'] ) : false ), FILTER_VALIDATE_BOOLEAN );
            $is_ga4 = true; // Since Universal analytics has stopped tracking, so we only have GA4.
            $is_enable_pixel = filter_var((isset($_POST['is_enable_pixel']) ? sanitize_text_field($_POST['is_enable_pixel']) : false), FILTER_VALIDATE_BOOLEAN);
            $ga_tracking_code = (isset($_POST['ga_tracking_code']) ? sanitize_text_field($_POST['ga_tracking_code']) : '');
            $ga4_api_secret = ( isset( $_POST['ga4_api_secret'] ) ? sanitize_text_field($_POST['ga4_api_secret']) : '' );
            $pixel_id = (isset($_POST['pixel_id']) ? sanitize_text_field($_POST['pixel_id']) : '');
            $pixel_access_token = (isset($_POST['pixel_access_token']) ? sanitize_text_field($_POST['pixel_access_token']) : '');
            $analytic_data = array(
                'is_enable_ga' => $is_enable_ga,
                'is_ga4' => $is_ga4,
                'ga_tracking_code' => $ga_tracking_code,
                'ga4_api_secret' => $ga4_api_secret,
                'is_enable_pixel' => $is_enable_pixel,
                'pixel_id' => $pixel_id,
                'pixel_access_token' => $pixel_access_token,
            );
            $update = update_option(BETTERLINKS_PRO_EXTERNAL_ANALYTICS_OPTION_NAME, $analytic_data);
            if(defined('BETTERLINKS_EXISTS_LINKS_JSON') && BETTERLINKS_EXISTS_LINKS_JSON){
                $formattedArray = \BetterLinks\Helper::get_links_for_json();
                file_put_contents(BETTERLINKS_UPLOAD_DIR_PATH . '/links.json', json_encode($formattedArray));
            }
            wp_send_json_success($update);
        }
        wp_die("You don't have permission to do this.");
    }

    public function analytics()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $Cron = new Cron();
            $resutls = $Cron->analytics();
            wp_send_json_success($resutls);
        }
        wp_die("You don't have permission to do this.");
    }
    public function set_auto_link_create_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (apply_filters('betterlinkspro/admin/current_user_can_edit_settings', current_user_can('manage_options'))) {
            $enable_auto_link = filter_var((isset($_POST['enable_auto_link']) ? sanitize_text_field($_POST['enable_auto_link']) : false), FILTER_VALIDATE_BOOLEAN);
            $post_shortlinks = filter_var((isset($_POST['post_shortlinks']) ? sanitize_text_field($_POST['post_shortlinks']) : false), FILTER_VALIDATE_BOOLEAN);
            $page_shortlinks = filter_var((isset($_POST['page_shortlinks']) ? sanitize_text_field($_POST['page_shortlinks']) : false), FILTER_VALIDATE_BOOLEAN);
            $post_default_cat = ( isset( $_POST['post_default_cat'] ) ? sanitize_text_field($_POST['post_default_cat']) : 0 );
            $page_default_cat = ( isset( $_POST['page_default_cat'] ) ? sanitize_text_field($_POST['page_default_cat']) : 0 );

            $auto_link_create_options = [
                'enable_auto_link' => $enable_auto_link,
                'post_shortlinks'  => $enable_auto_link ? $post_shortlinks : false,
                'page_shortlinks'  => $enable_auto_link ? $page_shortlinks : false,
            ];

            if( !empty($auto_link_create_options['post_shortlinks']) ){
                $post_default_cat = \BetterLinksPro\Helper::insert_new_category($post_default_cat);
                $auto_link_create_options['post_default_cat'] = $post_default_cat;
            }
            if( !empty($auto_link_create_options['page_shortlinks']) ){
                $page_default_cat = \BetterLinksPro\Helper::insert_new_category($page_default_cat);
                $auto_link_create_options['page_default_cat'] = $page_default_cat;
            }

            $update = update_option(BETTERLINKS_PRO_AUTO_LINK_CREATE_OPTION_NAME, wp_json_encode($auto_link_create_options));
            wp_send_json_success($update);
        }
        wp_die("You don't have permission to do this.");
    }

    public function get_links()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! apply_filters('betterlinks/api/links_get_items_permissions_check', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this.");
        }
        global $wpdb;
        $ID = (isset($_POST['ID']) ? sanitize_text_field( esc_sql( $_POST['ID'] ) ) : '');
        if ($ID) {
            $results = $wpdb->get_results( $wpdb->prepare( 
                "SELECT ID, link_title FROM {$wpdb->prefix}betterlinks WHERE ID=%d", $ID
             ), OBJECT);
        } else {
            $results = $wpdb->get_results("SELECT ID, link_title FROM {$wpdb->prefix}betterlinks", OBJECT);
        }
        wp_send_json_success($results);
    }
    public function get_broken_links_data()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks", OBJECT);
        $links = json_encode($results);
        $logs = get_option('betterlinkspro_broken_links_logs');
        wp_send_json_success(['links' =>  $links, 'logs' => $logs]);
    }
    public function run_instant_broken_link_checker()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}betterlinks", OBJECT);
        $links = json_encode($results);
        $logs = get_option('betterlinkspro_broken_links_logs');
        wp_send_json_success(['links' =>  $links, 'logs' => $logs]);
    }
    public function delete_broken_link_checker_logs()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $result = delete_option('betterlinkspro_broken_links_logs');
        wp_send_json_success($result);
    }
    public function run_broken_links_checker()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        if (is_array($data) && count($data)) {
            $this->check_broken_link($data);
        }
        wp_send_json_success(get_option('betterlinkspro_broken_links_logs'));
    }
    public function run_single_broken_link_checker()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        
        global $wpdb;
        $ID = (isset($_REQUEST['ID']) ? sanitize_text_field( esc_sql( $_REQUEST['ID'] ) ) : 0);
        $result = $wpdb->get_results( $wpdb->prepare( 
            "SELECT * FROM {$wpdb->prefix}betterlinks WHERE ID=%d", $ID
         ), OBJECT);
         
        $result = current($result);
        $target_url = '';
        $target_url = \BetterLinksPro\Helper::addScheme($result->target_url);
        $status = \BetterLinksPro\Helper::url_http_response_is_broken($target_url);
        if(isset($status['status_code'])){
            wp_send_json_success($status);
        }else{
            wp_send_json_error("status wasn't recieved");
        }
    }
    
    public function update_broken_link()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        do_action('betterlinks/write_json_links');
        $ID = (isset($_REQUEST['ID']) ? sanitize_text_field($_REQUEST['ID']) : 0);
        $target_url = (isset($_REQUEST['target_url']) ? sanitize_text_field($_REQUEST['target_url']) : 0);
        $logs = json_decode(get_option('betterlinkspro_broken_links_logs'), true);
        $log_item =  $logs[$ID];
        $log_item['target_url'] = $target_url;
        $logs[$ID] = $log_item;
        update_option('betterlinkspro_broken_links_logs', json_encode($logs), false);
        wp_send_json_success(json_encode($logs));
    }
    public function remove_broken_link()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $ID = (isset($_REQUEST['ID']) ? sanitize_text_field($_REQUEST['ID']) : 0);
        $results = json_decode(get_option('betterlinkspro_broken_links_logs'), true);
        $results[$ID]['is_log_removed'] = true;
        update_option('betterlinkspro_broken_links_logs', json_encode($results), false);
        wp_send_json_success(json_encode($results));
    }
    public function remove_multi_broken_link()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $logs = \BetterLinks\Helper::fresh_ajax_request_data($_REQUEST);
        $results = json_decode(get_option('betterlinkspro_broken_links_logs'), true);
        foreach ($logs as $ID) {
            $results[$ID]['is_log_removed'] = true;
        }
        update_option('betterlinkspro_broken_links_logs', json_encode($results), false);
        wp_send_json_success(json_encode($results));
    }


    public function current_user_can_edit_settings()
    {
        if (current_user_can('manage_options')) {
            return true;
        }
        $user = wp_get_current_user();
        $user_permission = json_decode(get_option(BETTERLINKS_PRO_ROLE_PERMISSON_OPTION_NAME), true);
        $current_user_roles = current($user->roles);
        if (
            in_array($current_user_roles, $user_permission['editsettings'])
        ) {
            return true;
        }
        return false;
    }

    public function get_broken_link_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $restuls = get_option(BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME, '{}');
        wp_send_json_success($restuls);
    }

    public function save_broken_link_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $data = \BetterLinks\Helper::sanitize_text_or_array_field($data);
        
        wp_clear_scheduled_hook('betterlinkspro/broken_link_checker');
        update_option(BETTERLINKS_PRO_BROKEN_LINK_OPTION_NAME, json_encode($data));
        wp_send_json_success(json_encode($data));
    }
    public function get_split_test_analytics()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (!apply_filters('betterlinkspro/api/analytics_items_permissions_check', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this.");
        }
        $ID = (isset($_REQUEST['ID']) ? $_REQUEST['ID'] : "");
        $results = \BetterLinksPro\Helper::get_split_test_analytics_data(['id' => $ID]);
        wp_send_json_success(
            $results,
            200
        );
    }
    public function get_reporting_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $restuls = get_option(BETTERLINKS_PRO_REPORTING_OPTION_NAME, '{}');
        wp_send_json_success(
            $restuls,
            200
        );
    }
    public function saved_reporting_settings()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $data = \BetterLinks\Helper::sanitize_text_or_array_field($data);
        update_option(BETTERLINKS_PRO_REPORTING_OPTION_NAME, json_encode($data));
        wp_send_json_success(
            json_encode($data),
            200
        );
    }
    public function test_report_mail()
    {
        check_ajax_referer('betterlinkspro_admin_nonce', 'security');
        if (! $this->current_user_can_edit_settings()) {
            wp_die("You don't have permission to do this.");
        }
        $email = $this->send_mail();
        wp_send_json_success(
            $email,
            200
        );
    }
    public function get_all_keywords()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (!apply_filters('betterlinkspro/api/manage_autolink_permission_check', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this.");
        }
        $results = \BetterLinks\Helper::get_keywords();
        wp_send_json_success(
            $results,
            200
        );
    }
    public function create_keyword()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! apply_filters('betterlinkspro/api/manage_autolink_permission_check', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this.");
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $item = $this->prepare_keyword_item_for_db($data);
        $link_id = (isset($item['link_id']) ? $item['link_id'] : 0);
        \BetterLinks\Helper::add_link_meta($link_id, 'keywords', $item);
        wp_send_json_success(
            $item,
            200
        );
    }
    public function update_keyword()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! apply_filters('betterlinkspro/api/manage_autolink_permission_check', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this.");
        }
        $data = \BetterLinks\Helper::fresh_ajax_request_data($_POST);
        $old_link_id = absint(isset($data['oldChooseLink']) ? $data['oldChooseLink'] : 0);
        $link_id = absint(isset($data['chooseLink']) ? $data['chooseLink'] : 0);
        $old_keywords = (isset($data['oldKeywords']) ? $data['oldKeywords'] : "");
        $item = $this->prepare_keyword_item_for_db($data);
        $is_update = \BetterLinks\Helper::update_link_meta($link_id, 'keywords', $item, $old_keywords, $old_link_id);
        if($is_update){
            wp_send_json_success(
                array_merge($item, [
                    'old_link_id' => $old_link_id,
                    'old_keywords' => $old_keywords,
                ]),
                200
            );
        }else{
            wp_send_json_error( "updated link meta failed " );
        }
    }
    public function delete_keyword()
    {
        check_ajax_referer('betterlinks_admin_nonce', 'security');
        if (! apply_filters('betterlinkspro/api/manage_autolink_permission_check', current_user_can('manage_options'))) {
            wp_die("You don't have permission to do this.");
        }
        $id = (isset($_POST['id']) ? intval(sanitize_text_field($_POST['id'])) : 0);
        $keywords = (isset($_POST['keywords']) ? (sanitize_text_field($_POST['keywords'])) : "");
        $is_delete = \BetterLinks\Helper::delete_link_meta($id, 'keywords', "", $keywords);
        wp_send_json_success(
            $is_delete,
            200
        );
    }
}
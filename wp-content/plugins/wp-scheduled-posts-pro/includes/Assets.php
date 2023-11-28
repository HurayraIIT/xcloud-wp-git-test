<?php

namespace WPSP_PRO;

class Assets
{
    public function __construct()
    {
        add_action('enqueue_block_assets', array($this, 'block_scripts'), 11);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 30);
        add_filter('wpsp_settings_global', array($this, 'settings_objects_extend'));
    }
    /**
     * Gutten Support
     * @since 1.2.0
     */
    public function block_scripts()
    {
        if (!class_exists('\WPSP\Helper')) return;
        global $post_type;
        $allow_post_types = \WPSP_PRO\Helper::get_settings('allow_post_types');
        $allow_post_types = (!empty($allow_post_types) ? $allow_post_types : array('post'));
        if (!in_array($post_type, $allow_post_types) || !is_admin()) {
            return;
        }
        $manage_schedule = \WPSP\Helper::get_settings('manage_schedule');
        $activeScheduleStatus = (isset($manage_schedule->activeScheduleSystem) ? $manage_schedule->activeScheduleSystem : '');
        wp_enqueue_style('wps-publish-date', WPSP_PRO_ASSETS_URL . 'css/wpsp-admin.min.css');
        wp_enqueue_script('wps-publish-date', WPSP_PRO_ASSETS_URL . 'js/wpsp-admin.min.js', array('wp-components', 'wp-data', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins'), WPSP_PRO_VERSION, true);

        wp_localize_script('wps-publish-date', 'WPSchedulePosts', apply_filters('WPSchedulePostsData', array(
            'PanelTitle' => __('Schedule at', 'wp-scheduled-posts-pro'),
            'schedule' => Helper::manual_schedule(),
            'allowedPostTypes' => $allow_post_types,
            'currentTime' => array(
                'date' => current_time('mysql'),
                'date_gmt' => current_time('mysql', 1),
            ),
            'activeScheduleSystem' => $activeScheduleStatus,
            'auto_date' => Helper::auto_schedule(),
        )));
    }

    public function enqueue_scripts($hook)
    {
        if (!class_exists('\WPSP\Helper')) return;
        $manage_schedule = \WPSP\Helper::get_settings('manage_schedule');
        $activeScheduleStatus = (isset($manage_schedule->activeScheduleSystem) ? $manage_schedule->activeScheduleSystem : '');
        $current_screen = get_current_screen();
        if (is_admin() && \WPSP\Helper::plugin_page_hook_suffix($current_screen->post_type, $hook)) {
            wp_enqueue_script('media-upload');
            wp_enqueue_script('wpscp-pro-scripts', WPSP_PRO_ASSETS_URL . 'js/wpscp-pro-scripts.js', array('jquery'), WPSP_PRO_VERSION, true);
            wp_localize_script(
                'wpscp-pro-scripts',
                'wpscp_pro_ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'PanelTitle' => __('Schedule at', 'wp-scheduled-posts-pro'),
                    'schedule' => Helper::manual_schedule(),
                    'currentTime' => array(
                        'date' => current_time('mysql'),
                        'date_gmt' => current_time('mysql', 1),
                    ),
                    'activeScheduleSystem' => $activeScheduleStatus,
                    'auto_date' => Helper::auto_schedule(),
                )
            );
        }
    }
    public function settings_objects_extend($args)
    {
        $args['license_nonce'] = wp_create_nonce(WPSP_PRO_SL_ITEM_SLUG . '_license_nonce');
        return $args;
    }
}

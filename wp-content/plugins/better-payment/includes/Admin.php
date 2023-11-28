<?php

namespace Better_Payment\Lite;

use Better_Payment\Lite\Admin\Better_Payment_Settings;
use Better_Payment\Lite\Admin\Elementor\Better_Payment_Widget;
use Better_Payment\Lite\Admin\Elementor\Widget;
use Better_Payment\Lite\Admin\Menu;
use Better_Payment\Lite\Classes\Better_Payment_Helper;
use Better_Payment\Lite\Classes\Export;

/**
 * Exit if accessed directly
 */
if (!defined('ABSPATH')) {
    exit;
}

/**
 * The admin class
 * 
 * @since 0.0.1
 */
class Admin extends Controller{

    /**
     * Initialize the class
     * 
     * @since 0.0.1
     */
    public function __construct() {

    }

    public function init(){
        if ( defined( 'ELEMENTOR_VERSION' ) ) {
            new Better_Payment_Settings();
        }

        if (!did_action('elementor/loaded')) {
            $notice = new Better_Payment_Helper();
            $notice->elementor_not_loaded();
        }

        if (did_action('elementor/loaded')) {
            add_filter('plugin_action_links', array($this, 'plugin_actions_links'), 10, 2);
        }
    }

    /**
     * Add settings page link on plugins page
     * 
     * @param array $links
     * @param string $file
     * 
     * @return array
     * @since 0.0.1
     */
    public function plugin_actions_links($links, $file) {
        $better_payment_plugin = plugin_basename(BETTER_PAYMENT_FILE);

        if ($file == $better_payment_plugin && current_user_can('manage_options')) {
            $links[] = sprintf('<a href="%s">%s</a>', admin_url("admin.php?page=better-payment-settings"), __('Settings', 'better-payment'));

            if ( ! class_exists('\Better_Payment_Pro') ) {
                $links[] = sprintf('<a href="https://wpdeveloper.com/in/upgrade-better-payment-pro" target="_blank" style="color: #524cff; font-weight: bold;">' . __('Go Pro', 'better-payment') . '</a>');
            }
        }

        return $links;
    }

    /**
     * Dispatch and bind actions
     *
     * @return void
     * @since 0.0.1
     */
    public static function dispatch_actions( ) {
        $menuObj = new Menu();
        $menuObj->init();

        $bpElementorWidgetObj = new Widget();

        $bpExportObj = new Export();

        // Handle select2 ajax search
        add_action('wp_ajax_better_payment_select2_search_post', [$bpElementorWidgetObj, 'select2_ajax_posts_filter_autocomplete']);
        add_action('wp_ajax_nopriv_better_payment_select2_search_post', [$bpElementorWidgetObj, 'select2_ajax_posts_filter_autocomplete']);

        add_action('wp_ajax_better_payment_select2_get_title', [$bpElementorWidgetObj, 'select2_ajax_get_posts_value_titles']);
        add_action('wp_ajax_nopriv_better_payment_select2_get_title', [$bpElementorWidgetObj, 'select2_ajax_get_posts_value_titles']);

        // Elements
        add_action('elementor/controls/controls_registered',[$bpElementorWidgetObj, 'register_controls']);

        //Page: Transactions
        add_action('wp_ajax_better-payment-transactions-export',[$bpExportObj, 'export_transactions']);
    }
}

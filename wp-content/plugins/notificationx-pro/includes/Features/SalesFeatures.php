<?php
namespace NotificationXPro\Feature;

use NotificationX\Core\Rules;
use NotificationX\GetInstance;

/**
 * Class NotificationXPro_Sales_Features
 * Handles Pro features for sales notifications
 * @method static SalesFeatures get_instance($args = null)
 * @since 1.2.2
 */
class SalesFeatures {
    /**
     * Instance of SalesFeatures
     *
     * @var SalesFeatures
     */
    use GetInstance;

    /**
     * Woocommerce orders with days count
     * @var array
     */
    private $orders;

    /**
     * EDD payments with days count
     * @var array
     */
    private $edd_payments;

    /**
     * Learndash course enrollments with day counts
     * @var array
     */
    private $ld_enrolls;

    /**
     * Give donations with day counts
     * @var array
     */
    private $give_donations;

    /**
     * contents field meta key
     */
    public $meta_key = 'sales_counts';

    /**
     * Sales count source types
     * @var array
     */
    public $sales_count_sources = [
        'woocommerce',
        'edd',
        'freemius_conversions',
        'zapier_conversions',
        'custom_notification',
        'custom_notification_conversions',
        'envato',
        'learndash',
        'tutor',
        'give',
        'woo_inline',
        'edd_inline',
        'tutor_inline',
        'learndash_inline',
    ];

    /**
     * Sales count supported themes
     * @var array
     */
    public $sales_count_themes = [
        'conversions_conv-theme-seven',
        'conversions_conv-theme-eight',
        'conversions_conv-theme-nine',

        'elearning_conv-theme-seven',
        'elearning_conv-theme-eight',
        'elearning_conv-theme-nine',

        'donation_conv-theme-seven',
        'donation_conv-theme-eight',
        'donation_conv-theme-nine',

        'woo_inline_conv-theme-seven',
        'edd_inline_conv-theme-seven',
        'tutor_inline_conv-theme-seven',
        'tutor_inline_conv-theme-eight',
        'learndash_inline_conv-theme-seven',
    ];

    /**
     * NotificationXPro_Sales_Features constructor.
     */
    public function __construct() {
        // add_filter("nx_save_post", array($this, 'save_post'), 15, 3);
        // add_filter("nx_saved_post", array($this, 'saved_post'), 15, 3);
        // // add_filter('nx_filtered_entry', array($this, 'conversion_data'), 10, 2);
        // add_action('nx_cron_update_data', array($this, 'update_data'), 10, 2);
        add_filter("nx_get_post", array($this, 'get_post'), 8);
        add_filter('nx_fallback_data', array($this, 'fallback_data'), 10, 3);
        add_filter('nx_settings_tab_cache', [$this, 'cache_settings']);
        add_filter('nx_should_combine', array($this, 'should_combine'), 10, 3);
        add_filter('nx_filtered_data', array($this, 'conversion_data'), 10, 2);
        add_filter('nx_customize_fields', [$this, 'customize_fields']);
        add_filter('nx_content_fields', [$this, 'content_fields']);

    }

    public function is_sales_count($settings){
        if(isset($settings['source']) && isset($settings['themes']) && in_array($settings['source'], $this->sales_count_sources) && in_array($settings['themes'], $this->sales_count_themes)){
            return true;
        }
        return false;
    }

    /**
     * Fallback data;
     *
     * @param [type] $defaults
     * @param [type] $entry
     * @param [type] $settings
     * @return void
     */
    public function fallback_data($defaults, $entry, $post) {
        if (!$this->is_sales_count($post)) {
            return $defaults;
        }
        $defaults['sales_count'] = _x( 0, 'Fallback for Sales Count for conversions.', 'notificationx-pro' );

        $days                       = $this->get_days( $post );
        $defaults[ $days . 'days' ] = sprintf( __( 'in last %d days', 'notificationx-pro' ), $days );
        $defaults[ 'day:' . $days ] = sprintf( _n( '%d day', '%d days', $days, 'notificationx-pro' ), number_format_i18n( $days ) );
        $defaults['1day']           = __( 'in last 1 day', 'notificationx-pro' );
        $defaults['7days']          = __( 'in last 7 days', 'notificationx-pro' );
        $defaults['30days']         = __( 'in last 30 days', 'notificationx-pro' );
        return $defaults;
    }

    /**
     * Cache Settings for Regenerate data
     * @param array $sections
     * @return array
     */
    public function cache_settings($sections) {
        $sections['fields']['cache_settings']['fields']['sales_count_cache_duration'] = array(
            'type' => 'text',
            'name' => 'sales_count_cache_duration',
            'label' => __('Sales count Cache Duration', 'notificationx-pro'),
            'description' => __('Minutes (Schedule Duration to fetch new data).', 'notificationx-pro'),
            'default' => 3,
            'priority' => 4
        );
        return $sections;
    }

    public function should_combine($return, $entries, $settings){
        if($this->is_sales_count($settings)) {
            $return = false;
        }
        return $return;
    }

    public function get_days($settings){
        $template_adv = !empty($settings['template_adv']);
        if($template_adv){
            $advanced_template = $settings['advanced_template'];
            $matches = [];
            preg_match_all('/(?:{{day:(\d+)}})|(?:{{(\d+)days}})/', $advanced_template, $matches);
            $fourth_param = isset($matches[0][0]) ? $matches[0][0] : '';
        }
        else{
            $template_params = $settings['notification-template'];
            $fourth_param    = $template_params['fourth_param'];
            if($fourth_param == 'tag_custom'){
                $fourth_param = $template_params['custom_fourth_param'];
            }
        }
        $matches = [];
        preg_match('/[\d]+/', $fourth_param, $matches);
        $days = isset($matches[0]) ? $matches[0] : '';
        $days = !empty($days) ? (int) $days : 1;
        return $days;
    }

    /**
     * This method is responsible for make data available in frontend
     * @param array $data
     * @param int $id
     * @return array
     *
     * @todo combine into one notice.
     */
    public function conversion_data($entries, $settings) {
        if (!$this->is_sales_count($settings) || in_array($settings['source'], ['zapier_conversions', 'custom_notification_conversions', 'custom_notification'])) {
            return $entries;
        }

        $new_entries = [];
        $days = $this->get_days($settings);
        $days = strtotime($this->format_date($days));
        foreach ($entries as $key => $entry) {
            $source    = $settings['source'];
            $timestamp = $entry['timestamp'];
            if(!is_numeric($timestamp)){
                $timestamp = strtotime($timestamp);
            }

            if($days > $timestamp){
                continue;
            }

            $s_key = 0;
            if (in_array($source, array('woocommerce', 'tutor', 'edd', 'woo_inline', 'edd_inline', 'tutor_inline' ))) {
                $s_key = isset($entry['product_id']) ? $entry['product_id'] : 0;
            }
            if (in_array($source, array('give'))) {
                $s_key = isset($entry['give_form_id']) ? $entry['give_form_id'] : 0;
            }
            if (in_array($source, array('freemius_conversions'))) {
                $s_key = isset($entry['plugin_id']) ? $entry['plugin_id'] : 0;
            }
            $s_key = $s_key === 0 && isset($entry['id']) ? $entry['id'] : $s_key;

            if(empty($new_entries[$s_key])){
                $entry['sales_count'] = 1;
                $new_entries[$s_key] = $entry;
            }
            else{
                $new_entries[$s_key]['sales_count'] += 1;
            }
        }

        return $new_entries;
    }

    public function get_post($settings){
        if ($this->is_sales_count($settings)) {
            $settings['display_from'] = $this->get_days($settings);
            return $settings;
        }
        return $settings;
    }

    /**
     * Convert days in 'Y-m-d' format for query
     * @param int $days
     * @return false|string
     */
    private function format_date($days) {
        if ($days == 1) {
            return date('Y-m-d', strtotime('- 24 Hours'));
        } else {
            return date('Y-m-d', strtotime('-' . intval($days) . ' days'));
        }
    }
    /**
     * This method is an implementable method for All Extension coming forward.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function customize_fields($fields) {
        $fields["behaviour"]['fields']['display_from'] = Rules::includes('themes', $this->sales_count_themes, true, $fields["behaviour"]['fields']['display_from']);
        return $fields;
    }    /**
    * Undocumented function
    *
    * @param array $options
    * @return array
    */
   public function content_fields($fields) {
       $fields["content"]['fields']['advanced_template']['sales_count_themes'] = $this->sales_count_themes;
       return $fields;
   }
}

<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Admin;

use NotificationX\Core\Helper as HelperFree;
use NotificationX\GetInstance;
use NotificationX\ThirdParty\WPML;
use NotificationXPro\Core\Helper;

/**
 * @method static XSS get_instance($args = null)
 */
class XSS {

    use GetInstance;

    public function __construct()
    {
        add_filter('nx_builder_configs', [$this, 'builder_configs']);
        add_filter('nx_settings_xss_code_default', [$this, 'settings_field_xss_code']);
        // add_filter( 'nx_settings_tab_miscellaneous', [ $this, 'settings_tab_help' ], 11 );
        // add_action( 'init', [ $this, 'add_cors_http_header'] );
    }

    public function get_localize_data($data = []){
        $data = apply_filters('nx_frontend_localize_data', $data);
        if(isset($data['rest']['nonce'])){
            unset($data['rest']['nonce']);
        }
        return $data;
    }

    public function builder_configs($tabs){
        $tabs['xss_data']    = $this->get_localize_data();
        $tabs['xss_scripts'] = $this->get_scripts();
        return $tabs;
    }
    public function settings_field_xss_code($xss_code){
        $xss_code = "<script>\nwindow.notificationXArr = window.notificationXArr || []; \nwindow.notificationXArr.push(" . json_encode($this->get_localize_data(['all_active' => true, 'cross' => true ])) . ");\n</script>";
        $xss_code .= $this->get_scripts();
        return $xss_code;
    }

    public function get_scripts(){
        $xss_scripts    = "";
        $scripts = [
            HelperFree::file('public/js/frontend.js', true),
        ];
        $styles = [
            HelperFree::file('public/css/frontend.css', true),
            Helper::pro_file('public/css/frontend.css', true),
        ];

        foreach ($styles as $style) {
            $xss_scripts .= "\n<link rel='stylesheet' href='$style' media='all' />";
        }
        foreach ($scripts as $script) {
            $xss_scripts .= "\n<script src='$script'></script>"; /// crossorigin='anonymous'
        }


        return $xss_scripts;
    }

    public function settings_tab_help( $tabs ) {
        $tabs['fields']['xss_settings']['fields']['acao'] =
            array(
                'name'        => 'acao',
                'type'        => 'text',
                'label'       => __( 'Access-Control-Allow-Origin', 'notificationx' ),
                'placeholder' => __('*', 'notificationx'),
                'is_pro'      => true,
                'help'        => sprintf( __( 'Show your Notification Alerts in another website using <a target="_blank" href="%s">Cross Domain Notice</a>.', 'notificationx' ), 'https://notificationx.com/docs/notificationx-cross-domain-notice/' ),
                'default'     => '',
                'priority'    => 5,
            );

        return $tabs;
    }

    public function add_cors_http_header(){
        $acao = \NotificationX\Admin\Settings::get_instance()->get('settings.acao');
        if ( ! empty( $acao ) ) {
            header("Access-Control-Allow-Origin: $acao");
        }
    }
}
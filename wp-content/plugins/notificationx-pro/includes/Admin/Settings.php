<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Admin;

use NotificationX\Core\Rules;
use NotificationX\Admin\Settings as SettingsFree;
use NotificationXPro\Core\Helper;

/**
 * Settings Class
 */
class Settings extends SettingsFree {

    /**
     * Assets Path and URL
     */
    const ASSET_URL  = NOTIFICATIONX_PRO_ASSETS . 'admin/';
    const ASSET_PATH = NOTIFICATIONX_PRO_ASSETS_PATH . 'admin/';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct($args) {
        parent::__construct($args);
        add_filter('nx_settings_configs', [$this, 'settings_configs']);
        add_filter('nx_settings_tab', [$this, 'settings_tab']);
        add_filter('nx_settings_tab_advanced', [$this, 'tab_advanced']);
        add_filter('nx_settings_tab_email_analytics', [$this, 'reporting_settings']);

        add_action('notificationx_admin_scripts', function(){
            $d = include Helper::pro_file('admin/js/admin.asset.php');
            wp_enqueue_style( 'notificationx-pro-admin', Helper::pro_file( 'admin/css/admin.css', true ), [], $d['version'], 'all' );
            wp_enqueue_script( 'notificationx-pro-admin', Helper::pro_file( 'admin/js/admin.js', true ), $d['dependencies'], $d['version'], true );
        });
    }

    public function settings_configs( $settings ){
        $settings['submit']['rules'] = Rules::is( 'config.active', 'tab-go-license', true );
        return $settings;
    }

    public function settings_tab($tabs){

        $tabs[ 'api_integrations_tab' ] = array(
            'id' => 'tab-api-integrations',
            // 'name' => 'api_integrations_tab',
            'label' => __( 'API Integrations', 'notificationx-pro' ),
            'priority' => 90,
            'fields' => apply_filters('nx_settings_tab_api_integration', []),
            // 'views' => 'NotificationX_Settings::integrations',
            // 'rules' => Rules::is('modules.modules_cf7', true), //@todo: needs to complete it in Builder too.
        );

        $tabs['go_license_tab'] = array(
            'id' => 'tab-go-license',
            'label' => __( 'License', 'notificationx-pro' ),
            'priority' => 100,
            // 'views' => 'NotificationXPro_Settings::license',
            'fields' => [
                [
                    "name"   => 'nx_license',
                    'type'   => 'action',
                    'action' => 'nx_license'
                ]
            ],
        );
        return $tabs;
    }

    public function tab_advanced($tab){

        $tab['fields']['powered_by']['fields']['affiliate_link'] = array(
            'name' => 'affiliate_link',
            'type' => 'text',
            'label' => __('Affiliate Link', 'notificationx-pro'),
            'default' => '',
            'priority' => 11,
        );

        $tab['fields']['global_queue_management'] = array(
            'name' => 'global_queue_management',
            'label' => __( 'Global Queue Management', 'notificationx-pro' ),
            'type' => 'section',
            'priority' => 20,
            'fields' => array(
                'delay_before' => array(
                    'type'        => 'number',
                    'name'        => 'delay_before',
                    'label'       => __('Delay Before First Notification' , 'notificationx-pro'),
                    'description' => __('seconds', 'notificationx-pro'),
                    'help'        => __('Initial Delay', 'notificationx-pro'),
                    'priority'    => 1,
                    'default'       => 5,
                ),
                'display_for' => array(
                    'type'        => 'number',
                    'name'        => 'display_for',
                    'label'       => __('Display For' , 'notificationx-pro'),
                    'description' => __('seconds', 'notificationx-pro'),
                    'help'        => __('Display each notification for * seconds', 'notificationx-pro'),
                    'priority'    => 2,
                    'default'       => 5,
                ),
                'delay_between' => array(
                    'type'        => 'number',
                    'name'        => 'delay_between',
                    'label'       => __('Delay Between' , 'notificationx-pro'),
                    'description' => __('seconds', 'notificationx-pro'),
                    'help'        => __('Delay between each notification', 'notificationx-pro'),
                    'priority'    => 3,
                    'default'       => 5,
                ),
                'loop' => [
                    'name'     => 'loop',
                    'type'     => 'checkbox',
                    'label'    => __('Loop Notification', 'notificationx-pro'),
                    'priority' => 4,
                    'default'  => true,
                ],
                'random' => [
                    'name'     => 'random',
                    'type'     => 'checkbox',
                    'label'    => __('Random Notification', 'notificationx-pro'),
                    'priority' => 5,
                    'default'  => false,
                ],
            )
        );

        return $tab;
    }


    public function reporting_settings( $tab ){
        $email_reporting = &$tab['fields']['email_reporting']['fields'];
        unset( $email_reporting['reporting_frequency']['disable'] );
        unset( $email_reporting['reporting_subject']['disable'] );
        return $tab;
    }

}

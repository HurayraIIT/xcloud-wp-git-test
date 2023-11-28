<?php

namespace NotificationXPro\Core;

use NotificationX\Admin\Settings;
use NotificationX\GetInstance;

/**
 * Role Management Class for NX
 * @method static RoleManagement get_instance($args = null)
 */
class RoleManagement {
    use GetInstance;

    /**
     * Initial Invoked
     */
    public function __construct(){
        add_filter('nx_settings_tab_advanced', [$this, 'tab_advanced']);
        add_action( 'nx_settings_saved', [$this, 'add_role_caps'] );
    }

    public function tab_advanced($tab){
        if( ! current_user_can( 'delete_users' ) ) {
            unset( $tab['fields']['role_management'] );
        }
        else{
            $role_management = &$tab['fields']['role_management']['fields'];
            foreach ($role_management as $key => &$field) {
                unset($field['disable']);
            }
        }
        return $tab;
    }

    public function add_role_caps($settings){
        do_action('wpd_add_cap', Settings::get_instance()->get_role_map($settings));
    }
}
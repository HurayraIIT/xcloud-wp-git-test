<?php
/**
 * NotificationX File
 *
 * @package NotificationX
 */

namespace NotificationXPro;

use NotificationX\NotificationX as NotificationXFree;
use NotificationXPro\Core\Database;
use NotificationXPro\Core\Shortcode;
use NotificationXPro\Feature\SalesFeatures;
use NotificationXPro\Core\RoleManagement;
use NotificationXPro\Feature\Sound;
use NotificationXPro\Feature\Maps;

/**
 * Plugin Engine.
 */
final class NotificationX extends NotificationXFree {
    /**
     * Invoked initially.
     */
    public function __construct(){
        parent::__construct();
        Shortcode::get_instance();
        SalesFeatures::get_instance();
        RoleManagement::get_instance();
        Maps::get_instance();
        Sound::get_instance();

        if(get_option('nx_pro_activation_hook', false)){
            delete_option('nx_pro_activation_hook');
            $this->activator();
        }
    }

    /**
     * The Plugin Activator
     * @return void
     */
    public function activator(){
        // nx_activated
        Database::get_instance()->Create_DB();
    }

    public function init(){
        parent::init();
        load_plugin_textdomain( 'notificationx-pro', false, dirname( plugin_basename( NOTIFICATIONX_PRO_FILE ) ) . '/languages' );

    }

    public function licensing(){
        return nx_pro_licensing_manager();
    }

}

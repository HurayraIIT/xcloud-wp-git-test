<?php
/**
 * Admin Class File.
 *
 * @package NotificationX\Admin
 */

namespace NotificationXPro\Admin;

use NotificationX\Admin\Admin as AdminFree;

/**
 * Admin Class, this class is responsible for all Admin Actions
 */
final class Admin extends AdminFree {
    /**
     * Initially Invoked
     * when its initialized.
     */
    public function __construct(){
        parent::__construct();
        XSS::get_instance();
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function init(){
        parent::init();
    }

    /**
     * This method is reponsible for Admin Menu of
     * NotificationX
     *
     * @return void
     */
    public function admin_init(){
        parent::admin_init();
   }
}

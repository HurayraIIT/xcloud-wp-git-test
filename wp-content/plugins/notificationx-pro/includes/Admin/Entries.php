<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Admin;

use NotificationX\Admin\Entries as EntriesFree;

/**
 * Entries Class
 */
class Entries extends EntriesFree {
    protected $wpdb;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        global $wpdb;
        $this->wpdb = $wpdb;

    }


}

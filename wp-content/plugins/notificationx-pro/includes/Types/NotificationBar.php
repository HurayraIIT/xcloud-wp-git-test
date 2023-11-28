<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Types;

use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\Types\NotificationBar as NotificationBarFree;
/**
 * Extension Abstract for all Extension.
 */
class NotificationBar extends NotificationBarFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }


}
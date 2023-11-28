<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\Types\ContactForm as ContactFormFree;
/**
 * Extension Abstract for all Extension.
 */
class ContactForm extends ContactFormFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }


}
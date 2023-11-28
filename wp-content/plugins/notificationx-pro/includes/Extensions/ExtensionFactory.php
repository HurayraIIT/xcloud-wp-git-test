<?php

/**
 * Extension Factory
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions;

use NotificationX\GetInstance;
use NotificationX\Core\Modules;
use NotificationX\Extensions\ExtensionFactory as ExtensionFactoryFree;

/**
 * ExtensionFactory Class
 */
class ExtensionFactory extends ExtensionFactoryFree {

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        // add_filter('nx_extension_classes', [$this, 'extension_classes']);
    }

    public function extension_classes($extension_classes){
        // $extension_classes[] = '';

        return $extension_classes;
    }

}

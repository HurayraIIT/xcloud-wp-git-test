<?php
/**
 * Tutor Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\PressBar;

use NotificationX\Extensions\PressBar\PressBar as PressBarFree;

/**
 * PressBar Extension
 * @todo frontend filtering.
 */
class PressBar extends PressBarFree {
    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }
}
<?php

/**
 * LearnDash Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\LearnDash;

use NotificationX\Core\Rules;
use NotificationX\Extensions\LearnDash\LearnDash as LearnDashFree;
use NotificationX\Extensions\GlobalFields;
use NotificationXPro\Admin\Entries;

/**
 * LearnDash Extension
 */
class LearnDash extends LearnDashFree {

    use _LearnDash;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    public function public_actions(){
        parent::public_actions();

        add_filter("nx_can_entry_{$this->id}", array($this->get_type(), 'show_purchase_of'), 11, 3);
        add_action( 'learndash_update_course_access', [ $this, 'save_new_enrollment' ], 10, 2 );
    }

}

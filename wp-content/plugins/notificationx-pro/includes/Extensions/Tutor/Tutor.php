<?php
/**
 * Tutor Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\Tutor;

use NotificationX\Core\Rules;
use NotificationX\Extensions\Tutor\Tutor as TutorFree;
use NotificationX\Extensions\GlobalFields;
use NotificationXPro\Core\Helper;

/**
 * Tutor Extension
 * @todo frontend filtering.
 */
class Tutor extends TutorFree {
    use _Tutor;


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }

    public function init_fields() {
        parent::init_fields();

        add_filter('nx_elearning_course_list', [$this, 'courses']);
    }

    public function public_actions(){
        parent::public_actions();

        add_filter("nx_can_entry_{$this->id}", array($this->get_type(), 'show_purchase_of'), 10, 3);
    }

    public function courses($course_list){
        $forms  = Helper::get_post_titles_by_search($this->post_type);
        $result = GlobalFields::get_instance()->normalize_fields($forms, 'source', $this->id, $course_list);
        $result = array_values($result);
        return $result;
    }

}
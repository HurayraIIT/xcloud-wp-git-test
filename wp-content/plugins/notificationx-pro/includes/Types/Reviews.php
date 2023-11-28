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
use NotificationX\Types\Reviews as ReviewsFree;
/**
 * Extension Abstract for all Extension.
 */
class Reviews extends ReviewsFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_filter('nx_sound_options', [$this, 'sound_options']);
    }


    public function sound_options($options){
        $options = GlobalFields::get_instance()->normalize_fields([
                'review-one' => __('Sound One', 'notificationx-pro'),
                'review-two' => __('Sound Two', 'notificationx-pro'),
            ],
            'type',
            $this->id,
            $options
        );
        return $options;
    }

}
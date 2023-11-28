<?php

/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Types;

use NotificationX\Extensions\GlobalFields;
use NotificationX\Types\DownloadStats as DownloadStatsFree;
/**
 * Extension Abstract for all Extension.
 */
class DownloadStats extends DownloadStatsFree {
    public function init_fields() {
        parent::init_fields();
        add_filter('nx_sound_options', [$this, 'sound_options']);
    }

    public function sound_options($options){
        $options = GlobalFields::get_instance()->normalize_fields([
                'stats-one' => __('Sound One', 'notificationx-pro'),
                'stats-two' => __('Sound Two', 'notificationx-pro'),
            ],
            'type',
            $this->id,
            $options
        );
        return $options;
    }
}
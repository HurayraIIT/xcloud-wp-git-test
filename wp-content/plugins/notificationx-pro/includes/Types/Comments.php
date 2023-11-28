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
use NotificationX\NotificationX;
use NotificationX\Types\Comments as CommentsFree;
use NotificationXPro\Feature\Maps;

/**
 * Extension Abstract for all Extension.
 */
class Comments extends CommentsFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_filter('nx_map_fourth_param_dependency', [$this, 'map_dependency']);

        $this->themes['maps_theme']['template']     = Maps::get_instance()->get_themes( [ 'map_fourth_param' => __('commented on', 'notificationx-pro') ] );


        $map_templates = Maps::get_instance()->get_templates();
        $this->templates = array_merge($map_templates, $this->templates);


    }

    public function map_dependency($dependency){
        $dependency = array_merge($dependency, [
            "{$this->id}_maps_theme",
        ]);
        return $dependency;
    }




}
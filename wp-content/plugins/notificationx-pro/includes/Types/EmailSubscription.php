<?php
/**
 * Extension Abstract
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Types;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields as GlobalFieldsFree;
use NotificationX\GetInstance;
use NotificationX\Modules;
use NotificationX\Types\EmailSubscription as EmailSubscriptionFree;
use NotificationXPro\Extensions\GlobalFields;
use NotificationXPro\Feature\Maps;

/**
 * Extension Abstract for all Extension.
 */
class EmailSubscription extends EmailSubscriptionFree {

    public $default_theme = 'email_subscription_theme-one';

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_filter('nx_map_fourth_param_dependency', [$this, 'map_dependency']);
        add_filter('nx_sound_options', [$this, 'sound_options']);

        $this->themes['maps_theme']['template'] = Maps::get_instance()->get_themes();
    }

    /**
     * Hooked to nx_before_metabox_load action.
     *
     * @return void
     */
    public function init_fields() {
        parent::init_fields();
        // add_filter('nx_customize_fields', [$this, 'customize_fields'], 20);
    }

    public function map_dependency($dependency){
        $dependency = array_merge($dependency, [
            "{$this->id}_conv-theme-six",
            "{$this->id}_maps_theme",
        ]);
        return $dependency;
    }

    public function sound_options($options){
        $options = GlobalFields::get_instance()->normalize_fields([
                'subscription-one' => __('Sound One', 'notificationx-pro'),
                'subscription-two' => __('Sound Two', 'notificationx-pro'),
            ],
            'type',
            $this->id,
            $options
        );
        return $options;
    }

    /**
     * Undocumented function
     *
     * @param array $options
     * @return array
     */
    public function customize_fields($fields) {
        $behaviour = &$fields['behaviour']['fields'];
        $behaviour['link_open'] = Rules::is('type', $this->id, true, $behaviour['link_open']);
        return $fields;
    }

}
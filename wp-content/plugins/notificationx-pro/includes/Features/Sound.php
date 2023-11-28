<?php
namespace NotificationXPro\Feature;

use NotificationX\Core\Rules;
use NotificationX\Extensions\GlobalFields;
use NotificationX\GetInstance;

/**
 * @method static Sound get_instance($args = null)
 */
class Sound {
    /**
     * Instance of Admin
     *
     * @var Sound
     */
    use GetInstance;

    public $sound = '';
    public $sound_id = '';
    protected $audio_list;

    public function __construct(){
        add_filter( 'nx_customize_fields', array( $this, 'customize_fields' ) );
    }


    public function customize_fields( $fields ){
        $fields['sound_section'] = array(
            'label'       => __('Sound Settings', 'notificationx-pro'),
            'name'        => 'sound_section',
            'type'        => 'section',
            'priority'    => 300,
            'collapsable' => true,
            'fields'      => array(
                'sound'  => array(
                    'name'        => 'sound',
                    'type'        => 'select',
                    'label'       => __('Select a sound' , 'notificationx-pro'),
                    'description' => __('Select a notification sound to play with Notification.', 'notificationx-pro'),
                    'default'     => 'none',
                    'priority'    => 5,
                    'options'     => apply_filters('nx_sound_options', GlobalFields::get_instance()->normalize_fields([
                        'none'         => __('None', 'notificationx-pro'),
                        'to-the-point' => __('To the Point', 'notificationx-pro'),
                        'intuition'    => __('Intuition', 'notificationx-pro'),
                    ])),
                ),
                'volume'  => array(
                    'name'        => 'volume',
                    'type'        => 'slider',
                    'label'       => __('Volume' , 'notificationx-pro'),
                    'default'     => 50,
                    'priority'    => 10,
                    'rules'       => Rules::is('sound', 'none', true),
                ),
            )
        );
        return $fields;
    }

}
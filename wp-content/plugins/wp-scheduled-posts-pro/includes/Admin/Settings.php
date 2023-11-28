<?php

namespace WPSP_PRO\Admin;

use WPSP\Admin\Settings as SettingsFree;

class Settings extends SettingsFree {

    /**
     * Settings class contructor
     */
    public function __construct()
    {
        add_filter( 'wpsp_general_fields',array($this,'_general_tab_fields'));
        add_filter( 'wpsp_layout_tabs',array($this,'_wpsp_layout_tabs'));
    }

    /**
     * Modify general tab fields
     */
    public function _general_tab_fields( $fields )
    {
        unset($fields['pro_features_section']);
        return $fields;
    }

    /**
     * Modify layout tabs
     */
    public function _wpsp_layout_tabs($tabs)
    {
        $tabs['layout_license']  = [
            'id'       => 'layout_license',
            'name'     => 'layout_license',
            'label'    => __('License', 'wp-scheduled-posts'),
            'priority' => 100,
            'fields'   => [
                'license_wrapper'  => [
                    'id'        => 'license_wrapper',
                    'name'      => 'license_wrapper',
                    'type'      => 'section',
                    'label'     => false,
                    'priority'  => 5,
                    'fields'    => [
                        'license_activation'  => [
                            'id'            => 'license_activation',
                            'name'          => 'license_activation',
                            'type'          => 'section',
                            'label'         => false,
                            'fields'        => [
                                'license_title'  => [
                                    'id'            => 'license_title',
                                    'name'          => 'license_title',
                                    'type'          => 'pro-toggle',
                                    'title'         => __('Just one more step to go!', 'wp-scheduled-posts'),
                                    'sub_title'     => __('Enter your license key here, to activate SchedulePress, and get automatic updates
                                    and premium support.', 'wp-scheduled-posts'),
                                    'has_toggle'    => false,
                                    'priority'      => 5,
                                ],
                                'license_desc'  => [
                                    'id'            => 'license_desc',
                                    'name'          => 'license_desc',
                                    'type'          => 'html',
                                    'html'         => __('<p>Visit the <a href="https://wpdeveloper.com/docs/activate-wp-scheduled-posts-license/" target="_blank">Validation Guide</a> for help.
                                    ', 'wp-scheduled-posts'),
                                    'priority'      => 10,
                                ],
                                'license_list'  => [
                                    'id'            => 'license_list',
                                    'name'          => 'license_list',
                                    'type'          => 'list',
                                    'priority'      => 15,
                                    'label'         => __('Read Detailed Documentation:','wp-scheduled-posts'),
                                    'content'       => [
                                        [
                                            'text'  => __('Log in to your account to get your license key.', 'wp-scheduled-posts'),
                                        ],
                                        [
                                            'text'  => __('If you don\'t yet have a license key, get SchedulePress now.', 'wp-scheduled-posts'),
                                        ],
                                        [
                                            'text'  => __('Copy the license key from your account and paste it below.', 'wp-scheduled-posts'),
                                        ],
                                        [
                                            'text'  => __('Click on "Activate License" button.', 'wp-scheduled-posts'),
                                        ],
                                    ],
                                ],
                                'active_license'  => [
                                    'id'            => 'active_license',
                                    'name'          => 'active_license',
                                    'type'          => 'license',
                                    'label'         => __('License Key', 'wp-scheduled-posts'),
                                    'priority'      => 20,
                                ],
                            ],
                        ],
                        'advance_video'  => [
                            'id'            => 'advance_video',
                            'name'          => 'advance_video',
                            'type'          => 'section',
                            'label'         => false,
                            'priority'      => 5,
                            'fields'        => [
                                'license_video'  => [
                                    'id'            => 'license_video',
                                    'name'          => 'license_video',
                                    'type'          => 'video',
                                    'label'         => __('Watch The Video Walkthrough','wp-scheduled=-posts'),
                                    'priority'      => 5,
                                    'url'           => esc_url('https://www.youtube.com/embed/rjdf1pB0KSg'),
                                    'width'         => 554,
                                    'height'        => 345,
                                ],
                            ],
                        ],
                    ]
                ]
            ]
        ];
        return $tabs;
    }
}
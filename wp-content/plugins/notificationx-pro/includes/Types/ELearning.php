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
use NotificationX\Types\ELearning as ELearningFree;
use NotificationXPro\Feature\Maps;

/**
 * Extension Abstract for all Extension.
 */
class ELearning extends ELearningFree {



    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
        add_filter('nx_map_fourth_param_dependency', [$this, 'map_dependency']);

        $this->themes['maps_theme']['template'] = Maps::get_instance()->get_themes();
        $this->themes['maps_theme']['template']['map_fourth_param'] = __('recently enrolled', 'notificationx-pro');
        $this->themes['conv-theme-six']['template'] = Maps::get_instance()->get_themes();
        $this->themes['conv-theme-six']['template']['map_fourth_param'] = __('recently enrolled', 'notificationx-pro');

        $pro_template = [
            'first_param'         => 'tag_name',
            'custom_first_param'  => __('John D.', 'notificationx-pro'),
            'second_param'        => __('Just Enrolled in', 'notificationx-pro'),
            'third_param'         => 'tag_course_title',
            'custom_third_param'  => __('Anonymous Course', 'notificationx-pro'),
            'fourth_param'        => 'tag_time',
            'custom_fourth_param' => __('Some time ago', 'notificationx-pro'),
        ];
        $sales_count_template = [
            'first_param'         => 'tag_sales_count',
            'second_param'        => __('people enrolled', 'notificationx-pro'),
            'third_param'         => 'tag_course_title',
            'fourth_param'        => 'tag_7days',
            'custom_fourth_param' => __('in last {{day:7}}', 'notificationx'),
        ];

        $this->themes['theme-four']['template']       = $pro_template;
        $this->themes['theme-five']['template']       = $pro_template;
        $this->themes['conv-theme-seven']['template'] = $sales_count_template;
        $this->themes['conv-theme-eight']['template'] = $sales_count_template;
        $this->themes['conv-theme-nine']['template']  = $sales_count_template;


        // @todo other pro themes.
        $this->templates['elearning_template_sales_count']['first_param']['tag_sales_count'] = __('Sales Count' , 'notificationx-pro');
        $this->templates['elearning_template_sales_count']['fourth_param']['tag_1day']       = __('In last 1 day' , 'notificationx-pro');
        $this->templates['elearning_template_sales_count']['fourth_param']['tag_7days']      = __('In last 7 days' , 'notificationx-pro');
        $this->templates['elearning_template_sales_count']['fourth_param']['tag_30days']     = __('In last 30 days' , 'notificationx-pro');


        $map_templates = Maps::get_instance()->get_templates();
        $this->templates = array_merge($map_templates, $this->templates);


    }

    public function map_dependency($dependency){
        $dependency = array_merge($dependency, [
            "{$this->id}_conv-theme-six",
            "{$this->id}_maps_theme",
        ]);
        return $dependency;
    }

    public function show_purchase_of( $return, $entry, $settings ){
        if( empty( $settings['ld_product_control'] ) || $settings['ld_product_control'] === 'none' ) {
            return $return;
        }

        if( empty( $settings['ld_course_list'] ) || empty($entry['data']['product_id']) ){
            return $return;
        }

        if( !in_array( $entry['data']['product_id'], $settings['ld_course_list'] ) ) {
            return false;
        }

        return $return;
    }

}
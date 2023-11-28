<?php
/**
 * EDD Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\EDD;

use NotificationX\Core\Rules;
use NotificationX\Extensions\EDD\EDD as EDDFree;
use NotificationX\Extensions\GlobalFields as GlobalFieldsFree;
use NotificationXPro\Extensions\GlobalFields;

/**
 * EDD Extension
 * @todo normalize data for frontend.
 * @todo show_purchaseof && excludes_product
 */
class EDD extends EDDFree {

    use _EDD;

    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }


    public function init(){
        parent::init();

    }

    public function init_fields(){
        parent::init_fields();
        add_filter( 'nx_content_fields', array( $this, 'content_fields' ) );
        add_filter('nx_combine_multiorder_text_dependency', [$this, 'multiorder_text_dependency']);
        add_filter('nx_conversion_product_list', [$this, 'products']);
        add_filter('nx_conversion_category_list', [$this, 'categories']);

    }

    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
        add_filter("nx_can_entry_{$this->id}", array($this->get_type(), 'nx_can_entry'), 10, 3);
    }

    public function public_actions(){
        parent::public_actions();
        // @todo deprecated remove in the future.
        add_filter("nx_filtered_data_{$this->id}", array($this->get_type(), 'show_exclude_product'), 11, 2);
    }

    public function content_fields($fields){
        $content_fields = &$fields['content']['fields'];

        $content_fields['template'] = array(
            'type'     => 'template',
            'name'     => 'template',
            'priority' => 90,
            'defaults' => [
                __('{{name}} recently purchased', 'notificationx-pro'), '{{title}}', '{{time}}'
            ],
            'variables' => [
                '{{name}}', '{{first_name}}', '{{last_name}}', '{{title}}', '{{time}}'
            ],
            'rules'       => Rules::is( 'source', $this->id ),
        );

        return $fields;
    }


    /**
     * Adding fields in the metabox.
     *
     * @param array $args Settings arguments.
     * @return mixed
     */
    public function multiorder_text_dependency($dependency) {
        $dependency[] = $this->id;
        return $dependency;
    }



}
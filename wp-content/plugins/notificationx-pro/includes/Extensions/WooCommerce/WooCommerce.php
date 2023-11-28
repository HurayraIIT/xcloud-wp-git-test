<?php
/**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\WooCommerce;

use NotificationX\Extensions\WooCommerce\WooCommerce as WooCommerceFree;
use NotificationX\Extensions\GlobalFields;

/**
 * WooCommerce Extension Class
 */
class WooCommerce extends WooCommerceFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }



    public function init(){
        parent::init();
        // add_filter('nx_content_fields', [$this, 'content_fields']);
    }

    public function init_fields(){
        parent::init_fields();
        add_filter('nx_combine_multiorder_text_dependency', [$this, 'multiorder_text_dependency']);

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

    public function multiorder_text_dependency($dependency) {
        $dependency[] = $this->id;
        return $dependency;
    }
}
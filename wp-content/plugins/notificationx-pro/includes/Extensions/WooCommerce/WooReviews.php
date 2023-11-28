<?php
/**
 * WooCommerce Extension
 *
 * @package NotificationX\Extensions
 */

namespace NotificationXPro\Extensions\WooCommerce;

use NotificationX\Extensions\WooCommerce\WooReviews as WooReviewsFree;
use NotificationX\Extensions\GlobalFields;
use NotificationXPro\Types\Conversions;

/**
 * WooCommerce Extension Class
 */
class WooReviews extends WooReviewsFree {


    /**
     * Initially Invoked when initialized.
     */
    public function __construct() {
        parent::__construct();
    }


    /**
     * This functions is hooked
     *
     * @return void
     */
    public function admin_actions() {
        parent::admin_actions();
        add_filter("nx_can_entry_{$this->id}", array(Conversions::get_instance(), 'nx_can_entry'), 10, 3);
    }
}
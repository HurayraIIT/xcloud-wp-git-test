<?php

namespace NotificationXPro\Core;

use NotificationX\Core\Locations as LocationsFree;

class Locations extends LocationsFree {

    public function __construct(){
        parent::__construct();
        add_filter( 'nx_location_status', array( $this, 'location_status' ), 10, 2 );

    }

    /**
     * This method is responsible for display notification on specific page
     *
     * @param array $status
     * @return array
     * @since 1.1.2
     */
    public function location_status( $status, $custom_ids ) {
        $status['is_custom'] = $this->check_location_custom_ids( $custom_ids );
        return $status;
    }

    /**
     * Check if current post/page id is in the inserted id by user
     * @param array $ids
     * @return bool
     */
	public function check_location_custom_ids( $ids = '' ) {
        global $post;
		if( empty( $ids ) || empty($post) ) {
			return false;
		}
		$ids = explode(',', $ids);
        $ids = array_map('trim', $ids);
		if( is_post_type_archive( 'product' ) ) {
			if( in_array( get_option( 'woocommerce_shop_page_id' ), $ids ) ) {
				return true;
			}
		}
		return in_array( $post->ID, $ids ) ? true : false;
	}

}
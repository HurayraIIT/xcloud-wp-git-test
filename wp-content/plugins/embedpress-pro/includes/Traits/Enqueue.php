<?php

namespace Embedpress\Pro\Traits;

if (!defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

trait Enqueue
{
    public function admin_enqueue_scripts() {
	    if ( isset( $_GET['page']) && 'embedpress' === $_GET['page']  ) {
		    wp_enqueue_style(
			    'embedpress-admin-pro',
			    EMBEDPRESS_PRO_PLUGIN_URL . 'assets/css/admin-pro.css',
			    false,
			    EMBEDPRESS_PRO_PLUGIN_VERSION
		    );
	    }
    }
}

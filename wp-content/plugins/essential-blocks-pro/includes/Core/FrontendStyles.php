<?php
namespace EssentialBlocks\Pro\Core;
use EssentialBlocks\Traits\HasSingletone;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class FrontendStyles {
    use HasSingletone;

	public function __construct() {
		add_filter('eb_frontend_styles/post-grid', [$this, 'post_grid_frontend_style'], 99, 1);
		add_filter('eb_frontend_styles/countdown', [$this, 'countdown_frontend_style'], 99, 1);
	}

	public function post_grid_frontend_style($style) {
		return array_merge($style, ['essential-blocks-pro-frontend-style']);
	}

	public function countdown_frontend_style($style) {
		return array_merge($style, ['essential-blocks-pro-frontend-style']);
	}
}

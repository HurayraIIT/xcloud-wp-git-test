<?php
namespace EssentialBlocks\Pro\Core;
use EssentialBlocks\Traits\HasSingletone;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PostMeta {
    use HasSingletone;

    /**
     * Register meta
     */
    public function register_meta() {
        register_meta(
            'post',
            '_eb_data_table',
            [
                'show_in_rest'  => true,
                'single'        => true,
                'auth_callback' => [$this, 'auth_callback']
            ]
        );
    }

    /**
     * Determine if the current user can edit posts
     *
     * @return bool True when can edit posts, else false.
     */
    public function auth_callback() {
        return current_user_can( 'edit_posts' );
    }
}

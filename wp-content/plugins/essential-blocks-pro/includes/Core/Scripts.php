<?php

namespace EssentialBlocks\Pro\Core;

use EssentialBlocks\Pro\Utils\Helper;
use EssentialBlocks\Traits\HasSingletone;

class Scripts {
    use HasSingletone;

    private $is_gutenberg_editor = false;

    public $plugin = null;

    public function __construct() {
        add_action( 'init', function () {
            $this->plugin = wpdev_essential_blocks();
        }, 1 );

        //Enqueue Assets Only for FSE
        global $pagenow;
        if ( $pagenow === "site-editor.php" ) {
            add_action( 'admin_init', [$this, 'block_editor_assets'], 2 );
        }

        add_action( 'enqueue_block_editor_assets', [$this, 'block_editor_assets'] );
        add_action( 'enqueue_block_editor_assets', [$this, 'frontend_backend_assets'] );
        add_action( 'wp_enqueue_scripts', [$this, 'frontend_backend_assets'] );
        add_action( 'init', [$this, 'localize_enqueue_scripts'] );
    }

    public function block_editor_assets() {
        $this->is_gutenberg_editor = true;

        //Vendor Bundle JS Register
        wpdev_essential_blocks_pro()->assets->register( 'vendor-bundle', '../vendor-bundle/index.js' );

        wpdev_essential_blocks_pro()->assets->enqueue( 'editor-script', '../dist/index.js', [
            'essential-blocks-pro-vendor-bundle',
            'essential-blocks-vendor-bundle',
            'essential-blocks-controls-util'
        ] );

        //If vendor files has css and extists
        if ( file_exists( ESSENTIAL_BLOCKS_PRO_DIR_PATH . 'vendor-bundle/style.css' ) ) {
            wpdev_essential_blocks_pro()->assets->register( 'admin-vendor-style', '../vendor-bundle/style.css' );
        }

        //Other CSS
        wpdev_essential_blocks_pro()->assets->register(
            'editor-style-data-table',
            '../assets/css/admin/controls.css'
        );

        // enqueue controls styles
        wpdev_essential_blocks_pro()->assets->register(
            'editor-style',
            '../dist/style.css',
            [
                'essential-blocks-editor-css',
                'essential-blocks-pro-editor-style-data-table',
                'essential-blocks-pro-admin-vendor-style'
            ]
        );
    }

    /**
     * enqueue/register assets files in frontend/backend
     * @return void
     */
    public function frontend_backend_assets() {
        // register styles
        wpdev_essential_blocks_pro()->assets->register( 'frontend-style', '../dist/style.css' );
        //If vendor files has css and extists
        if ( file_exists( ESSENTIAL_BLOCKS_PRO_DIR_PATH . 'vendor-bundle/style.css' ) ) {
            wpdev_essential_blocks_pro()->assets->register( 'vendor-style', '../vendor-bundle/style.css' );
        }
        //Vendor Bundle JS Register
        wpdev_essential_blocks_pro()->assets->register( 'vendor-bundle', '../vendor-bundle/index.js' );

        //Google reCaptcha Script
        // wpdev_essential_blocks_pro()->assets->register( 'recaptcha', 'https://www.google.com/recaptcha/api.js' );
        $recaptchaType = Helper::get_recaptcha_settings( 'recaptchaType' );
        if ( $recaptchaType === 'v3' ) {
            wp_register_script( 'essential-blocks-pro-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . Helper::get_recaptcha_settings( 'siteKey' ) );
        } else if ( $recaptchaType === 'v2-checkbox' || $recaptchaType === 'v2-invisible' ) {
            wp_register_script( 'essential-blocks-pro-recaptcha', 'https://www.google.com/recaptcha/api.js' );
        }
    }

    /**
     * enqueue localize scripts
     * @return void
     */
    public function localize_enqueue_scripts() {
        wpdev_essential_blocks()->assets->enqueue( 'blocks-localize', 'js/eb-blocks-localize.js' );

        $pro_localize_array = [
            'eb_pro_plugins_url'     => ESSENTIAL_BLOCKS_PRO_URL,
            'eb_pro_version'         => ESSENTIAL_BLOCKS_PRO_VERSION,
            'ajax_url'               => admin_url( 'admin-ajax.php' ),
            'adv_search_nonce'       => wp_create_nonce( 'eb-adv-search-nonce' ),
            'eb_dynamic_tags'        => ESSENTIAL_BLOCKS_DYNAMIC_TAGS,
            'data_table_nonce'       => wp_create_nonce( 'eb-data-table-nonce' ),
            'recaptcha_type'         => Helper::get_recaptcha_settings( 'recaptchaType' ),
            'post_grid_search_nonce' => wp_create_nonce( 'eb-post-grid-search-nonce' )
        ];
        if ( is_admin() ) {
            $admin_pro_localize_array = [
                'admin_nonce' => wp_create_nonce( 'eb-pro-admin-nonce' )
            ];

            $pro_localize_array = array_merge( $pro_localize_array, $admin_pro_localize_array );
        }

        wpdev_essential_blocks()->assets->localize( 'blocks-localize', 'EssentialBlocksProLocalize', $pro_localize_array );
    }
}

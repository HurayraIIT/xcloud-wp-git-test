<?php

namespace WPDeveloper\BetterDocs\Editors;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

use WPDeveloper\BetterDocs\Editors\BlockEditor\StyleHandler;
use WPDeveloper\BetterDocs\Editors\BlockEditor\TemplatesController;

class BlockEditor extends BaseEditor {

    public function init() {
        // TemplatesController::get_instance();

        add_action( 'admin_init', [$this, 'enqueue_styles'] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_styles'] );

        $_blocks_category_hook = version_compare( get_bloginfo( 'version' ), '5.8', '>=' ) ? 'block_categories_all' : 'block_categories';
        add_filter( $_blocks_category_hook, [$this, 'register_category'], 9, 2 );

        StyleHandler::get_instance();

        $this->register_blocks();
    }

    public function admin_init() {
        $blocks = $this->get_blocks();

        if ( empty( $blocks ) || ! is_array( $blocks ) ) {
            return;
        }

        foreach ( $blocks as $block_name => $block ) {
            if ( isset( $block['object'] ) ) {
                $block_object = betterdocs()->container->get( $block['object'] );

                if ( ! $block_object->can_enable() ) {
                    continue;
                }

                $block_object->register_scripts();
            }
        }
    }

    /**
     * Only for Admin Add/Edit Pages
     */
    public function enqueue_styles( $hook ) {
        $editor = 'core/edit-post';
        if ( $hook == 'site-editor.php' || ( $hook == 'themes.php' && isset( $_GET['page'] ) && $_GET['page'] == 'gutenberg-edit-site' ) ) {
            $editor = 'core/edit-site';
        }

        $this->assets->register( 'betterdocs-blocks-editor-controls', 'blocks/controls.css' );
        $this->assets->register( 'betterdocs-blocks-editor', 'blocks/style-editor.css', ['betterdocs-blocks-editor-controls'] );
        $this->assets->register( 'betterdocs-blocks-editor', 'blocks/editor.js', ['betterdocs-blocks-style-handler'] );
        $this->assets->localize( 'betterdocs-blocks-editor', 'betterDocsBlocksHelper', [
            'is_pro_active' => betterdocs()->is_pro_active(),
            'resturl'       => get_rest_url(),
            'editorType'    => $editor
        ] );

        if ( $hook == 'post-new.php' || $hook == 'post.php' || $hook == 'site-editor.php' ) {
            $this->assets->enqueue( 'fontpicker-default-theme', 'vendor/css/fonticonpicker.base-theme.react.css' );
            $this->assets->enqueue( 'fontpicker-material-theme', 'vendor/css/fonticonpicker.material-theme.react.css' );
        }
    }

/**
 * Add a block category
 *
 * @param array $categories Block categories.
 *
 * @return array
 */
    public function register_category( $block_categories, $block_editor_context ) {
        $categories_slugs = wp_list_pluck( $block_categories, 'slug' );

        return in_array( 'betterdocs', $categories_slugs, true ) ? $block_categories : array_merge( [[
            'slug'  => 'betterdocs',
            'title' => __( 'Betterdocs', 'betterdocs' )
        ]], $block_categories );
    }

    /**
     * Get Blocks
     *
     * @since 2.5.0
     * @return array<array>
     */
    public function get_blocks() {
        $config_array = require_once BETTERDOCS_ABSPATH . 'includes/blocks.php';
        return apply_filters( 'betterdocs_blocks_config', $config_array );
    }

    public function register_blocks( $enqueue = false ) {
        $blocks = $this->get_blocks();

        if ( empty( $blocks ) || ! is_array( $blocks ) ) {
            return;
        }

        foreach ( $blocks as $block_name => $block ) {
            if ( isset( $block['object'] ) ) {
                $block_object = betterdocs()->container->get( $block['object'] );

                if ( ! $block_object->can_enable() ) {
                    continue;
                }

                if ( method_exists( $block_object, 'load_dependencies' ) ) {
                    $block_object->load_dependencies();
                }

                if ( $enqueue && method_exists( $block_object, 'enqueue' ) ) {
                    $block_object->enqueue( $this->assets );
                    continue;
                }

                if ( method_exists( $block_object, 'inner_blocks' ) ) {
                    $_inner_blocks = $block_object->inner_blocks();
                    foreach ( $_inner_blocks as $block_name => $block ) {
                        $block->register( $this->assets );
                    }
                }

                $block_object->register( $this->assets );
            }
        }
    }
}

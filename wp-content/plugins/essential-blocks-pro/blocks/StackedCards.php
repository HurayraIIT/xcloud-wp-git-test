<?php
namespace EssentialBlocks\Pro\blocks;

use EssentialBlocks\Core\Block;

class StackedCards extends Block {
    protected $is_pro           = true;
    protected $editor_scripts   = 'essential-blocks-pro-editor-script';
    protected $editor_styles    = 'essential-blocks-pro-editor-style';
    protected $frontend_scripts = ['essential-blocks-pro-stacked-cards-frontend'];
    protected $frontend_styles  = ['essential-blocks-pro-frontend-style'];

    /**
     * Unique name of the block.
     * @return string
     */
    public function get_name() {
        return 'pro-stacked-cards';
    }

    /**
     * Register all other scripts
     * @return void
     */
    public function register_scripts() {
        wpdev_essential_blocks_pro()->assets->register(
            'stacked-cards-frontend',
            $this->path() . '/frontend/index.js'
        );
    }

}

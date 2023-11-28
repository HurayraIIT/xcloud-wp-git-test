<?php
namespace EssentialBlocks\Pro\blocks;

use EssentialBlocks\Core\Block;

class DateTimePicker extends Block {
    protected $is_pro           = true;
    protected $editor_scripts   = 'essential-blocks-pro-editor-script';
    protected $editor_styles    = 'essential-blocks-pro-editor-style';
    protected $frontend_styles  = ['essential-blocks-pro-frontend-style', 'essential-blocks-pro-vendor-style'];
    protected $frontend_scripts = ['essential-blocks-pro-datetime-frontend'];

    /**
     * Unique name of the block.
     * @return string
     */
    public function get_name() {
        return 'form-datetime-picker';
    }

    /**
     * Register all other scripts
     * @return void
     */
    public function register_scripts() {
        wpdev_essential_blocks_pro()->assets->register(
            'datetime-frontend',
            $this->path() . '/frontend/index.js',
            ['essential-blocks-pro-vendor-bundle']
        );
    }
}

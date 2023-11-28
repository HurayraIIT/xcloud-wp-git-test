<?php
namespace EssentialBlocks\Pro\blocks;

use EssentialBlocks\Core\Block;

class PricingCell extends Block {
    protected $is_pro = true;
    // protected $editor_scripts   = 'essential-blocks-pro-editor-script';
    // protected $editor_styles    = 'essential-blocks-pro-editor-style';
    protected $frontend_styles = ['essential-blocks-pro-frontend-style'];
    // protected $frontend_scripts = ['essential-blocks-pro-multicolumn-pricing-table-frontend'];

    /**
     * Unique name of the block.
     * @return string
     */
    public function get_name() {
        return 'pro-pricing-cell';
    }
}

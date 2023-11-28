<?php
namespace EssentialBlocks\Pro\blocks;

use EssentialBlocks\blocks\Form;

use EssentialBlocks\Pro\blocks\DateTimePicker;
use EssentialBlocks\Pro\blocks\Recaptcha;

class ProForm extends Form {
    /**
     * Initialize the InnerBlocks for Accordion
     * @return array
     */
    public function inner_blocks() {
        return array_merge(parent::inner_blocks(), [
            DateTimePicker::get_instance(),
            Recaptcha::get_instance()
        ]);
    }
}

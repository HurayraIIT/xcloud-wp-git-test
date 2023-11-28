<div class="better-payment--wrapper">
    <div class="better-payment--container">
        <div class="better_payment__form--one better-payment-form-layout <?php echo esc_attr($better_payment_placeholder_class); ?>">
            <form name="better-payment-form-<?php echo esc_attr($this->get_id()); ?>" data-better-payment="<?php echo esc_attr($setting_meta); ?>" class="better-payment-form" id="better-payment-form-<?php echo esc_attr($this->get_id()); ?>" action="<?php echo esc_url($action); ?>" method="post">
                <input type="hidden" name="better_payment_page_id" value="<?php echo get_the_ID(); ?>">
                <input type="hidden" name="better_payment_widget_id" value="<?php echo esc_attr($this->get_id()); ?>">

                <?php 
                if ( !empty( $settings['better_payment_form_fields'] ) ) :
                    foreach (  $settings['better_payment_form_fields'] as $item ) :
                        if ( !empty( $item["better_payment_field_name_show"] ) && 'yes' !== $item["better_payment_field_name_show"] ) {
                            continue;
                        }
                        $is_item_required = !empty( $item["better_payment_field_name_required"] ) && 'yes' === $item["better_payment_field_name_required"] ? 1 : 0;
                        $required_class = $is_item_required ? ' required' : '';
                        $required_placeholder = $is_item_required ? ' *' : '';
    
                        $layout_better_payment_helper_class = new \Better_Payment\Lite\Classes\Better_Payment_Helper();
                        
                        $render_attribute_name = $layout_better_payment_helper_class->titleToSnake($item["better_payment_field_name_heading"]);
                        $render_attribute_class = "bp-form__control " . $required_class;
                        $render_attribute_placeholder = !empty( $item["better_payment_field_name_placeholder"] ) ? $item["better_payment_field_name_placeholder"] . $required_placeholder : $required_placeholder;
                        $render_attribute_type = !empty( $item["better_payment_field_type"] ) ? $item["better_payment_field_type"] : 'text';
                        $render_attribute_required = $is_item_required ? 'required' : '';
    
                    ?>
                        <div class="bp-form__group elementor-repeater-item-<?php echo esc_attr($item['_id']); ?>">
                            <input class="bp-form__control <?php echo esc_attr($required_class); ?>" type="<?php echo esc_attr($render_attribute_type); ?>" name="<?php echo esc_attr($render_attribute_name); ?>" class="<?php echo esc_attr($render_attribute_class); ?>" placeholder="<?php echo esc_attr($render_attribute_placeholder); ?>" <?php if ($render_attribute_required) : ?> required="<?php echo esc_attr($render_attribute_required); ?>" <?php endif; ?>>
                        </div>
                    <?php 
                    endforeach;
                endif;
                ?>

                <div class="bp-payment-amount-wrap mb30">
                    <?php
                    if (!empty($settings['better_payment_show_amount_list']) && 'yes' === $settings['better_payment_show_amount_list']) {
                        $this->render_amount_element($settings);
                    }
                    ?>

                    <?php $payment_field = (!empty($settings['better_payment_show_payment_amount_field']) && 'yes' === $settings['better_payment_show_payment_amount_field']) ? 'number' : 'hidden'; ?>
                    <?php $payment_field_hide_show = 'hidden' === $payment_field ? ' is-hidden' : ''; ?>
                    
                    <div class="bp-form__group <?php esc_attr_e($payment_field_hide_show, 'better-payment'); ?> ">
                        <input type="<?php echo esc_attr($payment_field); ?>" name="payment_amount" class="bp-form__control bp-custom-payment-amount" placeholder="" required min="1">
                    </div>    
                </div>

                <div class="payment__option">
                    <?php

                    if ($settings['better_payment_form_paypal_enable'] == 'yes') {
                        echo Better_Payment\Lite\Classes\Better_Payment_Handler::paypal_button(esc_attr($this->get_id()), $settings);
                    }

                    if ($settings['better_payment_form_stripe_enable'] == 'yes') {
                        echo Better_Payment\Lite\Classes\Better_Payment_Handler::stripe_button(esc_attr($this->get_id()), $settings);
                    }

                    ?>
                </div>
            </form>
        </div>
    </div>
</div>
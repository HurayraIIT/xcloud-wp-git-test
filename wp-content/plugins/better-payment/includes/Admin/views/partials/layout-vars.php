<?php
$layout_action = !empty($extraDatas['action']) ? $extraDatas['action'] : '';
$layout_setting_meta = !empty($extraDatas['setting_meta']) ? $extraDatas['setting_meta'] : '';
$layout_dynamic_payment_hide_show = !empty($settings["better_payment_form_payment_source"]) && 'woocommerce' !== $settings["better_payment_form_payment_source"] ? 'is-hidden' : '';
$layout_put_amount_field_hide_show = !empty($settings["better_payment_form_payment_source"]) && 'woocommerce' === $settings["better_payment_form_payment_source"] ? 'is-hidden' : '';
$layout_form_content_offset_class = !empty($settings["better_payment_form_payment_source"]) && 'woocommerce' !== $settings["better_payment_form_payment_source"] ? '' : ''; //is-offset-2

$layout_form_transaction_details_heading = !empty($settings["better_payment_form_transaction_details_heading"]) ? $settings["better_payment_form_transaction_details_heading"] : '';
$layout_form_transaction_details_sub_heading = !empty($settings["better_payment_form_transaction_details_sub_heading"]) ? $settings["better_payment_form_transaction_details_sub_heading"] : '';
$layout_form_transaction_details_product_title = !empty($settings["better_payment_form_transaction_details_product_title"]) ? $settings["better_payment_form_transaction_details_product_title"] : '';
$layout_form_transaction_details_amount_text = !empty($settings["better_payment_form_transaction_details_amount_text"]) ? $settings["better_payment_form_transaction_details_amount_text"] : '';
$is_payment_type_woocommerce = !empty($settings["better_payment_form_payment_source"]) && 'woocommerce' === $settings["better_payment_form_payment_source"] ? true : false;

$layout_form_currency = !empty($settings["better_payment_form_currency"]) ? $settings["better_payment_form_currency"] : '';
$layout_form_currency_symbol = $layout_form_currency ? Better_Payment\Lite\Classes\Better_Payment_Handler::get_currency_symbols(esc_html($layout_form_currency)) : '<i class="bp-icon bp-logo-2"></i>';

$currency_alignment             = ! empty ( $settings['better_payment_form_currency_alignment'] ) ? $settings['better_payment_form_currency_alignment'] : 'left';
$layout_form_currency_left      = 'left'    === $currency_alignment ? $layout_form_currency_symbol : '';
$layout_form_currency_right     = 'right'   === $currency_alignment ? $layout_form_currency_symbol : '' ;

$product_name = '';
$product_permalink = '';
$product_price = '';
$payment_amount_field_exists = 0;
$is_payment_amount_field_hidden = 0;

if ($is_payment_type_woocommerce) {
    if( 
        !empty($settings['better_payment_form_currency_use_woocommerce']) && 'yes' === $settings['better_payment_form_currency_use_woocommerce'] &&
        !empty($settings['better_payment_form_currency_woocommerce'])
    ) {
        $layout_form_currency_symbol = \Better_Payment\Lite\Classes\Better_Payment_Handler::get_currency_symbols( esc_html($settings['better_payment_form_currency_woocommerce']) );
    }

    //Fetch product data using product ID
    $layout_form_woocommerce_product_id = !empty($settings["better_payment_form_woocommerce_product_id"]) ? intval($settings["better_payment_form_woocommerce_product_id"]) : 0;

    if (function_exists('wc_get_product')) {
        $bp_woocommerce_product = wc_get_product($layout_form_woocommerce_product_id);
        $product = $bp_woocommerce_product;

        if ($product) {
            $product_name = $product->get_name();
            $product_permalink = get_permalink($product->get_id());
            $product_price = $product->get_price();
        }
    }
}

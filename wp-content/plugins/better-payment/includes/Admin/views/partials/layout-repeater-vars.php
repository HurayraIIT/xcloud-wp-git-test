<?php
$item_primary_field_type = !empty($item["better_payment_primary_field_type"]) ? $item["better_payment_primary_field_type"] : "";

$is_primary_first_name_field = 'primary_first_name' === $item_primary_field_type ? 1 : 0;
$is_primary_last_name_field = 'primary_last_name' === $item_primary_field_type ? 1 : 0;
$is_primary_email_field = 'primary_email' === $item_primary_field_type ? 1 : 0;
$is_payment_amount_field = 'primary_payment_amount' === $item_primary_field_type ? 1 : 0;

$is_item_required = !empty($item["better_payment_field_name_required"]) && 'yes' === $item["better_payment_field_name_required"] ? 1 : 0;
$required_class = $is_item_required ? ' required' : '';
$required_placeholder = $is_item_required ? ' *' : '';

$render_attribute_name = $better_payment_helper_obj->titleToSnake($item["better_payment_field_name_heading"]);

$render_attribute_class = $required_class;
$render_attribute_placeholder = !empty($item["better_payment_field_name_placeholder"]) ? $item["better_payment_field_name_placeholder"] . $required_placeholder : $required_placeholder;
$render_attribute_type = !empty($item["better_payment_field_type"]) ? $item["better_payment_field_type"] : 'text';
$render_attribute_required = $is_item_required ? 'required' : '';

$render_attribute_icon = ('email' === $render_attribute_type) ? 'bp-icon bp-envelope' : 'bp-icon bp-user';
$layout_show_image = 0;

if (!empty($item['better_payment_field_icon']['library'])) {
    if ($item['better_payment_field_icon']['library'] == 'svg') {
        $layout_show_image = 1;
        $render_attribute_icon = $item['better_payment_field_icon']['value']['url'];
    } else {
        $render_attribute_icon = $item['better_payment_field_icon']['value'];
    }
}

if ($is_payment_amount_field) {
    $payment_amount_field_exists = 1;
    $render_attribute_class .= ' bp-custom-payment-amount ';
    $render_attribute_type = 'number';
    $render_attribute_required = 'required';
    $required_placeholder = ' *';
}

if ($is_primary_email_field) {
    $render_attribute_type = 'email';
    $render_attribute_required = 'required';
    $render_attribute_placeholder = $item["better_payment_field_name_placeholder"] . ' *';
}

if (!empty($item_primary_field_type)) {
    if (
        $is_primary_first_name_field ||
        $is_primary_last_name_field ||
        $is_primary_email_field ||
        $is_payment_amount_field
    ) {
        $render_attribute_name = $item_primary_field_type;
    }
}

$payment_amount_field_class = $is_payment_amount_field && $is_payment_type_woocommerce ? 'is-hidden' : '';
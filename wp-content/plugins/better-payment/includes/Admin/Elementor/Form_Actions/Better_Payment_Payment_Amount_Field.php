<?php

namespace Better_Payment\Lite\Admin\Elementor\Form_Actions;

use Better_Payment\Lite\Admin\Better_Payment_DB;
use Better_Payment\Lite\Classes\Better_Payment_Handler;
use Elementor\Widget_Base;
use ElementorPro\Modules\Forms\Classes;
use Elementor\Controls_Manager;
use ElementorPro\Modules\Forms\Fields\Field_Base;
use ElementorPro\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Payment Amount field class
 *
 * @since 0.0.1
 */
class Better_Payment_Payment_Amount_Field extends Field_Base {

	public function get_type() {
		return 'payment_amount';
	}

	public function get_name() {
		return __( 'Payment Amount', 'better-payment' );
	}

	public function render( $item, $item_index, $form ) {
		$item['custom_id'] = $this->get_type();

		$form->add_render_attribute( 'input' . $item['custom_id'], [
			'name' => $this->get_attribute_name( $item ),
			'id' => $this->get_attribute_id( $item ),
		] );

		if ( $item['bp_placeholder'] ) {
			$form->add_render_attribute( 'input' . $item['custom_id'], 'placeholder', $item['bp_placeholder'] );
		}

		$form->add_render_attribute( 'input' . $item['custom_id'], 'required', 'required' );
		$form->add_render_attribute( 'input' . $item['custom_id'], 'aria-required', 'true' );

		if ( isset( $item['bp_field_min'] ) ) {
			$form->add_render_attribute( 'input' . $item['custom_id'], 'min', esc_attr( $item['bp_field_min'] ) );
		}
		
		if ( isset( $item['bp_field_max'] ) ) {
			$form->add_render_attribute( 'input' . $item['custom_id'], 'max', esc_attr( $item['bp_field_max'] ) );
		}

		$better_payment_form_currency = $this->get_better_payment_form_currency($form);
        $better_payment_form_currency_symbol = Better_Payment_Handler::get_currency_symbols( $better_payment_form_currency );
            
		$bp_payment_amount_input = '<input type="number" class="elementor-field elementor-size-sm elementor-field-textual bp-elementor-field-textual-amount" ' . $form->get_render_attribute_string( 'input' . $item['custom_id'] ) . '>';

		$bp_payment_amount_html = "<div class='bp-input-group'>
									<div class='bp-input-group-prepend'>
										<div class='bp-input-group-text' title='$better_payment_form_currency'>$better_payment_form_currency_symbol</div>
									</div>
									$bp_payment_amount_input
								</div>";

		$bp_payment_amount_html_v2 = "<div class='better-payment is-full-width'>
										<div class='better-payment-field-advanced-layout field-primary_payment_amount elementor-repeater-item-".$this->get_attribute_id( $item )."'>
											<div class='control has-icons-left'>
												<input class='input is-medium required bp-custom-payment-amount bp-custom-payment-amount-el-integration' type='number' placeholder='" . esc_attr($item['bp_placeholder']) . "' name='" . esc_attr($this->get_attribute_name( $item )) . "' required='required' min='1' value='' >
												<span class='icon is-medium is-left'>
													<span class='bp-currency-symbol'>" . esc_html($better_payment_form_currency_symbol) . "</span>
												</span>
											</div>
										</div>
									</div>";

		$allowed_html = array(
			'a'      => array(
				'id'  => array(),
				'class'  => array(),
				'href'  => array(),
				'title' => array(),
			),
			'br'     => array(),
			'em'     => array(),
			'strong' => array(),
			'div' => array(
				'class'  => array(),
				'id'  => array(),
				'title' => array(),
			),
			'input' => array(
				'id' => array(),
				'class' => array(),
				'name' => array(),
				'type'  => array(),
				'placeholder' => array(),
				'aria-required' => array(),
				'required' => array(),
				'min' => array(),
				'max' => array(),
			),
			'span' => array(
				'class'  => array(),
				'id'  => array(),
				'title' => array(),
			),

		);

		echo wp_kses($bp_payment_amount_html_v2, $allowed_html) ;
	}

	/**
	 * @param Widget_Base $widget
	 */
	public function update_controls( $widget ) {
		$elementor = Plugin::elementor();

		$control_data = $elementor->controls_manager->get_control_from_stack( $widget->get_unique_name(), 'form_fields' );

		if ( is_wp_error( $control_data ) ) {
			return;
		}
		
		$field_controls = [
			'bp_payment_field_usage_notice' => [
				'name' => 'bp_payment_field_usage_notice',
				'raw' => sprintf(
					esc_html__( 'To display this field, make sure to enable the **Payment Amount** field from Better Payment Settings below', 'better-payment' ),
				),
				'content_classes' => 'elementor-descriptor',
				'type' => Controls_Manager::RAW_HTML,
				'condition' => [
					'field_type' => $this->get_type(),
				],
				'tab' => 'content',
				'inner_tab' => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			],
			'bp_placeholder' => [
				'name' => 'bp_placeholder',
				'label' => __( 'Placeholder', 'better-payment' ),
				'type' => Controls_Manager::TEXT,
				'condition' => [
					'field_type' => $this->get_type(),
				],
				'tab' => 'content',
				'inner_tab' => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			],
			'bp_field_min' => [
				'name' => 'bp_field_min',
				'label' => __( 'Min. Value', 'better-payment' ),
				'type' => Controls_Manager::NUMBER,
				'condition' => [
					'field_type' => $this->get_type(),
				],
				'tab' => 'content',
				'inner_tab' => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			],
			'bp_field_max' => [
				'name' => 'bp_field_max',
				'label' => __( 'Max. Value', 'better-payment' ),
				'type' => Controls_Manager::NUMBER,
				'condition' => [
					'field_type' => $this->get_type(),
				],
				'tab' => 'content',
				'inner_tab' => 'form_fields_content_tab',
				'tabs_wrapper' => 'form_fields_tabs',
			],
		];
		
		foreach ( $control_data['fields'] as $index => $field ) {
			if ( 'required' === $field['name'] ) {
				$control_data['fields'][ $index ]['conditions']['terms'][] = [
					'name' => 'field_type',
					'operator' => '!in',
					'value' => [
						'payment_amount',
					],
				];
			}
		}
		
		$control_data['fields'] = $this->inject_field_controls( $control_data['fields'], $field_controls );
		
		$widget->update_control( 'form_fields', $control_data );
	}

	public function validation( $field, Classes\Form_Record $record, Classes\Ajax_Handler $ajax_handler ) {

		if ( ! empty( $field['bp_field_max'] ) && $field['bp_field_max'] < (int) $field['value'] ) {
			$ajax_handler->add_error( $field['id'], sprintf( __( 'The value must be less than or equal to %s', 'better-payment' ), esc_html( $field['bp_field_max'] ) ) );
		}

		if ( ! empty( $field['bp_field_min'] ) && $field['bp_field_min'] > (int) $field['value'] ) {
			$ajax_handler->add_error( $field['id'], sprintf( __( 'The value must be greater than or equal %s', 'better-payment' ), esc_html( $field['bp_field_min'] ) ) );
		}
	}

	public function sanitize_field( $value, $field ) {
		return intval( $value );
	}

	public function get_attribute_name( $item ) {
		return "form_fields[{$item['custom_id']}]";
	}

	public function get_attribute_id( $item ) {
		return 'form-field-' . $item['custom_id'];
	}

	public function get_better_payment_form_currency($form){
        $instance = $form->get_settings_for_display();
        $submit_actions = $instance['submit_actions'];
        
        $better_payment_global_currency = Better_Payment_DB::get_settings('better_payment_settings_general_general_currency'); //USD
        $better_payment_global_currency = !empty($better_payment_global_currency) ? esc_html( $better_payment_global_currency ) : 'USD';
        
        if(in_array('paypal', $submit_actions)){
            $better_payment_form_currency = isset($instance['better_payment_form_paypal_currency']) ? esc_html($instance['better_payment_form_paypal_currency']) : esc_html($better_payment_global_currency);            
        }else {
			$better_payment_form_currency = isset($instance['better_payment_form_stripe_currency']) ? esc_html($instance['better_payment_form_stripe_currency']) : esc_html($better_payment_global_currency);
        }
        
        return $better_payment_form_currency;
    }
}

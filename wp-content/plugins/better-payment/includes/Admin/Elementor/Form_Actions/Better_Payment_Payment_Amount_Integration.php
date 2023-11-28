<?php

namespace Better_Payment\Lite\Admin\Elementor\Form_Actions;

use Better_Payment\Lite\Admin\Better_Payment_DB;
use Elementor\Controls_Manager;
use Elementor\Core\Kits\Documents\Tabs\Global_Colors;
use Elementor\Core\Kits\Documents\Tabs\Global_Typography;
use Elementor\Group_Control_Typography;
use ElementorPro\Modules\Forms\Classes\Action_Base;
use ElementorPro\Modules\Forms\Classes\Ajax_Handler;
use ElementorPro\Modules\Forms\Classes\Form_Record;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Payment Amount integration class
 *
 * @since 0.0.1
 */
class Better_Payment_Payment_Amount_Integration extends Action_Base {
    private $better_payment_global_settings = [];

    public function get_name() {
        return '';
    }

    public function get_label() {
        return __( 'Better Payment', 'better-payment' );
    }

    /**
     * @param \Elementor\Widget_Base $widget
     */
    public function register_settings_section( $widget ) {
        $this->better_payment_global_settings = Better_Payment_DB::get_settings();

        $widget->start_controls_section(
            'section_better_payment_payment_amount',
            [
                'label'     => __( 'Better Payment', 'better-payment' ),
                'condition' => [
                ],
            ]
        );
        
        $widget->add_control(
            'better_payment_payment_amount_enable',
            [
                'label'        => __( 'Payment Amount Field', 'better-payment' ),
                'description'        => __( 'We add an extra field type <b>Payment Amount</b> which offers you to accept payment via Paypal and Stripe. Disable it if you want to hide the field type.<br><br>Don\'t forget to add PayPal or Stripe on <strong>Actions After Submit</strong>', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'no',
            ]
        );

        $widget->add_control(
            'better_payment_payment_amount_style',
            [
                'label'        => __( 'Field Style', 'better-payment' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'better-payment' ),
                'label_off'    => __( 'No', 'better-payment' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'condition' => [
                    'better_payment_payment_amount_enable' => 'yes',
                ],
            ]
        );

        $widget->add_control(
			'better_payment_field_text_color',
			[
				'label' => esc_html__( 'Text Color', 'better-payment' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group .elementor-field.bp-elementor-field-textual-amount' => 'color: {{VALUE}};',
				],
				'global' => [
					'default' => Global_Colors::COLOR_TEXT,
				],
                'condition' => [
                    'better_payment_payment_amount_style' => 'yes',
                ],
			]
		);

		$widget->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name' => 'better_payment_field_typography',
				'selector' => '{{WRAPPER}} .elementor-field-group .elementor-field.bp-elementor-field-textual-amount',
				'global' => [
					'default' => Global_Typography::TYPOGRAPHY_TEXT,
				],
                'condition' => [
                    'better_payment_payment_amount_style' => 'yes',
                ],
			]
		);

		$widget->add_control(
			'better_payment_field_background_color',
			[
				'label' => esc_html__( 'Background Color', 'better-payment' ),
				'type' => Controls_Manager::COLOR,
				'default' => '#ffffff',
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group .elementor-field.bp-elementor-field-textual-amount' => 'background-color: {{VALUE}};',
				],
				'separator' => 'before',
                'condition' => [
                    'better_payment_payment_amount_style' => 'yes',
                ],
			]
		);

		$widget->add_control(
			'better_payment_field_border_color',
			[
				'label' => esc_html__( 'Border Color', 'better-payment' ),
				'type' => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group.elementor-field-type-payment_amount .bp-input-group' => 'border-color: {{VALUE}};border-style:solid;',
				],
				'separator' => 'before',
                'condition' => [
                    'better_payment_payment_amount_style' => 'yes',
                ],
			]
		);

		$widget->add_control(
			'better_payment_field_border_width',
			[
				'label' => esc_html__( 'Border Width', 'better-payment' ),
				'type' => Controls_Manager::DIMENSIONS,
				'placeholder' => '1',
				'size_units' => [ 'px' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group.elementor-field-type-payment_amount .bp-input-group' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
                'condition' => [
                    'better_payment_payment_amount_style' => 'yes',
                ],
			]
		);

		$widget->add_control(
			'better_payment_field_border_radius',
			[
				'label' => esc_html__( 'Border Radius', 'better-payment' ),
				'type' => Controls_Manager::DIMENSIONS,
				'size_units' => [ 'px', '%' ],
				'selectors' => [
					'{{WRAPPER}} .elementor-field-group.elementor-field-type-payment_amount .bp-input-group' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
				],
                'condition' => [
                    'better_payment_payment_amount_style' => 'yes',
                ],
			]
		);
        
        $widget->end_controls_section();
    }

    /**
     * @param array $element
     * @return array
     */
    public function on_export( $element ) {
        unset(
            $element[ 'settings' ][ 'better_payment_payment_amount_enable' ]
        );

        return $element;
    }

    /**
     * @param Form_Record $record
     * @param Ajax_Handler $ajax_handler
     */
    public function run( $record, $ajax_handler ) {
        //Silence is golden!
        wp_enqueue_style( 'better-payment-el' );
        wp_enqueue_style( 'bp-icon-front' );
        wp_enqueue_style( 'better-payment-style' );
        wp_enqueue_style( 'better-payment-common-style' );
        wp_enqueue_style( 'better-payment-admin-style' );
        
        wp_enqueue_script( 'better-payment-common-script' );
        wp_enqueue_script( 'better-payment' );
    }
}



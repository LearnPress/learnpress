<?php
/**
 * Fields settings PayPal Payment
 */

return apply_filters(
	'learn-press/gateway-payment/paypal/settings',
	array(
		array(
			'type' => 'title',
		),
		array(
			'title'   => esc_html__( 'Enable/Disable', 'learnpress' ),
			'id'      => '[enable]',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__( 'Enable PayPal Standard', 'learnpress' ),
		),
		array(
			'title' => esc_html__( 'PayPal email', 'learnpress' ),
			'id'    => '[paypal_email]',
			'type'  => 'text',
		),
		array(
			'title'   => esc_html__( 'Sandbox mode', 'learnpress' ),
			'id'      => '[paypal_sandbox]',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__( 'Enable PayPal sandbox', 'learnpress' ),
		),
		array(
			'title' => esc_html__( 'Sandbox email address', 'learnpress' ),
			'id'    => '[paypal_sandbox_email]',
			'type'  => 'text',
		),
		array(
			'type' => 'sectionend',
		),
	)
);

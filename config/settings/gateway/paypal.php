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
			'desc'    => esc_html__( 'Enable PayPal', 'learnpress' ),
		),
		array(
			'title' => esc_html__( 'PayPal email', 'learnpress' ),
			'id'    => '[paypal_email]',
			'type'  => 'text',
			'desc'  => esc_html__( 'The old standard will not be supported in 2023/11/30.', 'learnpress' ),
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
			'desc'  => esc_html__( 'The old standard will not be supported in 2023/11/30.', 'learnpress' ),
		),
		array(
			'title'   => esc_html__( 'Use PayPal REST API', 'learnpress' ),
			'id'      => '[use_paypal_rest]',
			'default' => 'yes',
			'type'    => 'checkbox',
			'desc'    => esc_html__( '(Recommendations)', 'learnpress' ),
		),
		array(
			'title' => esc_html__( 'Client ID', 'learnpress' ),
			'id'    => '[app_client_id]',
			'type'  => 'text',
			'desc'  => sprintf(
				__( 'How to get <a href="%s" target="_blank">Client ID</a>', 'learnpress' ),
				'https://developer.paypal.com/api/rest/#link-getclientidandclientsecret'
			),
		),
		array(
			'title' => esc_html__( 'Client Secret', 'learnpress' ),
			'id'    => '[app_client_secret]',
			'type'  => 'text',
			'desc'  => sprintf(
				__( 'How to get <a href="%s" target="_blank">Client Secret</a>', 'learnpress' ),
				'https://developer.paypal.com/api/rest/#link-getclientidandclientsecret'
			),
		),
		array(
			'type' => 'sectionend',
		),
	)
);

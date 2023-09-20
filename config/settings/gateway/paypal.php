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
			'title'      => esc_html__( 'Use paypal app', 'learnpress' ),
			'id'         => '[use_paypal_rest]',
			'default'    => 'no',
			'type'       => 'yes-no',
			'visibility' => array(
				'state'       => 'show',
				'conditional' => array(
					array(
						'field'   => '[enable]',
						'compare' => '=',
						'value'   => 'yes',
					),
				),
			),
			'desc'       => esc_html__( 'Use PayPal Standard Rest API, Create your app in Dashboard and use app Client ID and Client Secret', 'learnpress' ),
		),
		array(
			'title' => esc_html__( 'Client ID', 'learnpress' ),
			'id'    => '[app_client_id]',
			'type'  => 'text',
			'desc'  => esc_html__( 'PayPal Application Client ID', 'learnpress' ),
		),
		array(
			'title' => esc_html__( 'Client Secret', 'learnpress' ),
			'id'    => '[app_client_secret]',
			'type'  => 'text',
			'desc'  => esc_html__( 'PayPal Application Client Secret', 'learnpress' ),
		),
		array(
			'type' => 'sectionend',
		),
	)
);

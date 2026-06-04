<?php
/**
 * Fields settings PayPal Payment
 */

$subscription_webhook_url = esc_url( rest_url( 'lp/v1/gateways/paypal/subscription-webhook' ) );

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
		/*array(
			'title' => esc_html__( 'PayPal email', 'learnpress' ),
			'id'    => '[paypal_email]',
			'type'  => 'text',
			'desc'  => esc_html__( 'The old standard will not be supported in 2023/11/30.', 'learnpress' ),
		),*/
		array(
			'title'   => esc_html__( 'Sandbox mode', 'learnpress' ),
			'id'      => '[paypal_sandbox]',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__( 'Enable PayPal sandbox', 'learnpress' ),
		),
		/*array(
			'title' => esc_html__( 'Sandbox email address', 'learnpress' ),
			'id'    => '[paypal_sandbox_email]',
			'type'  => 'text',
			'desc'  => esc_html__( 'The old standard will not be supported in 2023/11/30.', 'learnpress' ),
		),*/
		/*array(
			'title'   => esc_html__( 'Use PayPal REST API', 'learnpress' ),
			'id'      => '[use_paypal_rest]',
			'default' => 'yes',
			'type'    => 'checkbox',
			'desc'    => esc_html__( '(Recommendations)', 'learnpress' ),
		),*/
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
			'title'   => esc_html__( 'Enable subscriptions', 'learnpress' ),
			'id'      => '[enable_subscriptions]',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => sprintf(
				'%1$s<br /><strong>%2$s</strong> <code>%3$s</code>',
				esc_html__( 'Enable PayPal subscription checkout flow.', 'learnpress' ),
				esc_html__( 'Webhook URL:', 'learnpress' ),
				esc_html( $subscription_webhook_url )
			),
		),
		array(
			'title' => esc_html__( 'Subscription webhook ID', 'learnpress' ),
			'id'    => '[subscription_webhook_id]',
			'type'  => 'text',
			'desc'  => esc_html__( 'PayPal webhook ID used to reverse-verify subscription events.', 'learnpress' ),
		),
		array(
			'type' => 'sectionend',
		),
	)
);

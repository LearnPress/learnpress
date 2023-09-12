<?php
/**
 * Fields settings Offline Payment
 */

/**
 * @var LP_Gateway_Offline_Payment $lp_gateway_offline_payment
 */
if ( ! isset( $lp_gateway_offline_payment ) ) {
	return [];
}

return apply_filters(
	'learn-press/gateway-payment/offline-payment/settings',
	array(
		array(
			'type' => 'title',
		),
		array(
			'title'   => __( 'Enable', 'learnpress' ),
			'id'      => '[enable]',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'title'   => __( 'Testing Mode', 'learnpress' ),
			'id'      => '[sandbox]',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => __( 'Auto complete the order for testing purpose.' ),
		),
		array(
			'title'   => __( 'Title', 'learnpress' ),
			'id'      => '[title]',
			'default' => $lp_gateway_offline_payment->title,
			'type'    => 'text',
		),
		array(
			'title'   => __( 'Instruction', 'learnpress' ),
			'id'      => '[description]',
			'default' => $lp_gateway_offline_payment->description,
			'type'    => 'textarea',
		),
		array(
			'type' => 'sectionend',
		),
	)
);

<?php
/**
 * Fields settings Refund
 */

defined( 'ABSPATH' ) || exit;

return apply_filters(
	'learn-press/payment-settings/refund',
	array(
		array(
			'type' => 'title',
		),
		array(
			'title'   => esc_html__( 'Enable Refund Requests', 'learnpress' ),
			'id'      => 'enable_refund_requests',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__( 'Allow users to submit refund requests.', 'learnpress' ),
		),
		array(
			'title'           => esc_html__( 'Auto Refund', 'learnpress' ),
			'id'              => 'auto_refund',
			'default'         => 'no',
			'type'            => 'checkbox',
			'desc'            => esc_html__( 'Enable auto refund, skip admin review.', 'learnpress' ),
			'show_if_checked' => 'enable_refund_requests',
		),
		array(
			'title'           => esc_html__( 'Refund Time Limit (days)', 'learnpress' ),
			'id'              => 'refund_time_limit',
			'default'         => 30,
			'type'            => 'number',
			'min'             => 0,
			'desc'            => esc_html__( 'Maximum days after purchase that users can request a refund. 0 means unlimited.', 'learnpress' ),
			'show_if_checked' => 'enable_refund_requests',
		),
		array(
			'title'           => esc_html__( 'Require Refund Reason', 'learnpress' ),
			'id'              => 'require_refund_reason',
			'default'         => 'no',
			'type'            => 'checkbox',
			'desc'            => esc_html__( 'Require users to enter a reason when requesting a refund.', 'learnpress' ),
			'show_if_checked' => 'enable_refund_requests',
		),
		array(
			'title'           => esc_html__( 'Allow Re-Request After Rejection', 'learnpress' ),
			'id'              => 'allow_resend_after_rejected',
			'default'         => 'no',
			'type'            => 'checkbox',
			'desc'            => esc_html__( 'Allow users to submit a new request after a denied refund request.', 'learnpress' ),
			'show_if_checked' => 'enable_refund_requests',
		),
		array(
			'type' => 'sectionend',
		),
	)
);

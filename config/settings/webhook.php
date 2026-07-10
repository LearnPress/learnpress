<?php
/**
 * Setting tab Webhook.
 */

return apply_filters(
	'learn_press_webhook_settings',
	array(
		array(
			'type'  => 'title',
			'title' => esc_html__( 'Webhook Integration', 'learnpress' ),
			'id'    => 'lp_metabox_webhook_general',
		),
		array(
			'title'   => esc_html__( 'Enable Webhook', 'learnpress' ),
			'id'      => 'enable_webhook_integration',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__( 'Enable webhook delivery. Webhook events are not delivered unless this option is enabled.', 'learnpress' ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'lp_metabox_webhook_general',
		),
	)
);

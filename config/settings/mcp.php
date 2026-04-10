<?php
/**
 * Setting tab MCP.
 */

return apply_filters(
	'learn_press_mcp_settings',
	array(
		array(
			'type'  => 'title',
			'title' => esc_html__( 'MCP Integration', 'learnpress' ),
			'id'    => 'lp_metabox_mcp_general',
		),
		array(
			'title'   => esc_html__( 'Enable MCP Integration', 'learnpress' ),
			'id'      => 'enable_mcp_integration',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__(
				'Enable Model Context Protocol integration for AI-powered LMS operations. Review all AI actions before executing.',
				'learnpress'
			),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'lp_metabox_mcp_general',
		),
	)
);

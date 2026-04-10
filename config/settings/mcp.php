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
			'desc'    => sprintf(
				/* translators: %s: MCP documentation URL. */
				__(
					'Enable Model Context Protocol integration for AI-powered LMS operations. Review all AI actions before executing. <a href="%s" target="_blank" rel="noopener noreferrer">Learn more</a>.',
					'learnpress'
				),
				esc_url( 'https://learnpresslms.com/docs/learnpress-developer-documentation/model-context-protocol-mcp-integration/' )
			),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'lp_metabox_mcp_general',
		),
	)
);

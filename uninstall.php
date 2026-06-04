<?php
/**
 * Uninstall LearnPress.
 *
 * Scope-limited cleanup for MCP capability and integration tables.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$admin = get_role( 'administrator' );
if ( $admin ) {
	$admin->remove_cap( 'lp_mcp_access' );
}

global $wpdb;
$mcp_table_name      = $wpdb->prefix . 'learnpress_mcp_api_keys';
$webhooks_table_name = $wpdb->prefix . 'learnpress_webhooks';
$wpdb->query( "DROP TABLE IF EXISTS {$mcp_table_name}" );
$wpdb->query( "DROP TABLE IF EXISTS {$webhooks_table_name}" );

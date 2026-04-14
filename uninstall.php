<?php
/**
 * Uninstall LearnPress.
 *
 * Scope-limited cleanup for MCP capability.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$admin = get_role( 'administrator' );
if ( $admin ) {
	$admin->remove_cap( 'lp_mcp_access' );
}

global $wpdb;
$table_name = $wpdb->prefix . 'learnpress_mcp_api_keys';
$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

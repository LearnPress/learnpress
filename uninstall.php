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

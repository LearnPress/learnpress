<?php
/**
 * MCP API keys settings section.
 *
 * @var LP_Admin_MCP_API_Keys_Table_List $table
 * @var array<int, WP_User>              $users
 * @var array<string,string>|null         $message
 */

defined( 'ABSPATH' ) || exit;

$default_user = get_current_user_id();
$description  = '';
$permissions  = 'read';
?>

<div class="lp-mcp-api-keys-section">
	<?php if ( $message ) : ?>
		<div class="notice notice-<?php echo esc_attr( $message['type'] ); ?> inline">
			<p><?php echo esc_html( $message['message'] ); ?></p>
		</div>
	<?php endif; ?>

	<div class="lp-mcp-key-editor" style="margin: 12px 0 20px; padding: 16px; border: 1px solid #dcdcde; background: #fff;">
		<h2 style="margin-top: 0;"><?php esc_html_e( 'Create MCP API Key', 'learnpress' ); ?></h2>
		<p>
			<?php esc_html_e( 'Generated credentials are shown once. Store them securely before leaving this page.', 'learnpress' ); ?>
		</p>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row"><label for="lp-mcp-key-user"><?php esc_html_e( 'User', 'learnpress' ); ?></label></th>
					<td>
						<select id="lp-mcp-key-user" name="mcp_key_user_id">
							<?php foreach ( $users as $user ) : ?>
								<option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $default_user, $user->ID ); ?>>
									<?php echo esc_html( $user->display_name . ' (' . $user->user_login . ')' ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="lp-mcp-key-description"><?php esc_html_e( 'Description', 'learnpress' ); ?></label></th>
					<td>
						<input type="text" id="lp-mcp-key-description" name="mcp_key_description" class="regular-text" maxlength="200" value="<?php echo esc_attr( $description ); ?>" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="lp-mcp-key-permissions"><?php esc_html_e( 'Permissions', 'learnpress' ); ?></label></th>
					<td>
						<select id="lp-mcp-key-permissions" name="mcp_key_permissions">
							<option value="read" <?php selected( $permissions, 'read' ); ?>><?php esc_html_e( 'read', 'learnpress' ); ?></option>
							<option value="write" <?php selected( $permissions, 'write' ); ?>><?php esc_html_e( 'write', 'learnpress' ); ?></option>
							<option value="read_write" <?php selected( $permissions, 'read_write' ); ?>><?php esc_html_e( 'read_write', 'learnpress' ); ?></option>
						</select>
					</td>
				</tr>
			</tbody>
		</table>

		<p>
			<button type="button" class="button button-primary" id="lp-mcp-key-submit"><?php esc_html_e( 'Generate API Key', 'learnpress' ); ?></button>
			<span id="lp-mcp-key-status" style="margin-left: 10px;"></span>
		</p>

		<div id="lp-mcp-key-reveal" style="display:none; margin-top: 12px; padding: 12px; border: 1px solid #2271b1; background: #f0f6fc;">
			<p style="margin-top:0;"><strong><?php esc_html_e( 'Copy these credentials now. They will not be shown again.', 'learnpress' ); ?></strong></p>
			<p>
				<label><strong><?php esc_html_e( 'Consumer Key', 'learnpress' ); ?></strong></label><br />
				<input type="text" readonly id="lp-mcp-consumer-key" class="regular-text code" />
				<button type="button" class="button lp-mcp-copy" data-target="lp-mcp-consumer-key"><?php esc_html_e( 'Copy', 'learnpress' ); ?></button>
			</p>
			<p>
				<label><strong><?php esc_html_e( 'Consumer Secret', 'learnpress' ); ?></strong></label><br />
				<input type="text" readonly id="lp-mcp-consumer-secret" class="regular-text code" />
				<button type="button" class="button lp-mcp-copy" data-target="lp-mcp-consumer-secret"><?php esc_html_e( 'Copy', 'learnpress' ); ?></button>
			</p>
		</div>
	</div>

	<div class="lp-mcp-key-list" style="background:#fff; padding: 16px; border: 1px solid #dcdcde;">
		<h2 style="margin-top:0;"><?php esc_html_e( 'API Keys', 'learnpress' ); ?></h2>
		<input type="hidden" name="page" value="learn-press-settings" />
		<input type="hidden" name="tab" value="mcp" />
		<?php wp_nonce_field( 'lp_mcp_bulk_revoke_action', 'lp_mcp_bulk_revoke_nonce' ); ?>
		<?php $table->search_box( __( 'Search keys', 'learnpress' ), 'lp-mcp-keys-search' ); ?>
		<?php $table->display(); ?>
	</div>
</div>

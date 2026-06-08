<?php
/**
 * Webhook management settings section.
 *
 * @var LP_Admin_Webhooks_Table_List $table
 * @var array<string, string>         $events
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="lp-webhooks-section">
	<div id="lp-webhook-editor-holder" style="display:none;">
		<div id="lp-webhook-editor" class="lp-webhook-editor" style="max-height:70vh; overflow:auto; padding:4px 6px 0; text-align:left;">
			<h2 id="lp-webhook-editor-title" style="margin-top: 0;"><?php esc_html_e( 'Create Webhook', 'learnpress' ); ?></h2>
			<input type="hidden" id="lp-webhook-id" value="0" />

			<table id="lp-webhook-editor-fields" class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row"><label for="lp-webhook-name"><?php esc_html_e( 'Name', 'learnpress' ); ?></label></th>
						<td><input type="text" id="lp-webhook-name" class="regular-text" maxlength="200" required disabled /></td>
					</tr>
					<tr>
						<th scope="row"><label for="lp-webhook-delivery-url"><?php esc_html_e( 'URL callback', 'learnpress' ); ?></label></th>
						<td><input type="url" id="lp-webhook-delivery-url" class="regular-text code" required disabled /></td>
					</tr>
					<tr>
						<th scope="row"><label for="lp-webhook-secret"><?php esc_html_e( 'Secret', 'learnpress' ); ?></label></th>
						<td>
							<input type="password" id="lp-webhook-secret" class="regular-text code" maxlength="255" autocomplete="new-password" placeholder="<?php esc_attr_e( 'Leave blank to auto-generate a secret.', 'learnpress' ); ?>" disabled />
							<p class="description"><?php esc_html_e( 'Leave blank to generate a secret for a new webhook or keep the existing secret when editing.', 'learnpress' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="lp-webhook-status"><?php esc_html_e( 'Status', 'learnpress' ); ?></label></th>
						<td>
							<select id="lp-webhook-status" disabled>
								<option value="active"><?php esc_html_e( 'Active', 'learnpress' ); ?></option>
								<option value="paused"><?php esc_html_e( 'Paused', 'learnpress' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Events tracked', 'learnpress' ); ?></th>
						<td>
							<select
								id="lp-webhook-events"
								name="lp_webhook_events[]"
								class="lp-tom-select"
								multiple="multiple"
								placeholder="<?php esc_attr_e( 'Select events', 'learnpress' ); ?>"
								style="width:100%; max-width:900px;"
								disabled
							>
								<?php foreach ( $events as $event_key => $event_label ) : ?>
									<option value="<?php echo esc_attr( $event_key ); ?>">
										<?php echo esc_html( sprintf( '%1$s (%2$s)', $event_label, $event_key ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
				</tbody>
			</table>

			<p id="lp-webhook-editor-actions">
				<button type="button" class="button button-primary" id="lp-webhook-submit" disabled><?php esc_html_e( 'Create Webhook', 'learnpress' ); ?></button>
				<button type="button" class="button" id="lp-webhook-cancel" style="display:none;" disabled><?php esc_html_e( 'Cancel edit', 'learnpress' ); ?></button>
				<button type="button" class="button" id="lp-webhook-regenerate-editor" style="display:none;" disabled><?php esc_html_e( 'Regenerate secret', 'learnpress' ); ?></button>
				<span id="lp-webhook-status-message" style="margin-left:10px;"></span>
			</p>

			<div id="lp-webhook-secret-reveal" style="display:none; margin-top:12px; padding:12px; border:1px solid #2271b1; background:#f0f6fc;">
				<p style="margin-top:0;"><strong><?php esc_html_e( 'Copy this secret now.', 'learnpress' ); ?></strong></p>
				<input type="text" readonly id="lp-webhook-secret-value" class="regular-text code" />
				<button type="button" class="button" id="lp-webhook-copy-secret"><?php esc_html_e( 'Copy', 'learnpress' ); ?></button>
			</div>
		</div>
	</div>

	<div class="lp-webhook-list" style="background:#fff; padding:16px; border:1px solid #dcdcde;">
		<div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:12px;">
			<h2 style="margin:0;"><?php esc_html_e( 'Webhooks', 'learnpress' ); ?></h2>
			<button type="button" class="button button-primary" id="lp-webhook-open-create"><?php esc_html_e( 'Add Webhook', 'learnpress' ); ?></button>
		</div>
		<input type="hidden" name="page" value="learn-press-settings" />
		<input type="hidden" name="tab" value="advanced" />
		<input type="hidden" name="section" value="webhook" />
		<?php $table->search_box( __( 'Search webhooks', 'learnpress' ), 'lp-webhooks-search' ); ?>
		<?php $table->display(); ?>
	</div>
</div>

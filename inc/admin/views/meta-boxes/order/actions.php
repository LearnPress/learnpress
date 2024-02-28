<?php
/**
 * Admin View: Order actions Meta box
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

global $post;
?>

<div class="submitbox" id="submitpost">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<select name="trigger-order-action">
					<option value=""><?php esc_html_e( 'Choose an action', 'learnpress' ); ?></option>
					<option
						value="current-status"><?php esc_html_e( 'Trigger action of the current order status', 'learnpress' ); ?></option>
				</select>
			</div>
		</div>
		<div id="major-publishing-actions">
			<div id="delete-action">
				<?php if ( current_user_can( 'delete_post', $post->ID ) ) : ?>
					<?php
					if ( ! EMPTY_TRASH_DAYS ) {
						$delete_text = esc_html__( 'Delete Permanently', 'learnpress' );
					} else {
						$delete_text = esc_html__( 'Move to Trash', 'learnpress' );
					}
					?>
					<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>">
						<?php echo esc_html( $delete_text ); ?>
					</a>
				<?php endif; ?>
			</div>

			<div id="publishing-action">
				<span class="spinner"></span>
				<input name="original_publish" type="hidden" id="original_publish" value="Update">
				<input name="save" type="submit" class="button button-primary button-large" id="publish"
						value="<?php esc_attr_e( 'Update', 'learnpress' ); ?>">
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>

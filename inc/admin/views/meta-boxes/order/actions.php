<?php
/**
 * Admin View: Order actions Meta box
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php global $post; ?>

<div class="submitbox" id="submitpost">
    <div id="minor-publishing">
        <div id="misc-publishing-actions">
            <div class="misc-pub-section">
                <select name="trigger-order-action">
                    <option value=""><?php _e( 'Choose an action', 'learnpress' ); ?></option>
                    <option value="current-status"><?php _e( 'Trigger action of current order status', 'learnpress' ); ?></option>
                </select>
            </div>
        </div>
        <div id="major-publishing-actions">
            <div id="delete-action">
				<?php
				if ( current_user_can( "delete_post", $post->ID ) ) {
					if ( ! EMPTY_TRASH_DAYS ) {
						$delete_text = __( 'Delete Permanently', 'learnpress' );
					} else {
						$delete_text = __( 'Move to Trash', 'learnpress' );
					} ?>
                    <a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>">
						<?php echo $delete_text; ?>
                    </a>
				<?php } ?>
            </div>

            <div id="publishing-action">
                <span class="spinner"></span>
                <input name="original_publish" type="hidden" id="original_publish" value="Update">
                <input name="save" type="submit" class="button button-primary button-large" id="publish" value="Update">
            </div>
            <div class="clear"></div>
        </div>
    </div>
</div>
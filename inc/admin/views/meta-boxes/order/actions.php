<?php
global $post, $action;
$post_type        = $post->post_type;
$post_type_object = get_post_type_object( $post_type );
$can_publish      = current_user_can( $post_type_object->cap->publish_posts );
$is_multiple      =

$datef = __( 'M j, Y @ H:i' );
if ( 0 != $post->ID ) {
	if ( 'future' == $post->post_status ) { // scheduled for publishing at a future date
		$stamp = __( 'Scheduled for: <b>%1$s</b>' );
	} elseif ( 'publish' == $post->post_status || 'private' == $post->post_status ) { // already published
		$stamp = __( 'Order date: <b>%1$s</b>' );
	} elseif ( '0000-00-00 00:00:00' == $post->post_date_gmt ) { // draft, 1 or more saves, no date specified
		$stamp = __( 'Publish <b>immediately</b>' );
	} elseif ( time() < strtotime( $post->post_date_gmt . ' +0000' ) ) { // draft, 1 or more saves, future date specified
		$stamp = __( 'Schedule for: <b>%1$s</b>' );
	} else { // draft, 1 or more saves, date specified
		$stamp = __( 'Publish on: <b>%1$s</b>' );
	}
	$date = date_i18n( $datef, strtotime( $post->post_date ) );
} else { // draft (no saves, and thus no date specified)
	$stamp = __( 'Publish <b>immediately</b>' );
	$date  = date_i18n( $datef, strtotime( current_time( 'mysql' ) ) );
}

?>
<div class="submitbox" id="submitpost">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<?php if ( $can_publish ) : // Contributors don't get to choose the date of publish ?>

				<div class="misc-pub-section">
					<label>
						<?php _e( 'Order status', 'learnpress' ); ?>
					</label>
					<select name="order-status" data-status="<?php echo 'lp-' . $order->get_status(); ?>">
						<?php
						$statuses = learn_press_get_order_statuses();
						foreach ( $statuses as $status => $status_name ) {
							echo '<option data-desc="' . esc_attr( _learn_press_get_order_status_description( $status ) ) . '" value="' . esc_attr( $status ) . '" ' . selected( $status, 'lp-' . $order->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
						}
						?>
					</select>

					<div class="description order-status-description">
						<?php if ( $order->get_status() == 'auto-draft' ) {
							echo _learn_press_get_order_status_description( 'lp-pending' );
						} ?>
						<?php echo _learn_press_get_order_status_description( 'lp-' . $order->get_status() ); ?>
					</div>
				</div>
				<div class="misc-pub-section hide-if-js order-action-section">
					<label for="trigger-order-action">
						<input type="checkbox" name="trigger-order-action" id="trigger-order-action" value="yes" />
						<?php _e( 'Trigger order status action', 'learnpress' ); ?>
					</label>
					<p class="description"><?php esc_attr_e( 'Check this option to force an action to be triggered. Normally, an action only is triggered after changing to an another action.', 'learnpress' ); ?></p>
				</div>

				<div class="misc-pub-section">
					<label>
						<?php _e( 'Customer', 'learnpress' ); ?>
					</label>
					<!--
					<input name="order-customer" type="text" class="wp-suggest-user ui-autocomplete-input" id="admin-email" data-autocomplete-type="search" data-autocomplete-field="user_email" autocomplete="off">
					-->
					<?php
					if ( $order->is_multi_users() ) {
						$order->dropdown_users();
						?>
						<input type="hidden" name="multi-users" value="yes" />
						<?php
						wp_enqueue_style( 'select2', RWMB_CSS_URL . 'select2/select2.css' );
						wp_enqueue_script( 'select2', RWMB_JS_URL . 'select2/select2.min.js' );
					} else {
						wp_dropdown_users(
							array(
								'show_option_none' => __( '[Guest]', 'learnpress' ),
								'name'             => 'order-customer',
								'id'               => null,
								'selected'         => $order->get_user( 'ID' )
							)
						);
					}
					if ( $order->get_status() == 'auto-draft' && !$order->is_multi_users() ) {
						?>
						--
						<a href="<?php echo add_query_arg( 'multi-users', 'yes' ); ?>"><?php _e( 'Multiple users', 'learnpress' ); ?></a>
						<?php
					}
					//					}else{
					//						echo $order->get_customer_name();
					//					}
					?>
				</div>

				<div class="misc-pub-section curtime misc-pub-curtime">
				<span id="timestamp"><?php printf( $stamp, $date ); ?></span>
				<a href="#edit_timestamp" class="edit-timestamp hide-if-no-js"><span aria-hidden="true"><?php _e( 'Edit' ); ?></span>
					<span class="screen-reader-text"><?php _e( 'Edit date and time' ); ?></span></a>
				<fieldset id="timestampdiv" class="hide-if-js">
					<legend class="screen-reader-text"><?php _e( 'Date and time' ); ?></legend>
					<?php touch_time( ( $action === 'edit' ), 1 ); ?>
				</fieldset>
				</div><?php // /misc-pub-section ?>
			<?php endif; ?>
		</div>
		<div id="major-publishing-actions">
			<div id="delete-action">
				<?php
				if ( current_user_can( "delete_post", $post->ID ) ) {
					if ( !EMPTY_TRASH_DAYS )
						$delete_text = __( 'Delete Permanently' );
					else
						$delete_text = __( 'Move to Trash' );
					?>
					<a class="submitdelete deletion" href="<?php echo get_delete_post_link( $post->ID ); ?>"><?php echo $delete_text; ?></a><?php
				} ?>
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
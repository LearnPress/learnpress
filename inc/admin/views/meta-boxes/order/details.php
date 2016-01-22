<?php
if ( isset( $order_items ) ) {
	$currency_symbol = learn_press_get_currency_symbol( $order_items->currency );
} else {
	$currency_symbol = learn_press_get_currency_symbol();
}
global $post;
?>
<div id="learn-press-order" class="order-details">
	<div class="order-data">
		<h3 class="order-data-number"><?php echo sprintf( __( 'Order %s', 'learn_press' ), $order->get_order_number() ); ?></h3>

		<div
			class="order-data-date"><?php echo sprintf( __( 'Date %s', 'learn_press' ), $order->order_date ); ?></div>
		<div class="order-data-status <?php echo sanitize_title( $order->post_status ); ?>"><?php echo sprintf( __( 'Status %s', 'learn_press' ), $order->get_order_status() ); ?></div>
		<div
			class="order-data-payment-method"><?php echo learn_press_payment_method_from_slug( $post->ID ); ?></div>
	</div>
	<div class="order-user-data clearfix">
		<div class="order-user-avatar">
			<?php echo get_avatar( $order->get_user( 'ID' ), 120 ); ?>
		</div>
		<div class="order-user-meta">
			<div class="user-display-name">
				<?php $display_name = $order->get_user( 'display_name' );
				echo empty( $display_name ) ? __( 'Guest', 'learn_press' ) : $display_name; ?>
			</div>
			<div class="user-email">
				<?php $user_email = $order->get_user( 'user_email' );
				echo empty( $user_email ) ? '' : $user_email; ?>
			</div>
			<div class="user-ip-address">
				<?php echo $order->user_ip_address; ?>
			</div>
			<?php if ( $title = $order->get_payment_method_title() ) { ?>
				<div class="payment-method-title">
					<?php echo sprintf( __( 'Pay via <strong>%s</strong>', 'learn_press' ), $title ); ?>
				</div>
			<?php } ?>
		</div>
	</div>
	<br />

	<h3><?php _e( 'Order Items', 'learn_press' ); ?></h3>

	<div class="order-items">
		<table>
			<thead>
			<tr>
				<th><?php _e( 'Item', 'learn_press' ); ?></th>
				<th><?php _e( 'Cost', 'learn_press' ); ?></th>
				<th><?php _e( 'Quantity', 'learn_press' ); ?></th>
				<th class="align-right"><?php _e( 'Amount', 'learn_press' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( $items = $order->get_items() ): ?>
				<?php foreach ( $items as $item ) : ?>
					<?php include learn_press_get_admin_view( 'meta-boxes/order/order-item.php' ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
			<tr class="no-order-items<?php echo $items ? ' hide-if-js' : ''; ?>">
				<td colspan="4"><?php _e( 'No order items', 'learn_press' ); ?></td>
			</tr>
			</tbody>
			<tfoot>
			<tr>
				<td width="300" colspan="3" class="align-right"><?php _e( 'Sub Total', 'learn_press' ); ?></td>
				<td width="100" class="align-right">
					<span class="order-subtotal">
						<?php echo learn_press_format_price( $order->order_subtotal, $currency_symbol ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td class="align-right" colspan="3"><?php _e( 'Total', 'learn_press' ); ?></td>
				<td class="align-right total">
					<span class="order-total">
						<?php echo learn_press_format_price( $order->order_total, $currency_symbol ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td class="align-right" colspan="4">
					<button class="button" type="button" id="learn-press-add-order-item"><?php _e( 'Add Item', 'learn_press' ); ?></button>
					<!--<button class="button" type="button" id="learn-press-calculate-order-total"><?php _e( 'Calculate Total', 'learn_press' ); ?></button>-->
				</td>
			</tr>
			</tfoot>
		</table>
	</div>
	<?php if ( $note = get_the_excerpt( $order->id ) ) { ?>
		<br />
		<h3><?php _e( 'Customer Note', 'learn_press' ); ?></h3>
		<p class="order-note description"><?php echo $note;?></p>
	<?php } ?>
</div>
<script type="text/html" id="tmpl-learn-press-modal-add-order-courses">
	<div id="learn-press-modal-add-order-courses" class="lp-modal-search" data-nonce="<?php echo wp_create_nonce( 'add_item_to_order' ); ?>">
		<div class="lp-search-items">
			<input type="text" id="learn-press-search-item-term" data-nonce="<?php echo wp_create_nonce( 'search_item_term' ); ?>" name="lp-item-name" placeholder="<?php _e( 'Type here to search the course', 'learn_press' ); ?>" />
		</div>
		<ul id="learn-press-courses-result">
			<li class="lp-search-no-results hide-if-js" data-id="0"><?php _e( 'No results', 'learn_press' ); ?></li>
		</ul>
		<button class="lp-close-lightbox button" onclick="LearnPress.MessageBox.hide();"><?php _e( 'Close', 'learn_press' ); ?></button>
	</div>
</script>
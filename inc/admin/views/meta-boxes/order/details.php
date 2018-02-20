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
		<h3 class="order-data-number"><?php echo sprintf( __( 'Order %s', 'learnpress' ), $order->get_order_number() ); ?></h3>

		<div
			class="order-data-date"><?php echo sprintf( __( 'Date %s', 'learnpress' ), $order->order_date ); ?></div>
		<div class="order-data-status <?php echo sanitize_title( $order->post_status ); ?>"><?php echo sprintf( __( 'Status %s', 'learnpress' ), $order->get_order_status() ); ?></div>
		<div
			class="order-data-payment-method"><?php echo learn_press_payment_method_from_slug( $post->ID ); ?></div>
	</div>
	<div class="order-user-data clearfix">
		<div class="order-user-avatar">
			<?php if ( $order->is_multi_users() ) { ?>
				<div class="avatar-multiple-users">
					<span></span>
				</div>
			<?php } else { ?>
				<?php echo get_avatar( $order->get_user( 'ID' ), 120 ); ?>
			<?php } ?>
		</div>
		<div class="order-user-meta">
			<?php if ( $order->is_multi_users() ) { ?>
				<div class="order-users">
					<strong><?php _e( 'Customers', 'learnpress' ); ?></strong>
					<p><?php $order->print_users(); ?></p>
				</div>
			<?php } else { ?>
				<div class="user-display-name">
					<?php echo $order->get_customer_name(); ?>
				</div>
				<div class="user-email">
					<?php $user_email = $order->get_user( 'user_email' );
					echo empty( $user_email ) ? '' : $user_email; ?>
				</div>
				<div class="user-ip-address">
					<?php echo $order->user_ip_address; ?>
				</div>
			<?php } ?>
			<?php if ( $title = $order->get_payment_method_title() ) { ?>
				<div class="payment-method-title">
					<?php echo $order->order_total == 0 ? $title : sprintf( __( 'Pay via <strong>%s</strong>', 'learnpress' ), $title ); ?>
				</div>
			<?php } ?>
		</div>
	</div>
	<br />

	<h3><?php _e( 'Order Items', 'learnpress' ); ?></h3>
	<div class="order-items">
		<table>
			<thead>
			<tr>
				<th><?php _e( 'Item', 'learnpress' ); ?></th>
				<th><?php _e( 'Cost', 'learnpress' ); ?></th>
				<th><?php _e( 'Quantity', 'learnpress' ); ?></th>
				<th class="align-right"><?php _e( 'Amount', 'learnpress' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( $items = $order->get_items() ): ?>
				<?php foreach ( $items as $item ) : ?>
					<?php include learn_press_get_admin_view( 'meta-boxes/order/order-item.php' ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
			<tr class="no-order-items<?php echo $items ? ' hide-if-js' : ''; ?>">
				<td colspan="4"><?php _e( 'No order items', 'learnpress' ); ?></td>
			</tr>
			</tbody>
			<tfoot>
			<tr>
				<td width="300" colspan="3" class="align-right"><?php _e( 'Sub Total', 'learnpress' ); ?></td>
				<td width="100" class="align-right">
					<span class="order-subtotal">
						<?php echo learn_press_format_price( $order->order_subtotal, $currency_symbol ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td class="align-right" colspan="3"><?php _e( 'Total', 'learnpress' ); ?></td>
				<td class="align-right total">
					<span class="order-total">
						<?php echo learn_press_format_price( $order->order_total, $currency_symbol ); ?>
					</span>
				</td>
			</tr>
			<tr>
				<td class="align-right" colspan="4">
					<button class="button" type="button" id="learn-press-add-order-item"><?php _e( 'Add Item', 'learnpress' ); ?></button>
					<!--<button class="button" type="button" id="learn-press-calculate-order-total"><?php _e( 'Calculate Total', 'learnpress' ); ?></button>-->
				</td>
			</tr>
			</tfoot>
		</table>
	</div>
	<?php if ( $note = get_the_excerpt() ) { ?>
		<br />
		<h3><?php _e( 'Customer Note', 'learnpress' ); ?></h3>
		<p class="order-note description"><?php echo $note; ?></p>
	<?php } ?>
</div>
<script type="text/html" id="tmpl-learn-press-modal-add-order-courses">
	<div id="learn-press-modal-add-order-courses" class="lp-modal-search" data-nonce="<?php echo wp_create_nonce( 'add_item_to_order' ); ?>">
		<div class="lp-search-items">
			<input type="text" id="learn-press-search-item-term" data-nonce="<?php echo wp_create_nonce( 'search_item_term' ); ?>" name="lp-item-name" placeholder="<?php _e( 'Type here to search the course', 'learnpress' ); ?>" />
		</div>
		<ul id="learn-press-courses-result">
			<li class="lp-search-no-results hide-if-js" data-id="0"><?php _e( 'No results', 'learnpress' ); ?></li>
		</ul>
		<button class="lp-close-lightbox button" onclick="LP.MessageBox.hide();"><?php _e( 'Close', 'learnpress' ); ?></button>
	</div>
</script>
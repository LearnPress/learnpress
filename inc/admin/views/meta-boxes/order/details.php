<?php
$currency_symbol = learn_press_get_currency_symbol( $order_items->currency );

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
					<?php echo empty( $order->get_user( 'display_name' ) ) ? __( 'Unknown', 'learn_press' ) : $order->get_user( 'display_name' ); ?>
				</div>
				<div class="user-email">
					<?php echo empty( $order->get_user( 'user_email' ) ) ? __( 'Unknown', 'learn_press' ) : $order->get_user( 'user_email' ); ?>
				</div>
				<div class="user-ip-address">
					<?php echo $order->user_ip_address; ?>
				</div>
				<?php if( $title = $order->get_payment_method_title() ){ ?>
				<div class="payment-method-title">
					<?php echo sprintf( __( 'Pay via <strong>%s</strong>', 'learn_press' ), $title ); ?>
				</div>
				<?php } ?>
			</div>
		</div>
		<br />
		<h3><?php _e( 'Order courses', 'learn_press' ); ?></h3>
		<div class="order-products">
			<table>
				<thead>
				<tr>
					<th><?php _e( 'Course', 'learn_press' ); ?></th>
					<th><?php _e( 'Price', 'learn_press' ); ?></th>
					<th class="align-right"><?php _e( 'Amount', 'learn_press' ); ?></th>
				</tr>
				</thead>
				<tbody>
				<?php if ( $items = $order->get_items() ): foreach ( $items as $item ) { ?>
					<tr>
						<td>
							<?php echo $item['quantity'];?> &times; <a href="<?php echo get_the_permalink( $item['course_id'] ); ?>"><?php echo $item['name']; ?></a>
						</td>
						<td>
							<?php echo learn_press_format_price( $item['total'], $currency_symbol );?>
						</td>
						<td class="align-right"><?php echo learn_press_format_price( $item['total'], $currency_symbol ); ?></td>
					</tr>
				<?php } endif; ?>
				</tbody>
				<tfoot>
				<tr>
					<td width="300" colspan="2" class="align-right"><?php _e( 'Sub Total', 'learn_press' ); ?></td>
					<td width="100"
						class="align-right"><?php echo learn_press_format_price( $order->order_subtotal, $currency_symbol ); ?>
					</td>
				</tr>
				<tr>
					<td class="align-right" colspan="2"><?php _e( 'Total', 'learn_press' ); ?></td>
					<td class="align-right total"><?php echo learn_press_format_price( $order->order_total, $currency_symbol ); ?></td>
				</tr>
				<tr>
					<td class="align-right" colspan="3">
						<?php _e( 'Status', 'learn_press' ); ?>
						<select name="learn_press_order_status">
							<?php foreach ( learn_press_get_order_statuses() as $status => $label ) { ?>
								<option value="<?php echo $status; ?>" <?php selected( $status == get_post_status( $post->ID ) ? 1 : 0, 1 ); ?>><?php echo $label; ?></option>
							<?php } ?>
						</select>
						<button id="update-order-status" class="button button-primary" type="button"><?php _e( 'Apply', 'learn_press' ); ?></button>
					</td>
				</tr>
				</tfoot>
			</table>
		</div>
	</div>
<?php
print_r( $order );
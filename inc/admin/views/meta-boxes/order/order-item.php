<?php
/**
 * Display single row of order items.
 *
 * @author Nhamdv <4.0.0>
 * @version 4.0.1
 */
/**
 * @var LP_Order $order
 */
if ( ! isset( $item ) || ! isset( $order ) ) {
	return;
}
?>

<?php if ( ! empty( $item['course_id'] ) ) { ?>
	<tr class="order-item-row" data-item_id="<?php echo esc_attr( $item['id'] ); ?>" data-id="<?php echo esc_attr( $item['course_id'] ); ?>" data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
		<td class="column-name">
			<?php if ( $order->is_manual() && $order->get_status() === LP_ORDER_PENDING ) : ?>
				<a class="remove-order-item" href="#">
					<span class="dashicons dashicons-no-alt"></span>
				</a>
			<?php endif; ?>

			<?php do_action( 'learn_press/before_order_details_item_title', $item ); ?>

			<a href="<?php echo apply_filters( 'learn_press/order_item_link', get_the_permalink( $item['course_id'] ), $item ); ?>">
				<?php echo apply_filters( 'learn_press/order_item_name', esc_html( $item['name'] ), $item, $order ); ?>
			</a>

			<?php do_action( 'learn_press/after_order_details_item_title', $item ); ?>
		</td>

		<td class="column-price align-right">
			<?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?>
		</td>

		<td class="column-quantity align-right">
			<small class="times">×</small>
			<?php echo esc_html( $item['quantity'] ); ?>
		</td>

		<td class="column-total align-right"><?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?></td>
	</tr>

	<?php
} else {
	do_action( 'learn-press/order-item-not-course', $item, $order );
}
?>

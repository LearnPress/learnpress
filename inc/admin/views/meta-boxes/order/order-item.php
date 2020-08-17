<?php
/**
 * Order Item Details
 */

if ( ! isset( $item ) ) {
	return;
}

if ( ! empty( $item['course_id'] ) ) {
	?>
	<tr class="order-item-row" data-item_id="<?php echo $item['id']; ?>" data-id="<?php echo $item['course_id']; ?>"
		data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
		<td class="column-name">
			<?php if ( isset( $order ) && 'pending' === $order->get_status() ) { ?>
				<a class="remove-order-item" href="">
					<span class="dashicons dashicons-trash"></span>
				</a>
			<?php } ?>
			<?php
			do_action( 'learn_press/before_order_details_item_title', $item );

			$link_item = '<a href="' . get_the_permalink( $item['course_id'] ) . '">' . $item['name'] . '</a>';
			echo apply_filters( 'learn_press/order_detail_item_link', $link_item, $item );

			do_action( 'learn_press/after_order_details_item_title', $item );
			?>
		</td>
		<td class="column-price align-right">
			<?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?>
		</td>
		<td class="column-quantity align-right">
			<small class="times">×</small>
			<?php echo isset( $item['quantity'] ) ? $item['quantity'] : 0; ?>
		</td>
		<td class="column-total align-right"><?php echo learn_press_format_price( isset( $item['total'] ) ? $item['total'] : 0, isset( $currency_symbol ) ? $currency_symbol : '$' ); ?></td>
	</tr>
	<?php
} else {
	do_action( 'learn-press/order-item-not-course', $item );
}
?>

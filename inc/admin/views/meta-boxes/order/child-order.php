<?php
/**
 * Child Order in multil site.
 */

if ( ! isset( $order ) ) {
	$order = learn_press_get_order();
}

if ( ! $order->is_multi_users() ) {
	return;
}
?>

<h4><?php echo esc_html_e( 'Child orders', 'learnpress' ); ?></h4>

<table class="wp-list-table widefat fixed striped posts">
	<thead>
	<tr>
		<th>#</th>
		<th><?php esc_html_e( 'Customer', 'learnpress' ); ?></th>
		<th><?php esc_html_e( 'Order key', 'learnpress' ); ?></th>
		<th><?php esc_html_e( 'Status', 'learnpress' ); ?></th>
	</tr>
	</thead>

	<tbody>
		<?php $child_orders = $order->get_child_orders(); ?>

		<?php foreach ( $child_orders as $child_order_id ) : ?>
			<?php
			$child_order = learn_press_get_order( $child_order_id );

			if ( ! $child_order ) {
				continue;
			}
			?>

			<tr>
				<td>
					<strong><?php echo sprintf( '<a href="%s">%s</a>', admin_url( 'post.php?post=' . $child_order->get_id() . '&action=edit' ), $child_order->get_order_number() ); ?></strong>
				</td>
				<td><?php echo $child_order->get_customer_name(); ?></td>
				<td><?php echo $child_order->get_order_key(); ?></td>
				<td><?php echo $child_order->get_status(); ?></td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>

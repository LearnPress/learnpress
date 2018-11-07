<?php
/**
 *
 */

if ( ! isset( $order ) ) {
	$order = learn_press_get_order();
}

if ( ! $order->is_multi_users() ) {
	return;
}
?>

<h4><?php echo esc_html( 'Child orders', 'learnpress' ); ?></h4>

<table class="wp-list-table widefat fixed striped posts">
    <thead>
    <tr>
        <th>#</th>
        <th><?php _e( 'Customer', 'learnpress' ); ?></th>
        <th><?php _e( 'Order key', 'learnpress' ); ?></th>
    </tr>
    </thead>
    <tbody>
	<?php foreach ( $child_orders = $order->get_child_orders() as $child_order_id ) { ?>
		<?php
		if ( ! $child_order = learn_press_get_order( $child_order_id ) ) {
			continue;
		}
		?>
        <tr>
            <td>
                <strong><?php echo sprintf( '<a href="%s">%s</a>', admin_url( 'post.php?post=' . $child_order->get_id() . '&action=edit' ), $child_order->get_order_number() ); ?></strong>
            </td>
            <td><?php echo $child_order->get_customer_name(); ?></td>
            <td><?php echo $child_order->get_order_key(); ?></td>
        </tr>
	<?php } ?>
    </tbody>
</table>
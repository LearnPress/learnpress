<?php
/**
 * Template for displaying user's orders
 *
 * @author ThimPress
 * @package LearnPress/Template
 * @version 1.0
 */

?>
<?php if( $orders = $user->get_orders() ): ?>

<table class="table-orders">
	<thead>
		<th><?php _e( 'Order', 'learn_press' );?></th>
		<th><?php _e( 'Date', 'learn_press' );?></th>
		<th><?php _e( 'Status', 'learn_press' );?></th>
		<th><?php _e( 'Total', 'learn_press' );?></th>
		<th><?php _e( 'Action', 'learn_press' );?></th>
	</thead>
	<tbody>
	<?php foreach( $orders as $order ): $order = learn_press_get_order( $order );?>
		<tr>
			<td><?php echo $order->get_order_number();?></td>
			<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->order_date ) ); ?></td>
			<td><?php echo $order->get_order_status();?></td>
			<td><?php echo $order->get_formatted_order_total();?></td>
			<td>
				<?php
				$actions['view'] = array(
					'url'  => $order->get_view_order_url(),
					'text' => __( 'View', 'learn_press' )
				);
				$actions = apply_filters( 'learn_press_user_profile_order_actions', $actions, $order );

				foreach( $actions as $slug => $option ){
					printf( '<a href="%s">%s</a>', $option['url'], $option['text'] );
				}
				?>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>

<?php else: ?>
	<?php learn_press_display_message( __( 'You have not got any orders yet!', 'learn_press' ) ); ?>
<?php endif; ?>

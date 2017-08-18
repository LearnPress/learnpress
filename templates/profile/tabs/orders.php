<?php
/**
 * Template for displaying user's orders.
 *
 * @author  ThimPress
 * @package LearnPress/Template
 * @version 3.x.x
 */
defined( 'ABSPATH' ) || exit();

global $profile;

$query_orders = $profile->query_orders( array( 'fields' => 'ids' ) );
if ( ! $query_orders ) {
	learn_press_display_message( __( 'You have not got any orders yet!', 'learnpress' ) );

	return;
}
//$orders = _learn_press_get_user_profile_orders( $user_id, $page, $limit );
?>
<table class="table-orders">
    <thead>
    <tr class="order-row">
        <th class="column-order-number"><?php _e( 'Order', 'learnpress' ); ?></th>
        <th class="column-order-date"><?php _e( 'Date', 'learnpress' ); ?></th>
        <th class="column-order-status"><?php _e( 'Status', 'learnpress' ); ?></th>
        <th class="column-order-total"><?php _e( 'Total', 'learnpress' ); ?></th>
        <th class="column-order-action"><?php _e( 'Action', 'learnpress' ); ?></th>
    </tr>
    </thead>
    <tbody>
	<?php foreach ( $query_orders['orders'] as $order_id ): $order = learn_press_get_order( $order_id ); ?>
        <tr class="order-row">
            <td class="column-order-number"><?php echo $order->get_order_number(); ?></td>
            <td class="column-order-date"><?php echo $order->get_order_date( get_option( 'date_format' ) ); ?></td>
            <td class="column-order-status">
				<?php echo $order->get_order_status_html(); ?>
            </td>
            <td class="column-order-total"><?php echo $order->get_formatted_order_total(); ?></td>
            <td class="column-order-action">
				<?php
				if ( $actions = $order->get_profile_order_actions() ) {
					foreach ( $actions as $action ) {
						printf( '<a href="%s">%s</a>', $action['url'], $action['text'] );
					}
				}
				?>
            </td>
        </tr>
	<?php endforeach; ?>
    </tbody>
</table>

<?php
echo $query_orders['pagination'];
?>


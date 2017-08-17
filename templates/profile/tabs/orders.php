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

$orders = $profile->get_user_orders( true );

if ( ! $orders ) {
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
	<?php foreach ( $orders as $order_id => $courses ): $order = learn_press_get_order( $order_id ); ?>
        <tr class="order-row">
            <td class="column-order-number"><?php echo $order->get_order_number(); ?></td>
            <td class="column-order-date"><?php echo strtotime( $order->get_order_date( date_i18n( get_option( 'date_format' ) ) ) ); ?></td>
            <td class="column-order-status">
				<?php echo $order->get_order_status_html(); ?>
				<?php
				if ( $cancel_url = $order->get_cancel_order_url() ) {
					printf( '<a href="%s">%s</a>', $order->get_cancel_order_url(), __( 'Cancel', 'learnpress' ) );
				}
				?>
            </td>
            <td class="column-order-total"><?php echo $order->get_formatted_order_total(); ?></td>
            <td class="column-order-action">
				<?php
				$actions['view'] = array(
					'url'  => $order->get_view_order_url(),
					'text' => __( 'View', 'learnpress' )
				);
				$actions         = apply_filters( 'learn_press_user_profile_order_actions', $actions, $order );

				foreach ( $actions as $slug => $option ) {
					printf( '<a href="%s">%s</a>', $option['url'], $option['text'] );
				}
				?>
            </td>
        </tr>
	<?php endforeach; ?>
    </tbody>
</table>

<?php
learn_press_paging_nav( array(
	'num_pages' => $orders['num_pages'],
	'base'      => learn_press_user_profile_link( $user_id, LP()->settings->get( 'profile_endpoints.profile-orders' ) )
) );
?>


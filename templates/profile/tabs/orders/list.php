<?php
/**
 * Template for displaying list orders in orders tab of user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/orders/list.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();

$query_orders = $profile->query_orders( array( 'fields' => 'ids' ) );

if ( ! $query_orders['items'] ) {
	learn_press_display_message( __( 'No orders!', 'learnpress' ) );
	return;
}
?>

<h3 class="profile-heading"><?php esc_html_e( 'My Orders', 'learnpress' ); ?></h3>

<table class="lp-list-table profile-list-orders profile-list-table">
	<thead>
		<tr class="order-row">
			<th class="column-order-number"><?php esc_html_e( 'Order', 'learnpress' ); ?></th>
			<th class="column-order-total"><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
			<th class="column-order-status"><?php esc_html_e( 'Status', 'learnpress' ); ?></th>
			<th class="column-order-date"><?php esc_html_e( 'Date', 'learnpress' ); ?></th>
			<th class="column-order-actions"><?php esc_html_e( 'Actions', 'learnpress' ); ?></th>
		</tr>
	</thead>

	<tbody>
		<?php
		foreach ( $query_orders['items'] as $order_id ) {
			$order = learn_press_get_order( $order_id );
			?>

			<tr class="order-row">
				<td class="column-order-number">
					<a href="<?php echo esc_html( $order->get_view_order_url() ); ?>">
						<?php echo esc_html( $order->get_order_number() ); ?>
					</a>
				</td>
				<td class="column-order-total"><?php echo $order->get_formatted_order_total(); ?></td>
				<td class="column-order-status">
					<span class="lp-label label-<?php echo esc_attr( $order->get_status() ); ?>">
						<?php echo $order->get_order_status_html(); ?>
					</span>
				</td>
				<td class="column-order-date"><?php echo $order->get_order_date(); ?></td>
				<td class="column-order-actions">
					<?php
					$actions = $order->get_profile_order_actions();

					if ( $actions ) {
						foreach ( $actions as $action ) {
							printf( '<a href="%s">%s</a>', esc_url( $action['url'] ), $action['text'] );
						}
					}
					?>
				</td>
			</tr>
		<?php } ?>
	</tbody>

	<tfoot>
		<tr class="list-table-nav">
			<td colspan="2" class="nav-text"><?php echo $query_orders->get_offset_text(); ?></td>
			<td colspan="2" class="nav-pages"><?php $query_orders->get_nav_numbers( true ); ?></td>
		</tr>
	</tfoot>
</table>

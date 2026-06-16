<?php
/**
 * Template for displaying list orders in orders tab of user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/orders/list.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.2
 */

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit();

$profile = LP_Profile::instance();

$query_orders = $profile->query_orders( array( 'fields' => 'ids' ) );
if ( ! $query_orders->get_items() ) {
	Template::print_message( __( 'No orders!', 'learnpress' ), 'info' );
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
		foreach ( $query_orders->get_items() as $order_id ) {
			$order = learn_press_get_order( $order_id );
			?>

			<tr class="order-row">
				<td class="column-order-number">
					<a href="<?php echo esc_html( $order->get_view_order_url() ); ?>">
						<?php echo esc_html( $order->get_order_number() ); ?>
					</a>
				</td>
				<td class="column-order-total"><?php echo esc_html( $order->get_formatted_order_total() ); ?></td>
				<td class="column-order-status">
					<span class="lp-label label-<?php echo esc_attr( $order->get_status() ); ?>">
						<?php echo wp_kses_post( $order->get_order_status_html() ); ?>
					</span>
				</td>
				<td class="column-order-date"><?php echo esc_html( $order->get_order_date() ); ?></td>
				<td class="column-order-actions">
					<?php
					$actions = $order->get_profile_order_actions();

					if ( $actions ) {
						foreach ( $actions as $action ) {
							$action_text       = isset( $action['text'] ) ? (string) $action['text'] : '';
							$action_url        = isset( $action['url'] ) ? (string) $action['url'] : '';
							$action_class      = isset( $action['class'] ) ? (string) $action['class'] : '';
							$action_data_attrs = '';

							if ( ! empty( $action['data'] ) && is_array( $action['data'] ) ) {
								foreach ( $action['data'] as $data_key => $data_value ) {
									$action_data_attrs .= sprintf(
										' data-%1$s="%2$s"',
										esc_attr( str_replace( '_', '-', (string) $data_key ) ),
										esc_attr( (string) $data_value )
									);
								}
							}

							$action_attrs = '';
							if ( ! empty( $action_class ) ) {
								$action_attrs .= sprintf( ' class="%s"', esc_attr( $action_class ) );
							}
							$action_attrs .= $action_data_attrs;

							if ( ! empty( $action_url ) ) {
								printf( '<a href="%s"%s>%s</a>', esc_url_raw( $action_url ), $action_attrs, esc_html( $action_text ) );
							} else {
								printf( '<span class="order-action-text"%s>%s</span>', $action_attrs, esc_html( $action_text ) );
							}
						}
					}
					?>
				</td>
			</tr>
		<?php } ?>
	</tbody>

	<tfoot>
		<tr class="list-table-nav">
			<td colspan="3" class="nav-text"><?php echo esc_html( $query_orders->get_offset_text() ); ?></td>
			<td colspan="2" class="nav-pages"><?php $query_orders->get_nav_numbers( true ); ?></td>
		</tr>
	</tfoot>
</table>

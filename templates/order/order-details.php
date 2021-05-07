<?php
/**
 * Template for displaying order details.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/order/order-details.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( ! isset( $order ) ) {
	echo esc_html__( 'Invalid order', 'learnpress' );
	return;
}
?>

<h3><?php esc_html_e( 'Order Details', 'learnpress' ); ?></h3>

<table class="lp-list-table order-table-details">
	<thead>
		<tr>
			<th class="course-name"><?php esc_html_e( 'Course', 'learnpress' ); ?></th>
			<th class="course-total"><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
		</tr>
	</thead>

	<tbody>
	<?php
	$items = $order->get_items();
	if ( $items ) {
		$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() );

		foreach ( $items as $item_id => $item ) {
			if ( ! isset( $item['course_id'] ) ) {
				continue;
			}

			if ( apply_filters( 'learn-press/order/item-visible', true, $item ) ) {
				$course = learn_press_get_course( $item['course_id'] );

				if ( ! $course->exists() ) {
					continue;
				}
				?>

					<tr class="<?php echo esc_attr( apply_filters( 'learn-press/order/item-class', 'order-item', $item, $order ) ); ?>">
						<td class="course-name">
							<?php echo apply_filters( 'learn-press/order/item-name', sprintf( '<a href="%s">%s</a>', get_permalink( $item['course_id'] ), $item['name'] ), $item ); ?>
						</td>

						<td class="course-total">
							<?php
							$price = $course->get_price_html();

							if ( $price ) {
								$origin_price = $course->get_origin_price_html();

								if ( $course->has_sale_price() ) {
									echo '<span class="course-origin-price">' . $origin_price . '</span>';
								}
								echo '<span class="course-price">' . $price . '</span>';
							}
							?>
						</td>
					</tr>

					<?php
			}
		}
	}
	?>

	<?php do_action( 'learn-press/order/items-table', $order ); ?>
	</tbody>

	<tfoot>
		<tr>
			<th scope="row"><?php esc_html_e( 'Subtotal', 'learnpress' ); ?></th>
			<td><?php echo $order->get_formatted_order_subtotal(); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
			<td><?php echo $order->get_formatted_order_total(); ?></td>
		</tr>
	</tfoot>
</table>

<p>
	<strong><?php echo esc_html__( 'Order key:', 'learnpress' ); ?></strong>
	<?php echo $order->get_order_key(); ?>
</p>

<p>
	<strong><?php esc_html_e( 'Order status:', 'learnpress' ); ?></strong>
	<span class="lp-label label-<?php echo esc_attr( $order->get_status() ); ?>">
		<?php echo wp_kses_post( $order->get_order_status_html() ); ?>
	</span>
</p>

<?php do_action( 'learn-press/order/after-table-details', $order ); ?>

<?php
/**
 * Template for displaying order detail.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/order-received.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

if ( isset( $order ) ) :
	if ( is_int( $order ) ) {
		$order = learn_press_get_order( $order );
	}
	?>

	<p>
		<?php echo apply_filters( 'learn-press/order/received-order-message', esc_html__( 'Thank you. Your order has been received.', 'learnpress' ), $order ); ?>
	</p>

	<table class="order_details">
		<tr class="order">
			<th><?php esc_html_e( 'Order Number', 'learnpress' ); ?></th>
			<td>
				<?php echo $order->get_order_number(); ?>
			</td>
		</tr>
		<tr class="item">
			<th><?php esc_html_e( 'Item', 'learnpress' ); ?></th>
			<td>
				<?php
				$links = array();
				$items = $order->get_items();
				$count = sizeof( $items );

				foreach ( $items as $item ) {
					$_course = apply_filters( 'learn-press/order-received/item-product', learn_press_get_course( $item['course_id'] ) );

					if ( ! $_course ) {
						$links[] = esc_html__( 'Course does not exist', 'learnpress' );
					} else {
						$link = '<a href="' . $_course->get_permalink() . '">' . $_course->get_title() . ' (#' . $_course->get_id() . ')' . '</a>';

						if ( $count > 1 ) {
							$link = sprintf( '<li>%s</li>', $link );
						}

						$links[] = $link;
					}
				}

				if ( $count > 1 ) {
					echo sprintf( '<ol>%s</ol>', join( '', $links ) );
				} elseif ( 1 == $count ) {
					echo join( '', $links );
				} else {
					echo esc_html__( '(No item)', 'learnpress' );
				}
				?>

			</td>
		</tr>
		<tr class="date">
			<th><?php esc_html_e( 'Date', 'learnpress' ); ?></th>
			<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->get_order_date() ) ); ?></td>
		</tr>
		<tr class="total">
			<th><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
			<td><?php echo $order->get_formatted_order_total(); ?></td>
		</tr>

		<?php $method_title = $order->get_payment_method_title(); ?>
		<?php if ( $method_title ) : ?>
			<tr class="method">
				<th><?php esc_html_e( 'Payment Method', 'learnpress' ); ?></th>
				<td>
					<?php echo wp_kses_post( $method_title ); ?>
				</td>
			</tr>
		<?php endif; ?>

		<tr class="status">
			<th><?php esc_html_e( 'Status', 'learnpress' ); ?></th>
			<td>
				<strong><?php echo ucfirst( strtolower( $order->get_status() ) ); ?></strong>
			</td>
		</tr>
	</table>

	<?php do_action( 'learn-press/order/received/' . $order->payment_method, $order->id ); ?>
	<?php do_action( 'learn-press/order/received', $order ); ?>

<?php else : ?>

	<p><?php echo apply_filters( 'learn-press/order/received-invalid-order-message', esc_html__( 'Invalid order.', 'learnpress' ) ); ?></p>

<?php endif; ?>

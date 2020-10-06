<?php
/**
 * Template for displaying order detail.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/order-received.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.1.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php
if ( ! isset( $order_received ) ) {
	echo wp_sprintf( '<p>%s</p>', esc_html( apply_filters( 'learn-press/order/received-invalid-order-message', __( 'Invalid order.', 'learnpress' ) ) ) );

	return;
}

echo wp_sprintf( '<p>%s</p>', esc_html( apply_filters( 'learn-press/order/received-order-message', __( 'Thank you. Your order has been received.', 'learnpress' ) ) ) );

?>
    <table class="order_details">
        <tr class="order">
            <th><?php _e( 'Order Number', 'learnpress' ); ?></th>
            <td>
				<?php echo $order_received->get_order_number(); ?>
            </td>
        </tr>
        <tr class="item">
            <th><?php _e( 'Item', 'learnpress' ); ?></th>
            <td>
				<?php
				$links = array();
				$items = $order_received->get_items();
				$count = sizeof( $items );
				foreach ( $items as $item ) {
					if ( empty( $item['course_id'] ) || get_post_type( $item['course_id'] ) !== LP_COURSE_CPT ) {
						$links[] = apply_filters( 'learn-press/order-item-not-course-id', __( 'Course does not exist', 'learnpress' ), $item );
					} else {
						$link = '<a href="' . get_the_permalink( $item['course_id'] ) . '">' . get_the_title( $item['course_id'] ) . ' (#' . $item['course_id'] . ')' . '</a>';
						if ( $count > 1 ) {
							$link = sprintf( '<li>%s</li>', $link );
						}
						$links[] = apply_filters( 'learn-press/order-received-item-link', $link, $item );
					}
				}
				if ( $count > 1 ) {
					echo sprintf( '<ol>%s</ol>', join( "", $links ) );
				} elseif ( 1 == $count ) {
					echo join( "", $links );
				} else {
					echo __( '(No item)', 'learnpress' );
				} ?>
            </td>
        </tr>
        <tr class="date">
            <th><?php _e( 'Date', 'learnpress' ); ?></th>
            <td>
				<?php echo date_i18n( get_option( 'date_format' ), strtotime( $order_received->get_order_date() ) ); ?>
            </td>
        </tr>
        <tr class="total">
            <th><?php _e( 'Total', 'learnpress' ); ?></th>
            <td>
				<?php echo $order_received->get_formatted_order_total(); ?>
            </td>
        </tr>
		<?php if ( $method_title = $order_received->get_payment_method_title() ) : ?>
            <tr class="method">
                <th><?php _e( 'Payment Method', 'learnpress' ); ?></th>
                <td>
					<?php echo $method_title; ?>
                </td>
            </tr>
		<?php endif; ?>
    </table>

<?php do_action( 'learn-press/order/received/' . $order_received->payment_method, $order_received->get_id() ); ?>
<?php do_action( 'learn-press/order/received', $order_received ); ?>
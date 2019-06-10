<?php
/**
 * Template for displaying order detail.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/checkout/order-received.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * @var LP_Course $_course
 */
if ( isset( $order ) ) {

	if ( is_int( $order ) ) {
		$order = learn_press_get_order( $order );
	}
	?>
    <p><?php echo apply_filters( 'learn-press/order/received-order-message', __( 'Thank you. Your order has been received.', 'learnpress' ), $order ); ?></p>

    <table class="order_details">
        <tr class="order">
            <th><?php _e( 'Order Number', 'learnpress' ); ?></th>
            <td>
				<?php echo $order->get_order_number(); ?>
            </td>
        </tr>
        <tr class="item">
            <th><?php _e( 'Item', 'learnpress' ); ?></th>
            <td>
				<?php
				$links = array();
				$items = $order->get_items();
				$count = sizeof( $items );
				foreach ( $items as $item ) {

					$_course = apply_filters( 'learn-press/order-received/item-product', learn_press_get_course( $item['course_id'] ) );

					if ( ! $_course ) {
						$links[] = __( 'Course does not exist', 'learnpress' );
					} else {
						$link = '<a href="' . $_course->get_permalink() . '">' . $_course->get_title() . ' (#' . $_course->get_id() . ')' . '</a>';
						if ( $count > 1 ) {
							$link = sprintf( '<li>%s</li>', $link );
						}
						$links[] = $link;
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
				<?php echo date_i18n( get_option( 'date_format' ), strtotime( $order->get_order_date() ) ); ?>
            </td>
        </tr>
        <tr class="total">
            <th><?php _e( 'Total', 'learnpress' ); ?></th>
            <td>
				<?php echo $order->get_formatted_order_total(); ?>
            </td>
        </tr>
		<?php if ( $method_title = $order->get_payment_method_title() ) : ?>
            <tr class="method">
                <th><?php _e( 'Payment Method', 'learnpress' ); ?></th>
                <td>
					<?php echo $method_title; ?>
                </td>
            </tr>
		<?php endif; ?>

		<?php
		/**
		 * @since 3.x.x
		 */
		?>
        <tr class="status">
            <th><?php _e( 'Status', 'learnpress' ); ?></th>
            <td>
                <strong><?php echo $order->get_status(); ?></strong>
            </td>
        </tr>
    </table>

	<?php do_action( 'learn-press/order/received/' . $order->payment_method, $order->id ); ?>
	<?php do_action( 'learn-press/order/received', $order ); ?>

<?php } else { ?>

    <p><?php echo apply_filters( 'learn-press/order/received-invalid-order-message', __( 'Invalid order.', 'learnpress' ) ); ?></p>

<?php } ?>
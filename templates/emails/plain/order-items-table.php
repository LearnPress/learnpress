<?php
/**
 * Template for displaying the order information and items details.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 * @editor tungnx
 * @modify 4.1.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
/**
 * @var LP_Order $order
 */
if ( ! isset( $order ) || ! isset( $user_id ) ) {
	error_log( 'Invalid params on ' . __FILE__ );
	return;
}

$items = $order->get_items();

if ( ! $items ) {
	return;
}

echo '** ' . __( 'Order summary', 'learnpress' ) . " **\n";

echo '** ' . __( 'Order Number', 'learnpress' ) . ': ' . $order->get_order_number() . " **\n";

echo '** ' . __( 'Purchase Date', 'learnpress' ) . ': ' . $order->get_order_date() . " **\n";

echo '** ' . __( 'Payment Method', 'learnpress' ) . ': ' . $order->get_payment_method_title() . " **\n";

echo '** ' . __( 'Status', 'learnpress' ) . ': ' . strip_tags( $order->get_order_status_html() ) . " **\n";

echo '** ' . __( 'User Email', 'learnpress' ) . ': ' . $order->get_user_email( $user_id ) . " **\n\n";


$count = 0;
foreach ( $items as $item_id => $item ) :

	$course = apply_filters( 'learn-press/order/item-course', learn_press_get_course( $item['course_id'] ), $item );

	if ( $count ) {
		echo "\n*****************\n";
	}

	do_action( 'learn-press/before-email-order-item', $item_id, $item, $order );

	echo apply_filters( 'learn-press/email-order-item-name', $item['name'], $item );

	echo "\n" . sprintf( __( 'Quantity: %s', 'learnpress' ), apply_filters( 'learn-press/email-order-item-quantity', $item['quantity'], $item ) );

	echo "\n" . sprintf( __( 'Cost: %s', 'learnpress' ), apply_filters( 'learn-press/email-order-item-cost', $item['total'] . ' ' . $order->get_currency(), $item ) );

	do_action( 'learn-press/after-email-order-item', $item_id, $item, $order );

	$count ++;

endforeach;

echo "\n\n+++++++++++++++++++++++++\n\n";

echo __( 'Total', 'learnpress' ) . ': ' . $order->get_formatted_order_total();

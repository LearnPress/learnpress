<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !$order->get_items() ) {
	return;
}

foreach ( $order->get_items() as $item_id => $item ):

	$course = apply_filters( 'learn_press_order_item_course', learn_press_get_course( $item['course_id'] ), $item );

	echo apply_filters( 'learn_press_order_item_name', $item['name'], $item );

	do_action( 'learn_press_before_order_item', $item_id, $item, $order );

	echo "\n" . sprintf( __( 'Quantity: %s', 'learnpress' ), apply_filters( 'learn_press_email_order_item_quantity', $item['quantity'], $item ) );

	echo "\n" . sprintf( __( 'Cost: %s', 'learnpress' ), apply_filters( 'learn_press_email_order_item_cost', learn_press_format_price( $item['total'] ), $item ) );

	do_action( 'learn_press_after_order_item', $item_id, $item, $order );

	echo "\n\n";

endforeach;
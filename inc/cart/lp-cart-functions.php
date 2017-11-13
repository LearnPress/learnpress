<?php


/**
 * Return LP_Cart object instance
 *
 * @return mixed
 */
function learn_press_get_cart() {
	return LP()->cart;
}

function learn_press_enable_cart(){
	return apply_filters('learn-press/enable-cart', false);
}

/**
 * Get description for cart by join all item titles into one
 *
 * @return string
 */
function learn_press_get_cart_description() {
	$items       = LP()->cart->get_items();
	$description = array();
	if ( $items ) {
		foreach ( $items as $item ) {
			$description[] = apply_filters( 'learn_press_cart_item_description', get_the_title( $item['item_id'] ) );
		}
	}

	return apply_filters( 'learn_press_cart_description', join( ', ', $description ) );
}

function learn_press_get_cart_course_url() {
	$products = learn_press_get_cart( 'products' );
	$return   = '';
	if ( $products ) {
		foreach ( $products as $prop ) {
			$return = get_permalink( $prop['id'] );
			break;
		}
	}

	return apply_filters( 'learn_press_cart_course_url', $return );
}

/**
 * Return total of cart
 *
 * @return mixed
 */
function learn_press_get_cart_total() {
	return LP()->cart->total;
}


function learn_press_clear_cart_after_payment() {
	global $wp;

	if ( ! empty( $wp->query_vars['lp-order-received'] ) ) {

		$order_id  = absint( $wp->query_vars['lp-order-received'] );
		$order_key = isset( $_GET['key'] ) ? $_GET['key'] : '';

		if ( $order_id > 0 && ( $order = learn_press_get_order( $order_id ) ) ) {
			if ( $order->order_key === $order_key ) {
				LP()->cart->empty_cart();
			}
		}
	}

	if ( LP()->session->order_awaiting_payment > 0 ) {
		$order = learn_press_get_order( LP()->session->order_awaiting_payment );

		if ( $order && $order->id > 0 ) {
			if ( ! $order->has_status( array( 'failed', 'pending', 'cancelled' ) ) ) {
				LP()->cart->empty_cart();
				LP()->session->order_awaiting_payment = null;
			}
		}
	}
}

add_action( 'get_header', 'learn_press_clear_cart_after_payment' );
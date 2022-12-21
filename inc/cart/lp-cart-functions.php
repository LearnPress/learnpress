<?php
/**
 * Return LP_Cart object instance
 *
 * @return LP_Cart
 */
function learn_press_get_cart(): LP_Cart {
	return LearnPress::instance()->cart;
}

function learn_press_enable_cart() {
	return apply_filters( 'learn-press/enable-cart', false );
}

/**
 * Get description for cart by join all item titles into one
 *
 * @return string
 */
function learn_press_get_cart_description() {
	$items       = LearnPress::instance()->cart->get_items();
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
 * @deprecated 4.2.0
 */
function learn_press_get_cart_total() {
	_deprecated_function( __FUNCTION__, '4.2.0' );
	return LearnPress::instance()->cart->total;
}

/**
 * @deprecated 4.2.0
 */
function learn_press_clear_cart_after_payment() {
	global $wp;

	if ( ! empty( $wp->query_vars['lp-order-received'] ) ) {
		$order_id  = absint( $wp->query_vars['lp-order-received'] );
		$order_key = LP_Helper::sanitize_params_submitted( $_GET['key'] ?? '' );
		$order     = learn_press_get_order( $order_id );

		if ( $order_id > 0 && $order ) {
			if ( $order->order_key === $order_key ) {
				LearnPress::instance()->cart->empty_cart();
			}
		}
	}

	if ( ! is_null( LearnPress::instance()->session ) && LearnPress::instance()->session->get( 'order_awaiting_payment' ) > 0 ) {
		$order = learn_press_get_order( LearnPress::instance()->session->get( 'order_awaiting_payment' ) );

		if ( $order && $order->id > 0 ) {
			if ( ! $order->has_status( array( 'failed', 'pending', 'cancelled' ) ) ) {
				LearnPress::instance()->cart->empty_cart();
				LearnPress::instance()->session->remove( 'order_awaiting_payment' );
			}
		}
	}
}
//add_action( 'get_header', 'learn_press_clear_cart_after_payment' );

/**
 * @param LP_Cart $cart
 *
 * @return mixed
 * @deprecated 4.2.0
 */
function learn_press_custom_checkout_cart( $cart ) {
	if ( empty( $_REQUEST['single-item'] ) ) {
		return $cart;
	}

	$cart = clone $cart;
	$cart->empty_cart();
	$items = explode( ',', $_REQUEST['single-item'] );

	foreach ( $items as $item ) {
		$cart->add_to_cart( $item );
	}

	return $cart;
}
//add_filter( 'learn_press_checkout_cart', 'learn_press_custom_checkout_cart' );

<?php
/**
 * Defines functions related to order
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Generate unique key for an order.
 *
 * @return mixed
 */
function learn_press_generate_order_key() {
	return apply_filters( 'learn-press/order-key', strtoupper( uniqid( 'ORDER' ) ) );
}

/**
 * Return default order status when creating new.
 *
 * @param string $prefix . Optional
 *
 * @return string
 */
function learn_press_default_order_status( $prefix = '' ) {
	return apply_filters( 'learn-press/default-order-status', $prefix . 'pending' );
}

/**
 * Create or update an order.
 * If an 'ID' is passed throught $order_data, then update the existing order
 *
 * @param array $order_data {
 *                          Array of options to add or update an order
 *
 * @type int ID The order ID
 * @type
 * }
 *
 * @return LP_Order|WP_Error
 */
function learn_press_create_order( $order_data ) {

	$order = new LP_Order();
	$order->save();

	return $order;
}

/**
 * Create a new order or update an existing
 *
 * @param array $order_data
 *
 * @return LP_Order|WP_Error
 *
 * @throws Exception
 */

function learn_press_update_order( $order_data ) {
	if ( empty( $order_data['order_id'] ) ) {
		throw new Exception( __( 'Invalid order ID when updating.', 'learnpress' ) );
	}

	return learn_press_create_order( $order_data );
}

/**************************/
/**
 * Update Order status
 *
 * @param int
 * @param string
 *
 * @return bool
 */
function learn_press_update_order_status( $order_id, $status = '' ) {
	$order = new LP_Order( $order_id );
	if ( $order ) {
		return $order->update_status( $status );
	}

	return false;
}

/**
 * Add an order item into order.
 *
 * @param int   $order_id
 * @param mixed $item - Array of item data or ID
 *
 * @return bool
 */
function learn_press_add_order_item( $order_id, $item ) {
	$item_id = false;
	if ( $order = learn_press_get_order( $order_id ) ) {
		$item_id = $order->add_item( $item );
	}

	return $item_id;
}

/**
 * Remove an order item by order_item_id.
 *
 * @param int $order_id
 * @param int $item_id
 *
 * @return bool
 */
function learn_press_remove_order_item( $order_id, $item_id ) {
	if ( $order = learn_press_get_order( $order_id ) ) {
		return $order->remove_item( $item_id );
	}

	return false;
}

function _learn_press_before_delete_order_item( $item_id, $order_id ) {
	global $wpdb;
	if ( $order = learn_press_get_order_by_item_id( $item_id ) ) {
		$course_id = learn_press_get_order_item_meta( $item_id, '_course_id' );
		learn_press_delete_user_data( $order->user_id, $course_id );
	}
}

//add_action( 'learn-press/before-delete-order-item', '_learn_press_before_delete_order_item', 10, 2 );

function _learn_press_ajax_add_order_item_meta( $item ) {
	$item_id = $item['id'];
	if ( $order = learn_press_get_order_by_item_id( $item_id ) ) {
		if ( $order->get_status() == 'completed' ) {
			learn_press_auto_enroll_user_to_courses( $order->id );
		}
	}
}

add_action( 'learn_press_ajax_add_order_item_meta', '_learn_press_ajax_add_order_item_meta' );

function learn_press_get_order_by_item_id( $item_id ) {
	global $wpdb;
	$order_id = $wpdb->get_var( $wpdb->prepare( "SELECT order_id FROM {$wpdb->prefix}learnpress_order_items WHERE order_item_id = %d", $item_id ) );
	if ( $order_id && $order = learn_press_get_order( $order_id ) ) {
		return $order;
	}

	return false;
}

/**
 * Add order item meta data.
 *
 * @param int    $item_id
 * @param string $meta_key
 * @param mixed  $meta_value
 * @param string $prev_value
 *
 * @return false|int
 */
function learn_press_add_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return add_metadata( 'learnpress_order_item', $item_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Update order item meta data.
 *
 * @param int    $item_id
 * @param string $meta_key
 * @param mixed  $meta_value
 * @param string $prev_value
 *
 * @return bool|int
 */
function learn_press_update_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'learnpress_order_item', $item_id, $meta_key, $meta_value, $prev_value );
}

/**
 * Delete order item meta data.
 *
 * @param int    $item_id
 * @param string $meta_key
 * @param mixed  $meta_value
 * @param bool   $delete_all
 *
 * @return bool
 */
function learn_press_delete_order_item_meta( $item_id, $meta_key, $meta_value, $delete_all = false ) {
	return delete_metadata( 'learnpress_order_item', $item_id, $meta_key, $meta_value, $delete_all );
}

/**
 * Get order item meta data.
 *
 * @param int    $item_id
 * @param string $meta_key
 * @param bool   $single
 *
 * @return mixed
 */
function learn_press_get_order_item_meta( $item_id, $meta_key, $single = true ) {
	return get_metadata( 'learnpress_order_item', $item_id, $meta_key, $single );
}

/**
 * Get order
 *
 * @param mixed $the_order
 *
 * @return LP_Order|bool object instance
 */
function learn_press_get_order( $the_order = false ) {
	global $post;
	$the_id = 0;
	if ( false === $the_order && is_a( $post, 'WP_Post' ) && LP_ORDER_CPT === get_post_type( $post ) ) {
		$the_id = $post->ID;
	} elseif ( is_numeric( $the_order ) ) {
		$the_id = $the_order;
	} elseif ( $the_order instanceof LP_Order ) {
		$the_id = $the_order->get_id();
	} elseif ( ! empty( $the_order->ID ) ) {
		$the_id = $the_order->ID;
	}

	if ( LP_ORDER_CPT != get_post_type( $the_id ) ) {
		return false;
	}

	return new LP_Order( $the_id );
}

/**
 * get confirm order URL
 *
 * @param int $order_id
 *
 * @return string
 */
function learn_press_get_order_confirm_url( $order_id = 0 ) {
	$url = '';

	if ( ( $confirm_page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && get_post( $confirm_page_id ) ) {
		$url = get_permalink( $confirm_page_id );
		if ( $order_id ) {
			$url = join( preg_match( '!\?!', $url ) ? '&' : '?', array( $url, "order_id={$order_id}" ) );
		}
	} else {
		$order = new LP_Order( $order_id );
		if ( ( $items = $order->get_items() ) && ! empty( $items->products ) ) {
			$course = reset( $items->products );
			$url    = get_permalink( $course['id'] );
		} else {
			$url = get_home_url() /* SITE_URL */
			;
		}
	}

	return $url;
}


function learn_press_do_transaction( $method, $transaction = false ) {
	LP_Gateways::instance()->get_available_payment_gateways();

	do_action( 'learn_press_do_transaction_' . $method, $transaction );
}

function learn_press_set_transient_transaction( $method, $temp_id, $user_id, $transaction ) {
	set_transient( $method . '-' . $temp_id, array(
		'user_id'            => $user_id,
		'transaction_object' => $transaction
	), 60 * 60 * 24 );
}

function learn_press_get_transient_transaction( $method, $temp_id ) {
	return get_transient( $method . '-' . $temp_id );
}

function learn_press_delete_transient_transaction( $method, $temp_id ) {
	return delete_transient( $method . '-' . $temp_id );
}


/**
 * Deprecated function
 *
 * @param array $args
 *
 * @return int
 */
function learn_press_add_transaction( $args = null ) {
	//_deprecated_function( 'learn_press_add_transaction', '1.0', 'learn_press_add_order' );
	return learn_press_add_order( $args );
}

/**
 * @param null $args
 *
 * @return mixed
 */
function learn_press_add_order( $args = null ) {
	//$method, $method_id, $status = 'Pending', $customer_id = false, $transaction_object = false, $args = array()
	$default_args = array(
		'method'             => '',
		'method_id'          => '',
		'status'             => '',
		'user_id'            => null,
		'order_id'           => 0,
		'parent'             => 0,
		'transaction_object' => false
	);

	$args       = wp_parse_args( $args, $default_args );
	$order_data = array();

	if ( $args['order_id'] > 0 && get_post( $args['order_id'] ) ) {
		$updating         = true;
		$order_data['ID'] = $args['order_id'];
	} else {
		$updating                  = false;
		$order_data['post_type']   = LP_ORDER_CPT;
		$order_data['post_status'] = ! empty( $args['status'] ) ? 'publish' : 'lpr-draft';
		$order_data['ping_status'] = 'closed';
		$order_data['post_author'] = ( $order_owner_id = learn_press_cart_order_instructor() ) ? $order_owner_id : 1; // always is administrator
		$order_data['post_parent'] = absint( $args['parent'] );
	}
	$order_title = array();
	if ( $args['method'] ) {
		$order_title[] = $args['method'];
	}
	if ( $args['method_id'] ) {
		$order_title[] = $args['method_id'];
	}
	$order_title[]            = date_i18n( 'Y-m-d-H:i:s' );
	$order_data['post_title'] = join( '-', $order_title );

	if ( empty( $args['user_id'] ) ) {
		$user            = learn_press_get_current_user();
		$args['user_id'] = $user->get_id();
	}

	if ( ! $args['transaction_object'] ) {
		$args['transaction_object'] = learn_press_generate_transaction_object();
	}

	if ( ! $updating ) {
		if ( $transaction_id = wp_insert_post( $order_data ) ) {
			update_post_meta( $transaction_id, '_learn_press_transaction_method', $args['method'] );

			//update_post_meta( $transaction_id, '_learn_press_transaction_status',    $status );
			update_post_meta( $transaction_id, '_learn_press_customer_id', $args['user_id'] );
			update_post_meta( $transaction_id, '_learn_press_customer_ip', learn_press_get_ip() );
			update_post_meta( $transaction_id, '_learn_press_order_items', $args['transaction_object'] );

			add_user_meta( $args['user_id'], '_lpr_order_id', $transaction_id );


		}
	} else {
		$transaction_id = wp_update_post( $order_data );
	}

	if ( $transaction_id ) {
		if ( ! empty( $args['status'] ) ) {
			learn_press_update_order_status( $transaction_id, $args['status'] );
		}
		update_post_meta( $transaction_id, '_learn_press_transaction_method_id', $args['method_id'] );

		if ( $args['transaction_object'] ) {
			update_post_meta( $transaction_id, '_learn_press_order_items', $args['transaction_object'] );
		}

		if ( ! empty( $args['status'] ) ) {
			if ( $updating ) {
				return apply_filters( 'learn_press_update_transaction_success', $transaction_id, $args );
			} else {
				return apply_filters( 'learn_press_add_transaction_success', $transaction_id, $args );
			}
		}

		return $transaction_id;
	}

	return false;

	//do_action( 'learn_press_add_transaction_fail', $args );// $method, $method_id, $status, $customer_id, $transaction_object, $args );
}

function learn_press_payment_method_from_slug( $order_id ) {
	$slug = get_post_meta( $order_id, '_learn_press_transaction_method', true );

	return apply_filters( 'learn_press_payment_method_from_slug_' . $slug, $slug );
}

function learn_press_generate_transaction_object() {
	$cart = learn_press_get_cart();


	if ( $products = $cart->get_items() ) {
		foreach ( $products as $key => $product ) {
			$products[ $key ]['product_base_price'] = floatval( learn_press_get_course_price( $product['id'] ) );
			$products[ $key ]['product_subtotal']   = floatval( learn_press_get_course_price( $product['id'] ) * $product['quantity'] );
			$products[ $key ]['product_name']       = get_the_title( $product['id'] );
			$products                               = apply_filters( 'learn_press_generate_transaction_object_products', $products, $key, $product );
		}
	}


	$transaction_object                         = new stdClass();
	$transaction_object->cart_id                = $cart->get_cart_id();
	$transaction_object->total                  = round( $cart->get_total(), 2 );
	$transaction_object->sub_total              = $cart->get_sub_total();
	$transaction_object->currency               = learn_press_get_currency();
	$transaction_object->description            = learn_press_get_cart_description();
	$transaction_object->products               = $products;
	$transaction_object->coupons                = '';
	$transaction_object->coupons_total_discount = '';

	$transaction_object = apply_filters( 'learn_press_generate_transaction_object', $transaction_object );

	return $transaction_object;
}

/**
 * Get the author ID of course in the cart
 *
 * Currently, it only get the first item in cart
 *
 * @return int
 */
function learn_press_cart_order_instructor() {
	$cart = learn_press_get_cart();
	if ( $products = $cart->get_items() ) {
		foreach ( $products as $key => $product ) {
			$post = get_post( $product['id'] );
			if ( $post && ! empty( $post->ID ) ) {
				return $post->post_author;
			}
		}
	}

	return 0;
}

function learn_press_get_orders( $args = array() ) {
	//_deprecated_function( __FUNCTION__, '3.0.0', 'get_posts' );
	$args['post_type'] = LP_ORDER_CPT;
	$orders            = get_posts( $args );

	return apply_filters( 'learn_press_get_orders', $orders, $args );
}

/**
 * Count orders by it's status
 *
 * @param array $args
 *
 * @return array
 */
function learn_press_count_orders( $args = array() ) {
	if ( is_string( $args ) ) {
		$args = array( 'status' => $args );
	} else {
		$args = wp_parse_args(
			$args,
			array(
				'status' => ''
			)
		);
	}
	global $wpdb;
	$statuses = $args['status'];

	if ( ! $statuses ) {
		$statuses = learn_press_get_register_order_statuses();
		$statuses = array_keys( $statuses );
	}

	settype( $statuses, 'array' );
	$size_of_status = sizeof( $statuses );

	foreach ( $statuses as $k => $status ) {
		$statuses[ $k ] = ! preg_match( '~^lp-~', $status ) ? 'lp-' . $status : $status;
	}

	$format     = array_fill( 0, $size_of_status, '%s' );
	$counts     = array_fill_keys( $statuses, 0 );
	$statuses[] = LP_ORDER_CPT;
	$query      = $wpdb->prepare( "
		SELECT COUNT(ID) AS count, post_status AS status
		FROM {$wpdb->posts} o
		WHERE post_status IN(" . join( ',', $format ) . ")
		AND post_type = %s
		GROUP BY o.post_status
	", $statuses );

	if ( $results = $wpdb->get_results( $query ) ) {
		foreach ( $results as $result ) {
			if ( array_key_exists( $result->status, $counts ) ) {
				$counts[ $result->status ] = absint( $result->count );
			}
		}
	}

	return $size_of_status > 1 ? $counts : reset( $counts );
}

/**
 * Format price with currency and other settings.
 *
 * @param float $price
 * @param array $args
 *
 * @return int|string
 */
function learn_press_format_price( $price, $args = array() ) {
	if ( is_bool( $args ) || is_string( $args ) ) {
		$with_currency = $args;
	} else {
		$with_currency = false;
	}
	if ( ! is_numeric( $price ) ) {
		$price = 0;
	}
	$settings            = LP()->settings;
	$before              = $after = '';
	$args                = wp_parse_args(
		$args,
		array(
			'with_currency'       => $with_currency,
			'decimals_separator'  => false,
			'number_of_decimals'  => false,
			'thousands_separator' => false
		)
	);
	$with_currency       = $args['with_currency'];
	$thousands_separator = $args['thousands_separator'] === false ? $settings->get( 'thousands_separator', ',' ) : $args['thousands_separator'];
	$number_of_decimals  = $args['number_of_decimals'] === false ? $settings->get( 'number_of_decimals', 2 ) : $args['number_of_decimals'];
	$decimals_separator  = $args['decimals_separator'] === false ? $settings->get( 'decimals_separator', '.' ) : $args['decimals_separator'];

	if ( $with_currency ) {
		if ( gettype( $with_currency ) != 'string' ) {
			$currency = learn_press_get_currency_symbol();
		} else {
			$currency = $with_currency;
		}

		switch ( $settings->get( 'currency_pos' ) ) {
			default:
				$before = $currency;
				break;
			case 'left_with_space':
				$before = $currency . ' ';
				break;
			case 'right':
				$after = $currency;
				break;
			case 'right_with_space':
				$after = ' ' . $currency;
		}
	}

	$price =
		$before
		. number_format(
			$price,
			$number_of_decimals,
			$decimals_separator,
			$thousands_separator
		) . $after;

	return $price;
}

/**
 * Update
 *
 * @param $order_id
 *
 * @return array|bool
 */
function learn_press_update_order_items( $order_id ) {
	if ( ! $order = learn_press_get_order( $order_id ) ) {
		return false;
	}
	$subtotal = 0;
	$total    = 0;
	if ( $items = $order->get_items() ) {
		/*
			[name] => What is LearnPress?
    		[id] => 214
    		[course_id] => 650
    		[quantity] => 1
    		[subtotal] => 1.9
    		[total] => 1.9
		 */

		foreach ( $items as $item ) {
			$subtotal += $item['subtotal'];
			$total    += $item['total'];
		}
	}


	update_post_meta( $order_id, '_order_currency', learn_press_get_currency() );
	update_post_meta( $order_id, '_prices_include_tax', 'no' );
	update_post_meta( $order_id, '_order_subtotal', $subtotal );
	update_post_meta( $order_id, '_order_total', $total );
	update_post_meta( $order_id, '_order_key', learn_press_generate_order_key() );
	update_post_meta( $order_id, '_payment_method', '' );
	update_post_meta( $order_id, '_payment_method_title', '' );
	update_post_meta( $order_id, '_order_version', '1.0' );

	return array(
		'subtotal' => $subtotal,
		'total'    => $total,
		'currency' => learn_press_get_currency()
	);
}

/**
 * Format order's ID in ten numbers. Eg: 0000000XXX.
 *
 * @param int $order_number
 *
 * @since 2.0.0
 *
 * @return string
 */
function learn_press_transaction_order_number( $order_number ) {
	$formatted_number = apply_filters( 'learn_press_get_order_number', '#' . sprintf( "%'.010d", $order_number ), $order_number );

	return apply_filters( 'learn-press/order-number-formatted', $formatted_number, $order_number );
}

/**
 * Get label of an order's statues.
 *
 * @param int|string $order_id - Optional. ID of an order or status.
 *
 * @return string|bool
 */
function learn_press_get_order_status_label( $order_id = 0 ) {
	$statuses = learn_press_get_order_statuses();
	if ( is_numeric( $order_id ) ) {
		$status = get_post_status( $order_id );
	} else {
		$status = $order_id;
	}

	return ! empty( $statuses[ $status ] ) ? $statuses[ $status ] : false;
}

/**
 * Get list of registered order's statuses and/or labels.
 *
 * @param bool $prefix
 * @param bool $status_only
 *
 * @since 2.1.7
 *
 * @return array
 */
function learn_press_get_order_statuses( $prefix = true, $status_only = false ) {
	$register_statues = learn_press_get_register_order_statuses();
	if ( ! $prefix ) {
		$order_statuses = array();
		foreach ( $register_statues as $k => $v ) {
			$k                    = preg_replace( '~^lp-~', '', $k );
			$order_statuses[ $k ] = $v;
		}
	} else {
		$order_statuses = $register_statues;
	}

	$order_statuses = wp_list_pluck( $order_statuses, 'label' );

	if ( $status_only ) {
		$order_statuses = array_keys( $order_statuses );
	}

	// @deprecated
	$order_statuses = apply_filters( 'learn_press_order_statuses', $order_statuses );

	// @since 3.0.0
	return apply_filters( 'learn-press/order-statues', $order_statuses );
}

/**
 * Get list of registered order's statues for registering with wp post's status.
 *
 * @since 2.0.0
 *
 * @return array
 */
function learn_press_get_register_order_statuses() {
	$order_statues = array();

	$order_statues['lp-pending']    = array(
		'label'                     => _x( 'Pending Payment', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'learnpress' )
	);
	$order_statues['lp-processing'] = array(
		'label'                     => _x( 'Processing', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'learnpress' )
	);
	$order_statues['lp-completed']  = array(
		'label'                     => _x( 'Completed', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'learnpress' )
	);
	$order_statues['lp-cancelled']  = array(
		'label'                     => _x( 'Cancelled', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'learnpress' )
	);
	$order_statues['lp-failed'] = array(
		'label'                     => _x( 'Failed', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'learnpress' )
	);

	return $order_statues;
}

function _learn_press_get_order_status_description( $status ) {
	static $descriptions = null;
	$descriptions = array(
		'pending'    => __( 'Order received in case user buy a course but doesn\'t finalise the order.', 'learnpress' ),
		'processing' => __( 'Payment received and the order is awaiting fulfillment.', 'learnpress' ),
		'completed'  => __( 'Order fulfilled and complete.', 'learnpress' ),
		'cancelled'  => __( 'The order is cancelled by an admin or the customer.', 'learnpress' )
	);

	return apply_filters( 'learn_press_order_status_description', ! empty( $descriptions[ $status ] ) ? $descriptions[ $status ] : '' );
}

/**
 * Get status of an order by the ID.
 *
 * @param int $order_id
 *
 * @return bool|string
 */
function learn_press_get_order_status( $order_id ) {
	$order = learn_press_get_order( $order_id );

	if ( $order ) {
		return $order->get_status();
	}

	return false;
}

if ( ! function_exists( 'learn_press_cancel_order_process' ) ) {
	/**
	 * Process action allows user to cancel an order is pending
	 * in their profile.
	 */
	function learn_press_cancel_order_process() {
		if ( empty( $_REQUEST['cancel-order'] ) || empty( $_REQUEST['lp-nonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_REQUEST['lp-nonce'] ), 'cancel-order' ) || is_admin() ) {
			return;
		}

		$order_id = absint( $_REQUEST['cancel-order'] );
		$order    = learn_press_get_order( $order_id );
		$user     = learn_press_get_current_user();

		if ( ! $order ) {
			learn_press_add_message( sprintf( __( 'Order number <strong>%s</strong> not found', 'learnpress' ), $order_id ), 'error' );
		} else if ( $order->has_status( 'pending' ) ) {
			$order->update_status( 'cancelled' );
			$order->add_note( __( 'Order cancelled by customer', 'learnpress' ) );

			// set updated message
			learn_press_add_message( sprintf( __( 'Order number <strong>%s</strong> has been cancelled', 'learnpress' ), $order->get_order_number() ) );
			$url = $order->get_cancel_order_url( true );
		} else {
			learn_press_add_message( sprintf( __( 'Order number <strong>%s</strong> can not be cancelled', 'learnpress' ), $order->get_order_number() ), 'error' );
		}
		if( !$url ){
			$url = learn_press_user_profile_link( $user->get_id(), LP()->settings->get( 'profile_endpoints.profile-orders', 'orders' ) );
		}
		wp_safe_redirect( $url );
		exit();
	}
}
add_action( 'init', 'learn_press_cancel_order_process' );

/**
 * Auto enroll course after user checkout
 *
 * @param $result
 * @param $order_id
 */
function _learn_press_checkout_auto_enroll_free_course( $result, $order_id ) {
	return;
	$enrolled = false;
	if ( $order_id && $order = learn_press_get_order( $order_id, true /* force to get all changed */ ) ) {
		$user = learn_press_get_user( $order->user_id, true );
		if ( $order_items = $order->get_items() ) {
			foreach ( $order_items as $item ) {
				if ( $user->has_enrolled_course( $item['course_id'] ) ) {
					continue;
				}
				if ( $user->enroll( $item['course_id'] ) ) {
					$enrolled = $item['course_id'];
				}
			}
			if ( ! $enrolled ) {
				$item     = reset( $order_items );
				$enrolled = $item['course_id'];
			}
		}
	}
	if ( $enrolled ) {
		learn_press_add_message( sprintf( __( 'You have enrolled in this course. <a href="%s">Order details</a>', 'learnpress' ), $result['redirect'] ) );
		$result['redirect'] = get_the_permalink( $enrolled );
		LP()->cart->empty_cart();
	}

	return $result;
}


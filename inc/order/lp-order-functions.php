<?php
/**
 * Defines functions related to order
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function learn_press_method_title_for_free( $title, $name ) {
	if ( empty( $title ) && empty( $name ) ) {
		$title = __( 'Free Payment', 'learnpress' );
	}
	return $title;
}

//add_filter( 'learn_press_display_payment_method_title', 'learn_press_method_title_for_free', 10, 2 );
/**
 * Create new order
 *
 * @param array
 *
 * @return LP_Order instance
 */
function learn_press_create_order( $order_data ) {
	$order_data_defaults = array(
		'ID'          => 0,
		'post_author' => '1',
		'post_parent' => '0',
		'post_type'   => LP_ORDER_CPT,
		'post_status' => 'lp-' . apply_filters( 'learn_press_default_order_status', 'pending' ),
		'ping_status' => 'closed',
		'post_title'  => __( 'Order on', 'learnpress' ) . ' ' . current_time( "l jS F Y h:i:s A" )
	);
	$order_data_defaults = apply_filters( 'learn_press_defaults_order_data', $order_data_defaults );
	$order_data          = wp_parse_args( $order_data, $order_data_defaults );

	if ( $order_data['status'] ) {
		if ( !in_array( 'lp-' . $order_data['status'], array_keys( learn_press_get_order_statuses() ) ) ) {
			return new WP_Error( 'learn_press_invalid_order_status', __( 'Invalid order status', 'learnpress' ) );
		}
		$order_data['post_status'] = 'lp-' . $order_data['status'];
	}

	if ( !is_null( $order_data['user_note'] ) ) {
		$order_data['post_excerpt'] = $order_data['user_note'];
	}

	if ( $order_data['ID'] ) {
		$order_data = apply_filters( 'learn_press_update_order_data', $order_data );
		wp_update_post( $order_data );
		$order_id = $order_data['ID'];
	} else {
		$order_data = apply_filters( 'learn_press_new_order_data', $order_data );
		$order_id   = wp_insert_post( $order_data );
	}

	if ( $order_id ) {
		$order = LP_Order::instance( $order_id );

		update_post_meta( $order_id, '_order_currency', learn_press_get_currency() );
		update_post_meta( $order_id, '_prices_include_tax', 'no' );
		update_post_meta( $order_id, '_user_ip_address', learn_press_get_ip() );
		update_post_meta( $order_id, '_user_agent', isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );
		update_post_meta( $order_id, '_user_id', get_current_user_id() );
		update_post_meta( $order_id, '_order_subtotal', LP()->cart->subtotal );
		update_post_meta( $order_id, '_order_total', LP()->cart->total );
		update_post_meta( $order_id, '_order_key', apply_filters( 'learn_press_generate_order_key', uniqid( 'order' ) ) );
		update_post_meta( $order_id, '_payment_method', '' );
		update_post_meta( $order_id, '_payment_method_title', '' );
		update_post_meta( $order_id, '_order_version', '1.0' );
		update_post_meta( $order_id, '_created_via', !empty( $order_data['created_via'] ) ? $order_data['created_via'] : 'checkout' );
	}

	return LP_Order::instance( $order_id, true );
}

/**
 * Create a new order or update an existing
 *
 * @param array
 *
 * @return LP_Order instance
 */

function learn_press_update_order( $order_data ) {
	return learn_press_create_order( $order_data );
}

function learn_press_get_booking_id_by_key( $order_key ) {
	global $wpdb;

	$order_id = $wpdb->get_var(
		$wpdb->prepare( "
			SELECT post_id
			FROM {$wpdb->prefix}postmeta pm
			INNER JOIN {$wpdb->prefix}posts p ON p.ID = pm.post_id
			WHERE meta_key = '_hb_booking_key'
			AND meta_value = %s
			AND p.post_type = %s
		", $order_key, LP_ORDER_CPT )
	);

	return $order_id;
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
	$order = LP_Order::instance( $order_id );
	if ( $order ) {
		$order->update_status( $status );
	}
}

function learn_press_add_order_item( $order_id, $item ) {
	global $wpdb;

	$order_id = absint( $order_id );

	if ( !$order_id )
		return false;

	$defaults = array(
		'order_item_name' => '',
		'order_id'        => $order_id
	);

	$item = wp_parse_args( $item, $defaults );
	if ( array_key_exists( 'data', $item ) ) {
		$item['data'] = maybe_serialize( $item['data'] );
	}
	//$course = LP_Course::get_course( $item['item_id'] );
	$wpdb->insert(
		$wpdb->learnpress_order_items,
		array(
			'order_item_name' => $item['order_item_name'],
			'order_id'        => $item['order_id']
		),
		array(
			'%s', '%d'
		)
	);

	$item_id = absint( $wpdb->insert_id );

	do_action( 'learn_press_new_order_item', $item_id, $item, $order_id );

	return $item_id;
}

function learn_press_remove_order_item( $item_id ) {
	global $wpdb;

	$item_id = absint( $item_id );

	if ( !$item_id )
		return false;

	do_action( 'learn_press_before_delete_order_item', $item_id );

	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_order_items WHERE order_item_id = %d", $item_id ) );
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_order_itemmeta WHERE learnpress_order_item_id = %d", $item_id ) );

	do_action( 'learn_press_delete_order_item', $item_id );

	return true;
}

function _learn_press_before_delete_order_item( $item_id ) {
	global $wpdb;
	if ( $order = learn_press_get_order_by_item_id( $item_id ) ) {
		$course_id = learn_press_get_order_item_meta( $item_id, '_course_id' );
		learn_press_delete_user_data( $order->user_id, $course_id );
	}
}

add_action( 'learn_press_before_delete_order_item', '_learn_press_before_delete_order_item' );

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

function learn_press_add_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return add_metadata( 'learnpress_order_item', $item_id, $meta_key, $meta_value, $prev_value );
}

function learn_press_update_order_item_meta( $item_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata( 'learnpress_order_item', $item_id, $meta_key, $meta_value, $prev_value );
}

function learn_press_delete_order_item_meta( $item_id, $meta_key, $meta_value, $delete_all = false ) {
	return delete_metadata( 'learnpress_order_item', $item_id, $meta_key, $meta_value, $delete_all );
}

function learn_press_get_order_item_meta( $item_id, $meta_key, $single = true ) {
	return get_metadata( 'learnpress_order_item', $item_id, $meta_key, $single );
}

/*******************************/

/*
 * Check to see if a user can view the order
 *
 * @param      $order_id
 * @param null $user_id
 * @return bool
 */
function learn_press_user_can_view_order( $order_id, $user_id = null ) {
	if ( !intval( $order_id ) ) return false;
	if ( !$user_id && !( $user_id = get_current_user_id() ) ) return false;
	if ( !get_post( $order_id ) ) return false;

	$orders = get_user_meta( $user_id, '_lpr_order_id' );

	if ( !in_array( $order_id, $orders ) ) return false;

	return true;
}

/**
 * Function get order information
 *
 * @param int $order_id
 *
 * @return LP_Order object instance
 */
function learn_press_get_order( $the_order, $force = false ) {
	if ( !$the_order ) {
		return false;
	}

	global $post;

	if ( false === $the_order ) {
		$the_order = $post;
	} elseif ( is_numeric( $the_order ) ) {
		$the_order = get_post( $the_order );
	} elseif ( $the_order instanceof LP_Order ) {
		$the_order = get_post( $the_order->id );
	}

	if ( !$the_order || !is_object( $the_order ) ) {
		return false;
	}

	return LP_Order::instance( $the_order, $force );
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
		if ( ( $items = $order->get_items() ) && !empty( $items->products ) ) {
			$course = reset( $items->products );
			$url    = get_permalink( $course['id'] );
		} else {
			$url = get_site_url();
		}
	}
	return $url;
}


function learn_press_do_transaction( $method, $transaction = false ) {
	LP_Gateways::instance()->get_available_payment_gateways();

	do_action( 'learn_press_do_transaction_' . $method, $transaction );
}

function learn_press_set_transient_transaction( $method, $temp_id, $user_id, $transaction ) {
	set_transient( $method . '-' . $temp_id, array( 'user_id' => $user_id, 'transaction_object' => $transaction ), 60 * 60 * 24 );
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
		$order_data['post_status'] = !empty( $args['status'] ) ? 'publish' : 'lpr-draft';
		$order_data['ping_status'] = 'closed';
		$order_data['post_author'] = ( $order_owner_id = learn_press_cart_order_instructor() ) ? $order_owner_id : 1; // always is administrator
		$order_data['post_parent'] = absint( $args['parent'] );
	}
	$order_title = array();
	if ( $args['method'] ) $order_title[] = $args['method'];
	if ( $args['method_id'] ) $order_title[] = $args['method_id'];
	$order_title[]            = date_i18n( 'Y-m-d-H:i:s' );
	$order_data['post_title'] = join( '-', $order_title );

	if ( empty( $args['user_id'] ) ) {
		$user            = learn_press_get_current_user();
		$args['user_id'] = $user->ID;
	}

	if ( !$args['transaction_object'] ) $args['transaction_object'] = learn_press_generate_transaction_object();

	if ( !$updating ) {
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
		if ( !empty( $args['status'] ) ) {
			learn_press_update_order_status( $transaction_id, $args['status'] );
		}
		update_post_meta( $transaction_id, '_learn_press_transaction_method_id', $args['method_id'] );

		if ( $args['transaction_object'] ) {
			update_post_meta( $transaction_id, '_learn_press_order_items', $args['transaction_object'] );
		}

		if ( !empty( $args['status'] ) ) {
			if ( $updating ) {
				return apply_filters( 'learn_press_update_transaction_success', $transaction_id, $args );
			} else
				return apply_filters( 'learn_press_add_transaction_success', $transaction_id, $args );
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
			$products[$key]['product_base_price'] = floatval( learn_press_get_course_price( $product['id'] ) );
			$products[$key]['product_subtotal']   = floatval( learn_press_get_course_price( $product['id'] ) * $product['quantity'] );
			$products[$key]['product_name']       = get_the_title( $product['id'] );
			$products                             = apply_filters( 'learn_press_generate_transaction_object_products', $products, $key, $product );
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
			if ( $post && !empty( $post->ID ) ) {
				return $post->post_author;
			}
		}
	}
	return 0;
}

function learn_press_handle_purchase_request() {
	LP_Gateways::instance()->get_available_payment_gateways();
	$method_var = 'learn-press-transaction-method';

	$requested_transaction_method = empty( $_REQUEST[$method_var] ) ? false : $_REQUEST[$method_var];
	learn_press_do_transaction( $requested_transaction_method );
}

function learn_press_get_orders( $args = array() ) {
	$defaults = array(
		'post_type' => LP_ORDER_CPT,
	);

	$args = wp_parse_args( $args, $defaults );

	$args['meta_query'] = empty( $args['meta_query'] ) ? array() : $args['meta_query'];

	if ( !empty( $args['transaction_method'] ) ) {
		$meta_query           = array(
			'key'   => '_learn_press_transaction_method',
			'value' => $args['transaction_method'],
		);
		$args['meta_query'][] = $meta_query;
	}

	$args = apply_filters( 'learn_press_get_orders_get_posts_args', $args );
        $orders = get_posts( $args );
	if ( $orders ) {
            // do somethings
	}

	return apply_filters( 'learn_press_get_orders', $orders, $args );
}

function learn_press_get_course_price_text( $price, $course_id ) {
	if ( !$price && LP_COURSE_CPT == get_post_type( $course_id ) ) {
		$price = __( 'Free', 'learnpress' );
	}
	return $price;
}

add_filter( 'learn_press_get_course_price', 'learn_press_get_course_price_text', 5, 2 );

function learn_press_get_order_items( $order_id ) {
	return get_post_meta( $order_id, '_learn_press_order_items', true );
}

function learn_press_format_price( $price, $with_currency = false ) {
	if ( !is_numeric( $price ) )
		$price = 0;
	$settings = LP()->settings;
	$before   = $after = '';
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
			$settings->get( 'number_of_decimals', 2 ),
			$settings->get( 'decimals_separator', '.' ),
			$settings->get( 'thousands_separator', ',' )
		) . $after;

	return $price;
}

function learn_press_update_order_items( $order_id ) {
	if ( !$order = learn_press_get_order( $order_id ) ) {
		return;
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
			$total += $item['total'];
		}
	}


	update_post_meta( $order_id, '_order_currency', learn_press_get_currency() );
	update_post_meta( $order_id, '_prices_include_tax', 'no' );
	//update_post_meta( $order_id, '_user_ip_address', learn_press_get_ip() );
	//update_post_meta( $order_id, '_user_agent', isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '' );
	//update_post_meta( $order_id, '_user_id', learn_press_get_current_user_id() );
	update_post_meta( $order_id, '_order_subtotal', $subtotal );
	update_post_meta( $order_id, '_order_total', $total );
	update_post_meta( $order_id, '_order_key', apply_filters( 'learn_press_generate_order_key', uniqid( 'order' ) ) );
	update_post_meta( $order_id, '_payment_method', '' );
	update_post_meta( $order_id, '_payment_method_title', '' );
	update_post_meta( $order_id, '_order_version', '1.0' );

	return array(
		'subtotal' => $subtotal,
		'total'    => $total,
		'currency' => learn_press_get_currency()
	);
}

function learn_press_transaction_order_number( $order_number ) {
	return '#' . sprintf( "%'.010d", $order_number );
}

function learn_press_transaction_order_date( $date, $format = null ) {
	$format = empty( $format ) ? get_option( 'date_format' ) : $format;
	return date( $format, strtotime( $date ) );
}

function learn_press_get_course_order( $course_id, $user_id = null ) {
	if ( !$user_id ) {
		$user_id = get_current_user_id();
	}

	global $wpdb;
	$order = false;
	$query = $wpdb->prepare( "
        SELECT ID, pm2.meta_value
        FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
        WHERE p.post_type = %s AND pm.meta_key = %s AND pm.meta_value = %d
    ", '_learn_press_order_items', LP_ORDER_CPT, '_learn_press_customer_id', $user_id );
	if ( $orders = $wpdb->get_results( $query ) ) {
		foreach ( $orders as $order_data ) {
			$order_id   = $order_data->ID;
			$order_data = maybe_unserialize( $order_data->meta_value );
			if ( $order_data && !empty( $order_data->products ) ) {
				if ( isset( $order_data->products[$course_id] ) ) {
					$order = $order_id;
					// a user only can take a course one time
					// so it should be existing in one and only one order
					break;
				}
			}
		}
	}
	return $order;
}

function learn_press_get_order_status_label( $order_id = 0 ) {
	$statuses = learn_press_get_order_statuses();
	if ( is_numeric( $order_id ) ) {
		$status = get_post_status( $order_id );
	} else {
		$status = $order_id;
	}
	return !empty( $statuses[$status] ) ? $statuses[$status] : __( 'Pending', 'learnpress' );
}

function learn_press_get_order_statuses( $prefix = true ) {
	$prefix         = $prefix ? 'lp-' : '';
	$order_statuses = array(
		$prefix . 'pending'    => _x( 'Pending', 'Order status', 'learnpress' ),
		$prefix . 'processing' => _x( 'Processing', 'Order status', 'learnpress' ),
		$prefix . 'completed'  => _x( 'Completed', 'Order status', 'learnpress' ),
		$prefix . 'on-hold'    => _x( 'On Hold', 'Order status', 'learnpress' ),
		$prefix . 'refunded'   => _x( 'Refunded', 'Order status', 'learnpress' ),
		$prefix . 'failed'     => _x( 'Failed', 'Order status', 'learnpress' ),
		$prefix . 'cancelled'  => _x( 'Cancelled', 'Order status', 'learnpress' )
	);

	return apply_filters( 'learn_press_order_statuses', $order_statuses );
}

function _learn_press_get_order_status_description( $status ) {
	static $descriptions = null;
	$descriptions = array(
		'lp-pending'    => __( 'Order received in case user buy a course but doesn\'t finalise the order.', 'learnpress' ),
		'lp-failed'     => __( 'Payment failed or was declined (unpaid).', 'learnpress' ),
		'lp-processing' => __( 'Payment received and the order is awaiting fulfillment.', 'learnpress' ),
		'lp-completed'  => __( 'Order fulfilled and complete.', 'learnpress' ),
		'lp-on-hold'    => __( 'Awaiting payment.', 'learnpress' ),
		'lp-cancelled'  => __( 'The order is cancelled by an admin or the customer.', 'learnpress' ),
		'lp-refunded'   => __( 'Refunded is to indicate that the refund to the customer has been sent.', 'learnpress' )
	);
	return apply_filters( 'learn_press_order_status_description', !empty( $descriptions[$status] ) ? $descriptions[$status] : '' );
}

function learn_press_get_order_status( $order_id ) {
	$order = learn_press_get_order( $order_id );
	if ( $order ) {
		return $order->get_status();
	}
	return false;
}

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
				if ( $user->has( 'enrolled-course', $item['course_id'] ) ) {
					continue;
				}
				if ( $user->enroll( $item['course_id'] ) ) {
					$enrolled = $item['course_id'];
				}
			}
			if ( !$enrolled ) {
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

if ( !function_exists( '_learn_press_total_raised' ) ) {
    function _learn_press_total_raised() {
        $orders = learn_press_get_orders( array( 'post_status' => 'lp-completed' ) );
        $total = 0;
        if ( $orders ) {
            foreach( $orders as $order ) {
                $order = learn_press_get_order( $order->ID );
                $total = $total + $order->order_total;
            }
        }

        return apply_filters( '_learn_press_total_raised', learn_press_format_price( $total, true ), $total );
    }
}
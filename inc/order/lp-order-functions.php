<?php
/**
 * Defines functions related to order
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Generate unique key for an order.
 *
 * @return mixed
 */
function learn_press_generate_order_key() {
	return apply_filters( 'learn-press/order-key', strtoupper( uniqid( 'ORDER' ) ) );
}

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
 * Count orders by it's status
 *
 * @param array $args
 * @Todo tungnx review to rewrite query
 * @return array
 */
function learn_press_count_orders( $args = array() ) {
	if ( is_string( $args ) ) {
		$args = array( 'status' => $args );
	} else {
		$args = wp_parse_args(
			$args,
			array(
				'status' => '',
			)
		);
	}
	global $wpdb;
	$statuses = $args['status'];

	if ( ! $statuses ) {
		$statuses = array_keys( LP_Order::get_order_statuses() );
	}

	settype( $statuses, 'array' );
	$size_of_status = sizeof( $statuses );

	foreach ( $statuses as $k => $status ) {
		$statuses[ $k ] = ! preg_match( '~^lp-~', $status ) ? 'lp-' . $status : $status;
	}

	$format     = array_fill( 0, $size_of_status, '%s' );
	$counts     = array_fill_keys( $statuses, 0 );
	$statuses[] = LP_ORDER_CPT;
	$query      = $wpdb->prepare(
		"
		SELECT COUNT(ID) AS count, post_status AS status
		FROM {$wpdb->posts} o
		WHERE post_status IN(" . join( ',', $format ) . ')
		AND post_type = %s
		GROUP BY o.post_status
	',
		$statuses
	);

	$results = $wpdb->get_results( $query );
	if ( $results ) {
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
 * @param string $currency
 *
 * @return string
 */
function learn_press_format_price( $price = 0, $currency = '' ): string {
	if ( ! is_numeric( $price ) ) {
		$price = 0;
	}

	$before = $after = '';

	$currency            = esc_html(
		is_string( $currency ) && '' !== $currency
			? $currency
			: learn_press_get_currency_symbol()
	);
	$thousands_separator = esc_html( LP_Settings::get_option( 'thousands_separator', ',' ) );
	$number_of_decimals  = esc_html( LP_Settings::get_option( 'number_of_decimals', 2 ) );
	$decimals_separator  = esc_html( LP_Settings::get_option( 'decimals_separator', '.' ) );

	switch ( LP_Settings::get_option( 'currency_pos' ) ) {
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

	return $before . number_format( $price, $number_of_decimals, $decimals_separator, $thousands_separator ) . $after;
}

/**
 * Update
 *
 * @param $order_id
 *
 * @return array|bool
 */
function learn_press_update_order_items( $order_id ) {
	$order = learn_press_get_order( $order_id );
	if ( ! $order ) {
		return false;
	}

	$subtotal = 0;
	$total    = 0;
	$items    = $order->get_items();

	if ( $items ) {
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
		'currency' => learn_press_get_currency(),
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
 * Get list of registered order's statues for registering with wp post's status.
 *
 * @since 2.0.0
 *
 * @return array
 */
function learn_press_get_register_order_statuses() {
	$order_statues = array();

	$order_statues['lp-completed']  = array(
		'label'                     => _x( 'Completed', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'learnpress' ),
	);
	$order_statues['lp-pending']    = array(
		'label'                     => _x( 'Pending', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'learnpress' ),
	);
	$order_statues['lp-processing'] = array(
		'label'                     => _x( 'Processing', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'learnpress' ),
	);
	$order_statues['lp-cancelled']  = array(
		'label'                     => _x( 'Cancelled', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'learnpress' ),
	);
	$order_statues['lp-failed']     = array(
		'label'                     => _x( 'Failed', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'learnpress' ),
	);
	$order_statues['trash']         = array(
		'label'                     => _x( 'Trash', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Trash <span class="count">(%s)</span>', 'Trash <span class="count">(%s)</span>', 'learnpress' ),
	);

	return $order_statues;
}

function _learn_press_get_order_status_description( $status ) {
	$descriptions = array(
		'pending'    => __( 'Order received in case a user purchases a course but doesn\'t finalize the order.', 'learnpress' ),
		'processing' => __( 'Payment received and the order is awaiting fulfillment.', 'learnpress' ),
		'completed'  => __( 'The order is fulfilled and completed.', 'learnpress' ),
		'cancelled'  => __( 'The order is cancelled by an admin or the customer.', 'learnpress' ),
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
		if ( empty( $_REQUEST['cancel-order'] ) || empty( $_REQUEST['lp-nonce'] ) ||
			! wp_verify_nonce( $_REQUEST['lp-nonce'], 'cancel-order' ) || is_admin() ) {
			return;
		}

		$user = learn_press_get_current_user();
		$url  = learn_press_user_profile_link(
			$user->get_id(),
			LP_Settings::instance()->get( 'profile_endpoints.orders', 'orders' )
		);

		try {
			$message = [
				'status'  => 'error',
				'content' => '',
			];

			$order_id = absint( $_REQUEST['cancel-order'] );
			$order    = learn_press_get_order( $order_id );

			if ( ! $order ) {
				throw new Exception( sprintf( __( 'Order number <strong>%s</strong> not found', 'learnpress' ), $order_id ) );
			}

			$user_ids = (array) $order->get_user_id();
			if ( ! in_array( $user->get_id(), $user_ids ) ) {
				throw new Exception( __( 'You do not have permission to cancel this order.', 'learnpress' ) );
			}

			if ( $order->has_status( LP_ORDER_PENDING ) ) {
				$order->update_status( LP_ORDER_CANCELLED );
				$order->add_note( __( 'The order is cancelled by the customer', 'learnpress' ) );

				$message['status']  = 'success';
				$message['content'] = sprintf( __( 'Order number <strong>%s</strong> has been cancelled', 'learnpress' ), $order->get_order_number() );
			} else {
				throw new Exception(
					__( 'The order number <strong>%s</strong> can not be cancelled.', 'learnpress' ),
					$order->get_order_number()
				);
			}
		} catch ( Throwable $e ) {
			$message['content'] = $e->getMessage();
		}

		learn_press_set_message( $message );
		wp_safe_redirect( $url );
		exit();
	}
}
add_action( 'init', 'learn_press_cancel_order_process' );


/**
 * get total price order complete
 *
 */

function learn_press_get_total_price_order_complete() {
	global $wpdb;

	$query = $wpdb->prepare(
		"SELECT SUM(meta_value) as order_total From `{$wpdb->prefix}postmeta` as mt
		INNER JOIN `{$wpdb->prefix}posts` as p ON p.id = mt.post_id
		WHERE p.post_type = %s AND mt.meta_key = %s
		AND p.post_status = %s
		",
		LP_ORDER_CPT,
		'_order_total',
		'lp-completed'
	);

	$total = $wpdb->get_results( $query )[0]->order_total;

	return learn_press_format_price( $total, true );
}

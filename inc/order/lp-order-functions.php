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
 * @param float  $price
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
	$order_statues['lp-refunded']   = array(
		'label'                     => _x( 'Refunded', 'Order status', 'learnpress' ),
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
		'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'learnpress' ),
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

	$status       = str_replace( 'lp-', '', (string) $status );
	$descriptions = array(
		'pending'    => __( 'Order received in case a user purchases a course but doesn\'t finalize the order.', 'learnpress' ),
		'processing' => __( 'Payment received and the order is awaiting fulfillment.', 'learnpress' ),
		'completed'  => __( 'The order is fulfilled and completed.', 'learnpress' ),
		'cancelled'  => __( 'The order is cancelled by an admin or the customer.', 'learnpress' ),
		'refunded'   => __( 'Order was refunded to the customer.', 'learnpress' ),
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

if ( ! function_exists( 'learn_press_get_profile_orders_redirect_url' ) ) {
	/**
	 * Build profile orders URL.
	 *
	 * @param int $user_id
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 * @return string
	 */
	function learn_press_get_profile_orders_redirect_url( int $user_id = 0 ): string {

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		return learn_press_user_profile_link(
			$user_id,
			LP_Settings::instance()->get( 'profile_endpoints.orders', 'orders' )
		);
	}
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
		$url  = learn_press_get_profile_orders_redirect_url( $user->get_id() );
		try {
			$message = array(
				'status'  => 'error',
				'content' => '',
			);

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
if ( ! function_exists( 'learn_press_execute_order_refund' ) ) {
	/**
	 * Execute full refund by payment gateway.
	 *
	 * @param LP_Order $order
	 * @param array    $context
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 * @return array
	 * @throws Exception
	 */
	function learn_press_execute_order_refund( LP_Order $order, array $context = array() ): array {

		$context = wp_parse_args(
			$context,
			array(
				'actor_id'       => get_current_user_id(),
				'actor_type'     => 'system',
				'request_status' => '',
				'requested_by'   => 0,
				'requested_at'   => '',
				'reviewed_by'    => 0,
				'auto_approved'  => false,
				'note'           => '',
			)
		);

		if ( ! $order->has_status( LP_ORDER_COMPLETED ) ) {
			throw new Exception( __( 'Only completed orders can be refunded.', 'learnpress' ) );
		}

		$order_id       = $order->get_id();
		$payment_method = strtolower( (string) $order->get_data( 'payment_method', '' ) );
		if ( ! in_array( $payment_method, array( 'stripe', 'paypal' ), true ) ) {
			throw new Exception( __( 'This payment gateway does not support refunds.', 'learnpress' ) );
		}

		$gateway = LP_Gateways::instance()->get_gateway( $payment_method );
		if ( ! $gateway || ! is_callable( array( $gateway, 'refund' ) ) ) {
			throw new Exception( __( 'Refund gateway is unavailable.', 'learnpress' ) );
		}

		$refund_result = $gateway->refund( $order_id );
		if ( is_wp_error( $refund_result ) ) {
			throw new Exception( $refund_result->get_error_message() );
		}

		if ( is_array( $refund_result ) && ! empty( $refund_result['result'] ) ) {
			$result_status = strtolower( (string) $refund_result['result'] );
			if ( ! in_array( $result_status, array( 'success', 'completed', 'succeeded' ), true ) ) {
				throw new Exception( __( 'Refund gateway returned a failed response.', 'learnpress' ) );
			}
		}

		if ( ! $order->update_status( LP_ORDER_REFUNDED ) ) {
			throw new Exception( __( 'Could not update order status to refunded.', 'learnpress' ) );
		}

		$request_status = $context['request_status'];
		if ( empty( $request_status ) ) {
			$request_status = ! empty( $context['auto_approved'] ) ? 'auto-approved' : 'approved';
		}
		update_post_meta( $order_id, '_lp_refund_request_status', sanitize_key( $request_status ) );

		$requested_by = absint( $context['requested_by'] );
		if ( ! empty( $requested_by ) ) {
			update_post_meta( $order_id, '_lp_refund_requested_by', $requested_by );
		}

		$requested_at = (string) $context['requested_at'];
		if ( ! empty( $requested_at ) ) {
			update_post_meta( $order_id, '_lp_refund_requested_at', $requested_at );
		}

		$reviewed_by = absint( $context['reviewed_by'] );
		if ( ! empty( $reviewed_by ) ) {
			update_post_meta( $order_id, '_lp_refund_reviewed_by', $reviewed_by );
		}
		update_post_meta( $order_id, '_lp_refund_reviewed_at', current_time( 'mysql' ) );

		$refund_note = trim( (string) $context['note'] );
		if ( ! empty( $refund_note ) ) {
			update_post_meta( $order_id, '_lp_refund_note', sanitize_textarea_field( $refund_note ) );
		}

		$actor_id   = absint( $context['actor_id'] );
		$actor_name = __( 'System', 'learnpress' );
		if ( ! empty( $actor_id ) ) {
			$user = get_user_by( 'id', $actor_id );
			if ( $user instanceof WP_User ) {
				$actor_name = $user->display_name;
			}
		}

		$order_note = sprintf(
			__( 'Refund completed via %1$s by %2$s.', 'learnpress' ),
			$payment_method,
			$actor_name
		);

		if ( is_array( $refund_result ) ) {
			$refund_id     = $refund_result['refund_id'] ?? '';
			$refund_status = $refund_result['status'] ?? '';
			if ( ! empty( $refund_id ) ) {
				$order_note .= ' ' . sprintf( __( 'Refund ID: %s.', 'learnpress' ), $refund_id );
			}
			if ( ! empty( $refund_status ) ) {
				$order_note .= ' ' . sprintf( __( 'Gateway status: %s.', 'learnpress' ), $refund_status );
			}
		}

		$order->add_note( $order_note );

		return is_array( $refund_result ) ? $refund_result : array(
			'result' => 'success',
		);
	}
}

if ( ! function_exists( 'learn_press_refund_order_process' ) ) {
	/**
	 * Process user refund request from profile orders.
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 */
	function learn_press_refund_order_process() {

		if ( empty( $_REQUEST['refund-order'] ) || is_admin() ) {
			return;
		}

		$user = learn_press_get_current_user();
		$url  = learn_press_get_profile_orders_redirect_url( $user->get_id() );

		try {
			$message = array(
				'status'  => 'error',
				'content' => '',
			);

			$nonce = $_REQUEST['lp-nonce'] ?? '';
			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'refund-order' ) ) {
				throw new Exception( __( 'Invalid refund request.', 'learnpress' ) );
			}

			$order_id = absint( $_REQUEST['refund-order'] );
			$order    = learn_press_get_order( $order_id );

			if ( ! $order ) {
				throw new Exception( sprintf( __( 'Order number <strong>%s</strong> not found', 'learnpress' ), $order_id ) );
			}

			$user_ids = (array) $order->get_user_id();
			if ( ! in_array( $user->get_id(), $user_ids ) ) {
				throw new Exception( __( 'You do not have permission to refund this order.', 'learnpress' ) );
			}

			if ( ! $order->has_status( LP_ORDER_COMPLETED ) ) {
				throw new Exception( __( 'Only completed orders can be refunded.', 'learnpress' ) );
			}

			$payment_method = strtolower( (string) $order->get_data( 'payment_method', '' ) );
			if ( ! in_array( $payment_method, array( 'stripe', 'paypal' ), true ) ) {
				throw new Exception( __( 'This payment gateway does not support refunds.', 'learnpress' ) );
			}

			$refund_request_status = get_post_meta( $order_id, '_lp_refund_request_status', true );
			if ( 'pending' === $refund_request_status ) {
				throw new Exception( __( 'A refund request is already pending for this order.', 'learnpress' ) );
			}

			$request_user_id = $user->get_id();
			$request_time    = current_time( 'mysql' );
			update_post_meta( $order_id, '_lp_refund_requested_by', $request_user_id );
			update_post_meta( $order_id, '_lp_refund_requested_at', $request_time );

			$auto_refund = LP_Settings::get_option( 'auto_refund', 'no' ) === 'yes';
			if ( $auto_refund ) {
				learn_press_execute_order_refund(
					$order,
					array(
						'actor_id'       => $request_user_id,
						'actor_type'     => 'customer',
						'request_status' => 'auto-approved',
						'requested_by'   => $request_user_id,
						'requested_at'   => $request_time,
						'auto_approved'  => true,
					)
				);

				$message['status']  = 'success';
				$message['content'] = sprintf( __( 'Order number <strong>%s</strong> has been refunded.', 'learnpress' ), $order->get_order_number() );
			} else {
				update_post_meta( $order_id, '_lp_refund_request_status', 'pending' );
				delete_post_meta( $order_id, '_lp_refund_reviewed_by' );
				delete_post_meta( $order_id, '_lp_refund_reviewed_at' );

				$order->add_note(
					sprintf(
						__( 'Refund request submitted by customer #%d and waiting for admin review.', 'learnpress' ),
						$request_user_id
					)
				);
				do_action( 'learn-press/order/refund-requested', $order_id, $request_user_id );

				$message['status']  = 'success';
				$message['content'] = __( 'Your refund request has been sent to the admin.', 'learnpress' );
			}
		} catch ( Throwable $e ) {
				$message['content'] = $e->getMessage();
			if ( ! empty( $order ) && $order instanceof LP_Order ) {
				$order->add_note( sprintf( __( 'Refund request failed: %s', 'learnpress' ), $e->getMessage() ) );
			}
		}

		learn_press_set_message( $message );
		wp_safe_redirect( $url );
		exit();
	}
}
add_action( 'init', 'learn_press_refund_order_process' );
if ( ! function_exists( 'learn_press_get_admin_order_edit_url' ) ) {
	/**
	 * Build order edit URL for admin.
	 *
	 * @param int $order_id
	 *
	 * @since 4.3.4
	 
	 * @return string
	 */
	function learn_press_get_admin_order_edit_url( int $order_id ): string {

		return add_query_arg(
			array(
				'post'   => $order_id,
				'action' => 'edit',
			),
			admin_url( 'post.php' )
		);
	}
}

if ( ! function_exists( 'learn_press_admin_refund_order_process' ) ) {
	/**
	 * Process admin approve/deny refund requests.
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 */
	function learn_press_admin_refund_order_process() {

		if ( empty( $_GET['lp-refund-order'] ) || empty( $_GET['lp-refund-action'] ) ) {
			return;
		}

		$order_id = absint( $_GET['lp-refund-order'] );
		$action   = sanitize_key( wp_unslash( $_GET['lp-refund-action'] ) );
		$nonce    = isset( $_GET['lp-refund-nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['lp-refund-nonce'] ) ) : '';

		$redirect_url = learn_press_get_admin_order_edit_url( $order_id );
		$notice_args  = array();

		try {
			if ( ! wp_verify_nonce( $nonce, 'lp-admin-refund-order-' . $order_id ) ) {
				throw new Exception( __( 'Invalid refund request nonce.', 'learnpress' ) );
			}

			if ( ! current_user_can( 'edit_post', $order_id ) ) {
				throw new Exception( __( 'You do not have permission to review this refund request.', 'learnpress' ) );
			}

			$order = learn_press_get_order( $order_id );
			if ( ! $order ) {
				throw new Exception( __( 'Order not found.', 'learnpress' ) );
			}

			$request_status = get_post_meta( $order_id, '_lp_refund_request_status', true );
			if ( 'pending' !== $request_status ) {
				throw new Exception( __( 'This order has no pending refund request.', 'learnpress' ) );
			}

			$admin_id = get_current_user_id();
			if ( 'approve' === $action ) {
				learn_press_execute_order_refund(
					$order,
					array(
						'actor_id'       => $admin_id,
						'actor_type'     => 'admin',
						'request_status' => 'approved',
						'reviewed_by'    => $admin_id,
					)
				);

				$notice_args = array(
					'lp-refund-admin' => 'approved',
				);
			} elseif ( 'deny' === $action ) {
				update_post_meta( $order_id, '_lp_refund_request_status', 'denied' );
				update_post_meta( $order_id, '_lp_refund_reviewed_by', $admin_id );
				update_post_meta( $order_id, '_lp_refund_reviewed_at', current_time( 'mysql' ) );

				$order->add_note(
					sprintf(
						__( 'Refund request denied by admin #%d.', 'learnpress' ),
						$admin_id
					)
				);

				$notice_args = array(
					'lp-refund-admin' => 'denied',
				);
			} else {
				throw new Exception( __( 'Invalid refund review action.', 'learnpress' ) );
			}
		} catch ( Throwable $e ) {
			if ( ! empty( $order ) && $order instanceof LP_Order && 'approve' === $action ) {
				$order->add_note( sprintf( __( 'Refund approval failed: %s', 'learnpress' ), $e->getMessage() ) );
			}

			$notice_args = array(
				'lp-refund-admin'   => 'error',
				'lp-refund-message' => $e->getMessage(),
			);
		}

		wp_safe_redirect( add_query_arg( $notice_args, $redirect_url ) );
		exit();
	}
}
add_action( 'admin_init', 'learn_press_admin_refund_order_process' );
if ( ! function_exists( 'learn_press_admin_refund_order_notices' ) ) {
	/**
	 * Show admin notices after approve/deny refund actions.
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 */
	function learn_press_admin_refund_order_notices() {

		if ( empty( $_GET['lp-refund-admin'] ) ) {
			return;
		}

		$notice  = sanitize_key( wp_unslash( $_GET['lp-refund-admin'] ) );
		$type    = 'success';
		$message = '';

		switch ( $notice ) {
			case 'approved':
				$message = __( 'Refund approved successfully.', 'learnpress' );
				break;
			case 'denied':
				$message = __( 'Refund request denied.', 'learnpress' );
				break;
			case 'error':
				$type    = 'error';
				$message = isset( $_GET['lp-refund-message'] ) ? sanitize_text_field( wp_unslash( $_GET['lp-refund-message'] ) ) : __( 'Could not process refund request.', 'learnpress' );
				break;
		}

		if ( empty( $message ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}
}
add_action( 'admin_notices', 'learn_press_admin_refund_order_notices' );
if ( ! function_exists( 'learn_press_admin_order_refund_request_panel' ) ) {
	/**
	 * Render pending refund request panel on order detail.
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 * @param LP_Order $order
	 */
	function learn_press_admin_order_refund_request_panel( $order ) {

		if ( ! $order instanceof LP_Order ) {
			return;
		}

		$order_id = $order->get_id();
		if ( ! current_user_can( 'edit_post', $order_id ) ) {
			return;
		}

		$refund_request_status = get_post_meta( $order_id, '_lp_refund_request_status', true );
		if ( 'pending' !== $refund_request_status ) {
			return;
		}

		$requested_by = absint( get_post_meta( $order_id, '_lp_refund_requested_by', true ) );
		$requested_at = get_post_meta( $order_id, '_lp_refund_requested_at', true );

		$requester = __( 'Unknown', 'learnpress' );
		if ( ! empty( $requested_by ) ) {
			$user = get_user_by( 'id', $requested_by );
			if ( $user instanceof WP_User ) {
				$requester = sprintf( '%s (#%d)', $user->display_name, $requested_by );
			}
		}

		$requested_time = __( 'Unknown time', 'learnpress' );
		if ( ! empty( $requested_at ) ) {
			$timestamp = strtotime( $requested_at );
			if ( $timestamp ) {
				$requested_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
			}
		}

		$approve_url = wp_nonce_url(
			add_query_arg(
				array(
					'lp-refund-order'  => $order_id,
					'lp-refund-action' => 'approve',
				),
				learn_press_get_admin_order_edit_url( $order_id )
			),
			'lp-admin-refund-order-' . $order_id,
			'lp-refund-nonce'
		);

		$deny_url = wp_nonce_url(
			add_query_arg(
				array(
					'lp-refund-order'  => $order_id,
					'lp-refund-action' => 'deny',
				),
				learn_press_get_admin_order_edit_url( $order_id )
			),
			'lp-admin-refund-order-' . $order_id,
			'lp-refund-nonce'
		);
		?>
		<div class="order-data-field order-data-refund-request">
			<label><?php esc_html_e( 'Refund Request', 'learnpress' ); ?></label>
			<p class="description">
				<?php
				echo esc_html(
					sprintf(
						__( 'Requested by %1$s at %2$s.', 'learnpress' ),
						$requester,
						$requested_time
					)
				);
				?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( $approve_url ); ?>">
					<?php esc_html_e( 'Approve Refund', 'learnpress' ); ?>
				</a>
				<a class="button" href="<?php echo esc_url( $deny_url ); ?>">
					<?php esc_html_e( 'Deny Refund', 'learnpress' ); ?>
				</a>
			</p>
		</div>
		
		<?php
	}
}
add_action( 'lp/admin/order/detail/after-order-key', 'learn_press_admin_order_refund_request_panel' );

	/**
	 * get total price order complete
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

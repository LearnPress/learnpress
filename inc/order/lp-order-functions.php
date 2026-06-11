<?php
/**
 * Defines functions related to order
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */
use LearnPress\Models\UserItems\UserCourseModel;
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

if ( ! function_exists( 'learn_press_get_refund_setting' ) ) {
	/**
	 * Get refund setting value with safe defaults.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 * @return mixed
	 */
	function learn_press_get_refund_setting( string $key, $default = null ) {
		$defaults = array(
			'enable_refund_requests'      => 'no',
			'auto_refund'                 => 'no',
			'refund_time_limit'           => 30,
			'require_refund_reason'       => 'no',
			'allow_resend_after_rejected' => 'no',
			'refund_max_completion'       => 0,
		);

		if ( null === $default && array_key_exists( $key, $defaults ) ) {
			$default = $defaults[ $key ];
		}

		return LP_Settings::get_option( $key, $default );
	}
}

if ( ! function_exists( 'learn_press_get_order_refund_supported_gateways' ) ) {
	/**
	 * Get list gateways that support refund.
	 *
	 * @since 4.3.4
	 * @version 1.1.0
	 * @return array
	 */
	function learn_press_get_order_refund_supported_gateways(): array {
		if ( ! class_exists( 'LP_Gateways' ) ) {
			return array();
		}

		$gateways_instance = LP_Gateways::instance();
		if ( ! $gateways_instance || ! method_exists( $gateways_instance, 'get_gateways' ) ) {
			return array();
		}

		$supported_gateways = array();
		$all_gateways       = (array) $gateways_instance->get_gateways();
		foreach ( $all_gateways as $gateway_id => $gateway ) {
			$gateway_id = sanitize_key( (string) $gateway_id );
			if ( empty( $gateway_id ) ) {
				continue;
			}

			if ( ! is_object( $gateway ) || ! is_callable( array( $gateway, 'refund' ) ) ) {
				continue;
			}

			$supported_gateways[] = $gateway_id;
		}

		return array_values( array_unique( $supported_gateways ) );
	}
}

if ( ! function_exists( 'learn_press_get_order_refund_eligibility' ) ) {
	/**
	 * Get unified completion data and policy for refund.
	 * Policy uses max course completion percent of courses in the order.
	 *
	 * Returns both raw progress data and policy gate fields (max_completion,
	 * is_limited, exceeded) so callers don't need a separate policy helper.
	 *
	 * @since 4.3.5
	 * @version 1.1.0
	 *
	 * @param LP_Order $order
	 * @param int      $user_id
	 *
	 * @return array{
	 *     user_id: int,
	 *     completion_percent: float,
	 *     course_progress: array<int,float>,
	 *     has_course_progress: bool,
	 *     max_completion: float,
	 *     is_limited: bool,
	 *     exceeded: bool
	 * }
	 */
	function learn_press_get_order_refund_completion_data( LP_Order $order, int $user_id = 0 ): array {
		$max_completion = max( 0, min( 100, floatval( learn_press_get_refund_setting( 'refund_max_completion', 0 ) ) ) );

		$data = array(
			'user_id'             => $user_id,
			'completion_percent'  => 0.0,
			'course_progress'     => array(),
			'has_course_progress' => false,
			'max_completion'      => $max_completion,
			'is_limited'          => $max_completion > 0,
			'exceeded'            => false,
		);

		if ( $user_id <= 0 ) {
			return $data;
		}

		$course_ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', (array) $order->get_item_ids() )
				)
			)
		);

		$max_progress = 0.0;
		foreach ( $course_ids as $course_id ) {
			$progress = 0.0;

			$user_course_model = UserCourseModel::find( $user_id, $course_id, true );
			if ( $user_course_model instanceof UserCourseModel ) {
				$course_results = $user_course_model->calculate_course_results( true );
				if ( is_array( $course_results ) && isset( $course_results['result'] ) ) {
					$progress = floatval( $course_results['result'] );
				}
			}

			$progress                              = max( 0, min( 100, $progress ) );
			$data['course_progress'][ $course_id ] = $progress;
			$data['has_course_progress']           = true;
			if ( $progress > $max_progress ) {
				$max_progress = $progress;
			}
		}

		$completion_percent         = round( $max_progress, 2 );
		$data['completion_percent'] = $completion_percent;
		$data['exceeded']           = $max_completion > 0 && $completion_percent > $max_completion;

		$filtered_data = apply_filters( 'learn-press/order/refund/completion-data', $data, $order, $user_id );
		if ( ! is_array( $filtered_data ) ) {
			return $data;
		}

		return wp_parse_args( $filtered_data, $data );
	}


	/**
	 * Check if an order is eligible for user refund request.
	 *
	 * @param LP_Order $order
	 * @param int      $user_id
	 *
	 * @since 4.3.4
	 * @version 1.0.0
	 * @return array
	 */
	function learn_press_get_order_refund_eligibility( LP_Order $order, int $user_id = 0 ): array {
		$result = array(
			'eligible'       => false,
			'code'           => '',
			'message'        => '',
			'require_reason' => learn_press_get_refund_setting( 'require_refund_reason', 'no' ) === 'yes',
			'reason_min'     => 10,
		);

		if ( 'yes' !== learn_press_get_refund_setting( 'enable_refund_requests', 'no' ) ) {
			$result['code'] = 'refund_disabled';
			return $result;
		}

		if ( $order->is_guest() ) {
			$result['code'] = 'guest_not_supported';
			return $result;
		}

		if ( $order->has_status( LP_ORDER_REFUNDED ) ) {
			$result['code'] = 'already_refunded';
			return $result;
		}

		// Defense-in-depth: request-time gate; execution gate is re-checked later before calling gateway.
		if ( ! $order->has_status( LP_ORDER_COMPLETED ) ) {
			$result['code'] = 'invalid_status';
			return $result;
		}

		$payment_method = strtolower( (string) $order->get_data( 'payment_method', '' ) );
		$gateways       = learn_press_get_order_refund_supported_gateways();
		if ( ! in_array( $payment_method, $gateways, true ) ) {
			$result['code'] = 'unsupported_gateway';
			return $result;
		}

		$refund_request_status = $order->get_refund_request();
		if ( 'pending' === $refund_request_status ) {
			$result['code'] = 'pending_request';
			return $result;
		}

		if ( 'rejected' === $refund_request_status
			&& 'yes' !== learn_press_get_refund_setting( 'allow_resend_after_rejected', 'no' ) ) {
			$result['code'] = 'rerequest_not_allowed';
			return $result;
		}

		// Defense-in-depth: ownership is validated here for early feedback and again in execution for runtime safety.
		if ( $user_id > 0 ) {
			$user_ids = array_map( 'absint', (array) $order->get_user_id() );
			if ( ! in_array( $user_id, $user_ids, true ) ) {
				$result['code'] = 'invalid_owner';
				return $result;
			}
		}

		$refund_time_limit = absint( learn_press_get_refund_setting( 'refund_time_limit', 30 ) );
		if ( $refund_time_limit > 0 ) {
			$order_time = absint( $order->get_order_date( 'timestamp' ) );
			if ( empty( $order_time ) ) {
				$order_time = absint( get_post_time( 'U', true, $order->get_id() ) );
			}

			if ( $order_time > 0 ) {
				$now                = current_time( 'timestamp' );
				$elapsed_seconds    = max( 0, $now - $order_time );
				$allowed_time_limit = $refund_time_limit * DAY_IN_SECONDS;
				if ( $elapsed_seconds > $allowed_time_limit ) {
					$result['code'] = 'time_limit_exceeded';
					return $result;
				}
			}
		}

		$completion_data = learn_press_get_order_refund_completion_data( $order, $user_id );
		if ( $user_id > 0 && ! empty( $completion_data['is_limited'] ) && ! empty( $completion_data['exceeded'] ) ) {
			$result['code'] = 'completion_exceeded';
			return $result;
		}

		$result['eligible'] = true;
		$result['code']     = 'ok';
		$result['message']  = '';
		$filtered_result    = apply_filters( 'learn-press/order/refund/eligible', $result, $order, $user_id );
		if ( ! is_array( $filtered_result ) ) {
			return $result;
		}

		return wp_parse_args( $filtered_result, $result );
	}
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

		$user_id = get_current_user_id();
		$profile = LP_Profile::instance( $user_id );
		$url     = $profile->get_tab_link(
			LP_Settings::instance()->get( 'profile_endpoints.orders', 'orders' )
		);

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
			if ( ! in_array( $user_id, $user_ids ) ) {
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
if ( ! function_exists( 'learn_press_get_order_refund_event_data' ) ) {
	/**
	 * Build normalized refund event payload.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param LP_Order $order
	 * @param array    $overrides
	 *
	 * @return array
	 */
	function learn_press_get_order_refund_event_data( LP_Order $order, array $overrides = array() ): array {
		$order_id = $order->get_id();

		$data = array(
			'order_id'             => $order_id,
			'order_number'         => $order->get_order_number(),
			'order_key'            => $order->get_order_key(),
			'order_status'         => $order->get_status(),
			'request_status'       => $order->get_refund_request(),
			'requested_by'         => $order->get_user_id(),
			'requested_at'         => (string) get_post_meta( $order_id, '_lp_refund_requested_at', true ),
			'reviewed_by'          => absint( get_post_meta( $order_id, '_lp_refund_reviewed_by', true ) ),
			'reviewed_at'          => (string) get_post_meta( $order_id, '_lp_refund_reviewed_at', true ),
			'reason'               => (string) get_post_meta( $order_id, '_lp_refund_reason', true ),
			'requester_email'      => '',
			'admin_order_edit_url' => add_query_arg(
				array(
					'post'   => $order_id,
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			),
		);

		$requested_user_id = absint( $data['requested_by'] );
		if ( ! empty( $requested_user_id ) ) {
			$requested_user = get_user_by( 'id', $requested_user_id );
			if ( $requested_user instanceof WP_User ) {
				$data['requester_email'] = $requested_user->user_email;
			}
		}

		if ( ! empty( $overrides ) ) {
			$data = array_merge( $data, $overrides );
		}

		return $data;
	}
}
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

	$refund_event_data     = learn_press_get_order_refund_event_data( $order );
	$refund_request_status = sanitize_key( (string) ( $refund_event_data['request_status'] ?? '' ) );
	$requested_by          = absint( $refund_event_data['requested_by'] ?? 0 );
	$requested_at          = (string) ( $refund_event_data['requested_at'] ?? '' );
	$refund_reason         = trim( (string) ( $refund_event_data['reason'] ?? '' ) );
	$requester_email       = '';

	if ( empty( $refund_request_status ) && empty( $requested_by ) && empty( $requested_at ) && empty( $refund_reason ) ) {
		return;
	}

	$status_labels = array(
		'pending'       => __( 'Pending review', 'learnpress' ),
		'approved'      => __( 'Approved', 'learnpress' ),
		'auto-approved' => __( 'Auto approved', 'learnpress' ),
		'rejected'      => __( 'Rejected', 'learnpress' ),
	);
	$status_label  = $status_labels[ $refund_request_status ] ?? '';
	if ( empty( $status_label ) && ! empty( $refund_request_status ) ) {
		$status_label = ucwords( str_replace( '-', ' ', $refund_request_status ) );
	}

	$requester = __( 'Unknown', 'learnpress' );
	if ( ! empty( $requested_by ) ) {
		$user = get_user_by( 'id', $requested_by );
		if ( $user instanceof WP_User ) {
			$requester       = sprintf( '%s (#%d)', $user->display_name, $requested_by );
			$requester_email = $user->user_email;
		}
	}

	$requested_time = __( 'Unknown time', 'learnpress' );
	if ( ! empty( $requested_at ) ) {
		$timestamp = strtotime( $requested_at );
		if ( $timestamp ) {
			$requested_time = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
		}
	}

	$render_statuses = array( 'pending', 'approved', 'auto-approved' );
	if ( ! in_array( $refund_request_status, $render_statuses, true ) ) {
		return;
	}

	$is_pending      = ( 'pending' === $refund_request_status );
	$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() );

	$order_total           = round( max( 0, floatval( $order->get_total() ) ), 2 );
	$order_total_formatted = learn_press_format_price( $order_total, $currency_symbol );

	// Refund amount is only meaningful once the refund has been executed
	// (approved/auto-approved), when _lp_refund_amount has been written.
	$refund_amount_formatted = '';
	if ( ! $is_pending ) {
		$refund_amount = get_post_meta( $order_id, '_lp_refund_amount', true );
		if ( '' === $refund_amount || null === $refund_amount ) {
			$refund_amount = $order_total; // Fallback for full refunds without a stored amount.
		}
		$refund_amount           = round( max( 0, floatval( $refund_amount ) ), 2 );
		$refund_amount_formatted = learn_press_format_price( $refund_amount, $currency_symbol );
	}

	learn_press_admin_view(
		'meta-boxes/order/refund-request-panel',
		array(
			'order_id'                => $order_id,
			'is_pending'              => $is_pending,
			'order_total'             => $order_total,
			'order_total_formatted'   => $order_total_formatted,
			'refund_amount_formatted' => $refund_amount_formatted,
			'requester'               => $requester,
			'requested_time'          => $requested_time,
			'requester_email'         => $requester_email,
			'refund_reason'           => $refund_reason,
			'status_label'            => $status_label,
		)
	);
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

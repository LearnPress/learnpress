<?php
/**
 * class RefundOrderAjax
 *
 * @since 4.3.5
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use LP_Helper;
use LP_Order;
use LP_Gateways;
use LP_User;
use LP_Request;
use LP_REST_Response;
use WP_User;
use Exception;
use Throwable;

class RefundOrderAjax extends AbstractAjax {
	/**
	 * Process admin approve/deny refund request via admin action URL.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public static function admin_refund_order_process() {
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
				self::execute_order_refund(
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

				$deny_event_data = learn_press_get_order_refund_event_data(
					$order,
					array(
						'request_status' => 'denied',
						'reviewed_by'    => $admin_id,
						'order_status'   => $order->get_status(),
						'actor_id'       => $admin_id,
						'actor_type'     => 'admin',
					)
				);
				do_action( 'learn-press/order/refund-denied', $order_id, $admin_id, $deny_event_data );

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

	/**
	 * Customer request refund order from profile page.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function request_refund_order() {
		$response = new LP_REST_Response();
		$order_id = 0;

		try {
			$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			if ( ! is_array( $params ) ) {
				throw new Exception( __( 'Invalid refund request.', 'learnpress' ) );
			}

			$order_id = absint( $params['order_id'] ?? 0 );
			$reason   = sanitize_textarea_field( (string) ( $params['reason'] ?? '' ) );

			$result            = $this->process_refund_order( $order_id, $reason );
			$response->status  = 'success';
			$response->message = $result['message'];
			$response->data    = $result['data'];
		} catch ( Throwable $e ) {
			$response->status  = 'error';
			$response->message = $e->getMessage();
			$response->data    = array(
				'order_id'       => $order_id,
				'request_status' => '',
				'order_status'   => '',
				'redirect'       => learn_press_get_profile_orders_redirect_url(),
			);

			if ( ! empty( $order_id ) ) {
				$order = learn_press_get_order( $order_id );
				if ( $order ) {
					$order->add_note( sprintf( __( 'Refund request failed: %s', 'learnpress' ), $e->getMessage() ) );
				}
			}
		}

		wp_send_json( $response );
	}

	/**
	 * Process customer refund request.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param int    $order_id
	 * @param string $reason
	 *
	 * @return array
	 * @throws Exception
	 */
	private function process_refund_order( int $order_id, string $reason ): array {
		if ( is_admin() ) {
			throw new Exception( __( 'Invalid refund request.', 'learnpress' ) );
		}

		if ( 'yes' !== learn_press_get_refund_setting( 'enable_refund_requests', 'no' ) ) {
			throw new Exception( __( 'Refund requests are currently disabled.', 'learnpress' ) );
		}

		if ( empty( $order_id ) ) {
			throw new Exception( __( 'Invalid refund request.', 'learnpress' ) );
		}

		$order = learn_press_get_order( $order_id );
		if ( ! $order ) {
			throw new Exception( sprintf( __( 'Order #%s not found.', 'learnpress' ), $order_id ) );
		}

		$user = learn_press_get_current_user();
		if ( ! is_user_logged_in() || ! $user instanceof LP_User || $user->get_id() <= 0 ) {
			throw new Exception( __( 'Guest account cannot request refunds.', 'learnpress' ) );
		}

		$eligibility = learn_press_get_order_refund_eligibility( $order, $user->get_id() );
		if ( empty( $eligibility['eligible'] ) ) {
			$eligibility_code    = sanitize_key( (string) ( $eligibility['code'] ?? '' ) );
			$eligibility_message = (string) ( $eligibility['message'] ?? '' );
			if ( empty( $eligibility_message ) ) {
				$eligibility_messages = array(
					'refund_disabled'       => __( 'Refund requests are currently disabled.', 'learnpress' ),
					'guest_not_supported'   => __( 'Guest account cannot request refunds.', 'learnpress' ),
					'invalid_status'        => __( 'Only completed orders can be refunded.', 'learnpress' ),
					'unsupported_gateway'   => __( 'This payment gateway does not support refunds.', 'learnpress' ),
					'pending_request'       => __( 'A refund request is already pending for this order.', 'learnpress' ),
					'already_refunded'      => __( 'This order has already been refunded.', 'learnpress' ),
					'rerequest_not_allowed' => __( 'You cannot submit another refund request for this order.', 'learnpress' ),
					'invalid_owner'         => __( 'You do not have permission to refund this order.', 'learnpress' ),
					'time_limit_exceeded'   => __( 'This order is outside the refund period.', 'learnpress' ),
					'completion_exceeded'   => __( 'Course completion exceeds the refund limit.', 'learnpress' ),
				);
				$eligibility_message  = $eligibility_messages[ $eligibility_code ] ?? __( 'This order is not eligible for refund.', 'learnpress' );
			}

			throw new Exception( $eligibility_message );
		}

		$previous_request_status = sanitize_key( (string) get_post_meta( $order_id, '_lp_refund_request_status', true ) );

		$require_reason = ! empty( $eligibility['require_reason'] );
		$reason_min     = absint( $eligibility['reason_min'] ?? 10 );
		if ( $require_reason ) {
			if ( empty( $reason ) ) {
				throw new Exception( __( 'Refund reason is required.', 'learnpress' ) );
			}

			$reason_length = function_exists( 'mb_strlen' ) ? mb_strlen( $reason ) : strlen( $reason );
			if ( $reason_length < $reason_min ) {
				throw new Exception(
					sprintf(
						__( 'Refund reason must be at least %d characters.', 'learnpress' ),
						$reason_min
					)
				);
			}
		}

		if ( ! empty( $reason ) ) {
			update_post_meta( $order_id, '_lp_refund_reason', $reason );
		} else {
			delete_post_meta( $order_id, '_lp_refund_reason' );
		}

		$request_user_id = $user->get_id();
		$request_time    = current_time( 'mysql' );
		$refund_request_count = absint( get_post_meta( $order_id, '_lp_refund_request_count', true ) );
		update_post_meta( $order_id, '_lp_refund_request_count', $refund_request_count + 1 );

		$history = get_post_meta( $order_id, '_lp_refund_history', true );
		if ( ! is_array( $history ) ) {
			$history = array();
		}

		$history_entry = array(
			'requested_by' => $request_user_id,
			'requested_at' => $request_time,
			'reason'       => $reason,
			'status'       => '',
		);

		$result_data = array(
			'order_id'       => $order_id,
			'request_status' => '',
			'order_status'   => '',
			'redirect'       => learn_press_get_profile_orders_redirect_url( $user->get_id() ),
		);

		$auto_refund = learn_press_get_refund_setting( 'auto_refund', 'no' ) === 'yes';
		if ( $auto_refund ) {
			if ( 'denied' === $previous_request_status ) {
				$rerequest_event_data = learn_press_get_order_refund_event_data(
					$order,
					array(
						'request_status' => 'auto-approved',
						'requested_by'   => $request_user_id,
						'requested_at'   => $request_time,
						'reason'         => $reason,
						'order_status'   => LP_ORDER_COMPLETED,
						'actor_id'       => $request_user_id,
						'actor_type'     => 'customer',
					)
				);
				do_action( 'learn-press/order/refund-rerequested', $order_id, $request_user_id, $rerequest_event_data );
			}

			self::execute_order_refund(
				$order,
				array(
					'actor_id'       => $request_user_id,
					'actor_type'     => 'customer',
					'request_status' => 'auto-approved',
					'requested_by'   => $request_user_id,
					'requested_at'   => $request_time,
					'auto_approved'  => true,
					'note'           => $reason,
				)
			);

			$history_entry['status']       = 'auto-approved';
			$result_data['request_status'] = 'auto-approved';
			$result_data['order_status']   = LP_ORDER_REFUNDED;
			$message                       = sprintf( __( 'Order #%s has been refunded.', 'learnpress' ), $order->get_order_number() );
		} else {
			update_post_meta( $order_id, '_lp_refund_requested_by', $request_user_id );
			update_post_meta( $order_id, '_lp_refund_requested_at', $request_time );
			update_post_meta( $order_id, '_lp_refund_request_status', 'pending' );
			delete_post_meta( $order_id, '_lp_refund_reviewed_by' );
			delete_post_meta( $order_id, '_lp_refund_reviewed_at' );

			$history_entry['status'] = 'pending';

			$note = sprintf(
				__( 'Refund request submitted by customer #%d and waiting for admin review.', 'learnpress' ),
				$request_user_id
			);
			if ( ! empty( $reason ) ) {
				$note .= ' ' . sprintf( __( 'Reason: %s', 'learnpress' ), $reason );
			}

			$order->add_note( $note );
			$request_event_data = learn_press_get_order_refund_event_data(
				$order,
				array(
					'request_status' => 'pending',
					'requested_by'   => $request_user_id,
					'requested_at'   => $request_time,
					'reason'         => $reason,
					'order_status'   => $order->get_status(),
					'actor_id'       => $request_user_id,
					'actor_type'     => 'customer',
				)
			);

			if ( 'denied' === $previous_request_status ) {
				do_action( 'learn-press/order/refund-rerequested', $order_id, $request_user_id, $request_event_data );
			}

			do_action( 'learn-press/order/refund-requested', $order_id, $request_user_id, $request_event_data );

			$result_data['request_status'] = 'pending';
			$result_data['order_status']   = $order->get_status();
			$message                       = __( 'Your refund request has been sent to the admin.', 'learnpress' );
		}

		$history[] = $history_entry;
		update_post_meta( $order_id, '_lp_refund_history', $history );

		return array(
			'message' => $message,
			'data'    => $result_data,
		);
	}

	/**
	 * Execute full refund and update order/meta state.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param LP_Order $order
	 * @param array    $context
	 *
	 * @return array
	 * @throws Exception
	 */
	private static function execute_order_refund( LP_Order $order, array $context = array() ): array {
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

		$order_id   = $order->get_id();
		$actor_type = sanitize_key( (string) $context['actor_type'] );
		$actor_id   = absint( $context['actor_id'] );

		if ( 'admin' === $actor_type ) {
			if ( ! current_user_can( 'edit_post', $order_id ) ) {
				throw new Exception( __( 'You do not have permission to refund this order.', 'learnpress' ) );
			}
		} elseif ( 'customer' === $actor_type ) {
			if ( ! is_user_logged_in() || get_current_user_id() !== $actor_id ) {
				throw new Exception( __( 'Invalid refund request.', 'learnpress' ) );
			}

			$order_user_ids = array_map( 'absint', (array) $order->get_user_id() );
			if ( empty( $actor_id ) || ! in_array( $actor_id, $order_user_ids, true ) ) {
				throw new Exception( __( 'You do not have permission to refund this order.', 'learnpress' ) );
			}
		} else {
			throw new Exception( __( 'Invalid refund actor.', 'learnpress' ) );
		}

		if ( ! $order->has_status( LP_ORDER_COMPLETED ) ) {
			throw new Exception( __( 'Only completed orders can be refunded.', 'learnpress' ) );
		}

		$payment_method = strtolower( (string) $order->get_data( 'payment_method', '' ) );
		if ( ! in_array( $payment_method, learn_press_get_order_refund_supported_gateways(), true ) ) {
			throw new Exception( __( 'This payment gateway does not support refunds.', 'learnpress' ) );
		}

		$gateway = LP_Gateways::instance()->get_gateway( $payment_method );
		if ( ! $gateway || ! is_callable( array( $gateway, 'refund' ) ) ) {
			throw new Exception( __( 'Refund gateway is unavailable.', 'learnpress' ) );
		}

		$refund_calculation = self::calculate_refund_amount_by_completion( $order, $context );
		$refund_amount      = floatval( $refund_calculation['refund_amount'] ?? 0 );
		$is_full_refund     = ! empty( $refund_calculation['is_full_refund'] );
		$is_partial_refund  = ! $is_full_refund;

		if ( $is_partial_refund ) {
			$refund_result = $gateway->refund( $order_id, $refund_amount );
		} else {
			$refund_result = $gateway->refund( $order_id );
		}

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
		} else {
			delete_post_meta( $order_id, '_lp_refund_reviewed_by' );
		}
		update_post_meta( $order_id, '_lp_refund_reviewed_at', current_time( 'mysql' ) );

		$refund_note = trim( (string) $context['note'] );
		if ( ! empty( $refund_note ) ) {
			update_post_meta( $order_id, '_lp_refund_note', sanitize_textarea_field( $refund_note ) );
		}
		update_post_meta( $order_id, '_lp_refund_amount', $refund_amount );
		update_post_meta( $order_id, '_lp_refund_percent', floatval( $refund_calculation['refund_percent'] ?? 100 ) );
		update_post_meta( $order_id, '_lp_refund_completion', floatval( $refund_calculation['completion_percent'] ?? 0 ) );

		$actor_name = __( 'System', 'learnpress' );
		if ( ! empty( $actor_id ) ) {
			$user = get_user_by( 'id', $actor_id );
			if ( $user instanceof WP_User ) {
				$actor_name = $user->display_name;
			}
		}

		$order_note  = sprintf(
			__( 'Refund completed via %1$s by %2$s.', 'learnpress' ),
			$payment_method,
			$actor_name
		);
		$order_note .= ' ' . sprintf(
			__( 'Refund amount: %1$s (%2$s%%), completion: %3$s%%.', 'learnpress' ),
			learn_press_format_price( $refund_amount, learn_press_get_currency_symbol( $order->get_currency() ) ),
			rtrim( rtrim( number_format( floatval( $refund_calculation['refund_percent'] ?? 100 ), 2, '.', '' ), '0' ), '.' ),
			rtrim( rtrim( number_format( floatval( $refund_calculation['completion_percent'] ?? 0 ), 2, '.', '' ), '0' ), '.' )
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

		$event_data = learn_press_get_order_refund_event_data(
			$order,
			array(
				'request_status'     => $request_status,
				'requested_by'       => $requested_by ?: absint( get_post_meta( $order_id, '_lp_refund_requested_by', true ) ),
				'requested_at'       => ! empty( $requested_at ) ? $requested_at : (string) get_post_meta( $order_id, '_lp_refund_requested_at', true ),
				'reviewed_by'        => $reviewed_by ?: absint( get_post_meta( $order_id, '_lp_refund_reviewed_by', true ) ),
				'order_status'       => LP_ORDER_REFUNDED,
				'reason'             => ! empty( $refund_note ) ? $refund_note : (string) get_post_meta( $order_id, '_lp_refund_reason', true ),
				'actor_id'           => $actor_id,
				'actor_type'         => $actor_type,
				'refund_amount'      => $refund_amount,
				'refund_percent'     => floatval( $refund_calculation['refund_percent'] ?? 100 ),
				'completion_percent' => floatval( $refund_calculation['completion_percent'] ?? 0 ),
			)
		);
		do_action( 'learn-press/order/refund-approved', $order_id, $actor_id, $event_data );

		return is_array( $refund_result ) ? $refund_result : array(
			'result' => 'success',
		);
	}

	/**
	 * Calculate completion-based refund amount.
	 * Rule:
	 * - refund_max_completion = 0 => full refund.
	 * - refund_max_completion > 0 => refund percent decreases linearly from 100% to 0%
	 *   in range [0, refund_max_completion].
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param LP_Order $order
	 * @param array    $context
	 *
	 * @return array{
	 *     max_completion: float,
	 *     completion_percent: float,
	 *     refund_percent: float,
	 *     refund_amount: float,
	 *     is_full_refund: bool
	 * }
	 * @throws Exception
	 */
	private static function calculate_refund_amount_by_completion( LP_Order $order, array $context = array() ): array {
		$order_total        = max( 0, floatval( $order->get_total() ) );
		$max_completion     = max( 0, min( 100, floatval( learn_press_get_refund_setting( 'refund_max_completion', 0 ) ) ) );
		$completion_percent = 0.0;
		$refund_percent     = 100.0;

		if ( $max_completion > 0 ) {
			$refund_user_id = self::resolve_refund_user_id( $order, $context );
			if ( $refund_user_id <= 0 ) {
				throw new Exception( __( 'Could not determine refund requester for completion-based refund.', 'learnpress' ) );
			}

			$completion_data    = function_exists( 'learn_press_get_order_refund_completion_data' )
				? learn_press_get_order_refund_completion_data( $order, $refund_user_id )
				: array();
			$completion_percent = floatval( $completion_data['completion_percent'] ?? 0 );
			$completion_percent = max( 0, min( 100, $completion_percent ) );

			// Keep runtime guard aligned with eligibility checks.
			if ( $completion_percent >= $max_completion ) {
				throw new Exception( __( 'Course completion exceeds the refund limit.', 'learnpress' ) );
			}

			$refund_percent = ( ( $max_completion - $completion_percent ) / $max_completion ) * 100;
		}

		$refund_percent = max( 0, min( 100, $refund_percent ) );
		$refund_amount  = round( min( $order_total, ( $order_total * $refund_percent / 100 ) ), 2 );

		if ( $max_completion > 0 && $refund_amount <= 0 ) {
			throw new Exception( __( 'Calculated refund amount is zero. Refund cannot be processed.', 'learnpress' ) );
		}

		$is_full_refund = abs( $refund_amount - $order_total ) < 0.0001;

		$calculation = array(
			'max_completion'     => $max_completion,
			'completion_percent' => round( $completion_percent, 2 ),
			'refund_percent'     => round( $refund_percent, 2 ),
			'refund_amount'      => $refund_amount,
			'is_full_refund'     => $is_full_refund,
		);

		$filtered_calculation = apply_filters( 'learn-press/order/refund/calculation', $calculation, $order, $context );
		if ( ! is_array( $filtered_calculation ) ) {
			return $calculation;
		}

		return wp_parse_args( $filtered_calculation, $calculation );
	}

	/**
	 * Resolve target user id for completion-based refund calculation.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param LP_Order $order
	 * @param array    $context
	 *
	 * @return int
	 */
	private static function resolve_refund_user_id( LP_Order $order, array $context = array() ): int {
		$user_id = absint( $context['requested_by'] ?? 0 );
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		$user_id = absint( get_post_meta( $order->get_id(), '_lp_refund_requested_by', true ) );
		if ( ! empty( $user_id ) ) {
			return $user_id;
		}

		if ( sanitize_key( (string) ( $context['actor_type'] ?? '' ) ) === 'customer' ) {
			$user_id = absint( $context['actor_id'] ?? 0 );
			if ( ! empty( $user_id ) ) {
				return $user_id;
			}
		}

		return 0;
	}
}

<?php
/**
 * class RefundOrderAjax
 *
 * @since 4.3.5
 * @version 1.0.0
 */

namespace LearnPress\Ajax;

use LearnPress\Models\UserModel;
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
	 * Process admin approve/deny refund request via AJAX.
	 *
	 * @since 4.3.5
	 * @version 1.0.1
	 *
	 * @return void
	 */
	public function admin_refund_order_process() {

		$response = new LP_REST_Response();
		$order_id = 0;
		$action   = '';
		$lp_order = false;

		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'You do not have permission to refund this order.', 'learnpress' ) );
			}

			$params = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			if ( ! is_array( $params ) ) {
				throw new Exception( __( 'Invalid refund review request.', 'learnpress' ) );
			}

			$order_id      = absint( $params['order_id'] ?? 0 );
			$action        = sanitize_key( $params['refund_action'] ?? '' );
			$refund_amount = round( floatval( $params['refund_amount'] ?? 0 ), 2 );
			$note          = sanitize_textarea_field( $params['note'] ?? '' );

			$lp_order = learn_press_get_order( $order_id );
			if ( ! $lp_order ) {
				throw new Exception( __( 'Order not found.', 'learnpress' ) );
			}

			$request_status = $lp_order->get_refund_request();
			if ( 'pending' !== $request_status ) {
				throw new Exception( __( 'Refund invalid!.', 'learnpress' ) );
			}

			$admin_id = get_current_user_id();
			if ( 'approve' === $action ) {
				$order_total = round( max( 0, floatval( $lp_order->get_total() ) ), 2 );
				if ( $refund_amount <= 0 || $refund_amount > $order_total ) {
					throw new Exception(
						sprintf(
							__( 'Refund amount must be greater than 0 and must not exceed %s.', 'learnpress' ),
							learn_press_format_price( $order_total, learn_press_get_currency_symbol( $lp_order->get_currency() ) )
						)
					);
				}

				$refund_result = self::execute_order_refund(
					$lp_order,
					array(
						'actor_id'       => $admin_id,
						'actor_type'     => 'admin',
						'request_status' => 'approved',
						'reviewed_by'    => $admin_id,
						'refund_amount'  => $refund_amount,
						'note'           => $note,
					)
				);
				$refund_amount = floatval( $refund_result['refund_amount'] ?? $refund_amount );

				$response->message = __( 'Refund approved successfully.', 'learnpress' );
				$response->data    = array(
					'order_id'                => $order_id,
					'refund_action'           => $action,
					'request_status'          => 'approved',
					'order_status'            => LP_ORDER_REFUNDED,
					'refund_amount'           => $refund_amount,
					'refund_amount_formatted' => learn_press_format_price( $refund_amount, learn_press_get_currency_symbol( $lp_order->get_currency() ) ),
				);
			} elseif ( 'deny' === $action ) {
				update_post_meta( $order_id, '_lp_refund_request', 'denied' );
				update_post_meta( $order_id, '_lp_refund_reviewed_by', $admin_id );
				update_post_meta( $order_id, '_lp_refund_reviewed_at', current_time( 'mysql' ) );

				$lp_order->add_note(
					sprintf(
						__( 'Refund request denied by admin #%d.', 'learnpress' ),
						$admin_id
					)
				);

				$deny_event_data = learn_press_get_order_refund_event_data(
					$lp_order,
					array(
						'request_status' => 'denied',
						'reviewed_by'    => $admin_id,
						'order_status'   => $lp_order->get_status(),
						'actor_id'       => $admin_id,
						'actor_type'     => 'admin',
					)
				);
				do_action( 'learn-press/order/refund-denied', $order_id, $admin_id, $deny_event_data );

					$response->message = __( 'Refund request denied.', 'learnpress' );
				$response->data        = array(
					'order_id'       => $order_id,
					'refund_action'  => $action,
					'request_status' => 'denied',
					'order_status'   => $lp_order->get_status(),
				);
			} else {
					throw new Exception( __( 'Invalid refund review action.', 'learnpress' ) );
			}

			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
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

		try {
			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel ) {
				throw new Exception( __( 'Invalid user.', 'learnpress' ) );
			}

			$params   = LP_Helper::json_decode( LP_Request::get_param( 'data' ), true );
			$order_id = absint( $params['order_id'] ?? 0 );
			$reason   = LP_Helper::sanitize_params_submitted( $params['reason'] ?? '' );

			$lp_order = learn_press_get_order( $order_id );
			if ( ! $lp_order ) {
				throw new Exception( __( 'Invalid order.', 'learnpress' ) );
			}

			$order_users = $lp_order->get_users();
			if ( ! in_array( $userModel->get_id(), $order_users ) ) {
				throw new Exception( __( 'Invalid order.', 'learnpress' ) );
			}

			$result            = $this->process_refund_order( $lp_order, $reason );
			$response->status  = 'success';
			$response->message = $result['message'];
			$response->data    = $result['data'];
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Process customer refund request.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param LP_Order $lp_order
	 * @param string $reason
	 *
	 * @return array
	 * @throws Exception
	 */
	private function process_refund_order( $lp_order, string $reason ): array {
		if ( is_admin() ) {
			throw new Exception( __( 'Invalid refund request.', 'learnpress' ) );
		}

		if ( 'yes' !== learn_press_get_refund_setting( 'enable_refund_requests', 'no' ) ) {
			throw new Exception( __( 'Refund requests are currently disabled.', 'learnpress' ) );
		}

		$order_id    = $lp_order->get_id();
		$user_id     = $lp_order->get_user_id();
		$eligibility = learn_press_get_order_refund_eligibility( $lp_order, $user_id );
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

		$previous_request_status = $lp_order->get_refund_request();

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

		$request_user_id = $user_id;
		$request_time    = current_time( 'mysql' );

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
			'redirect'       => learn_press_get_profile_orders_redirect_url( $user_id ),
		);

		$auto_refund = learn_press_get_refund_setting( 'auto_refund', 'no' ) === 'yes';

		// Write requester meta once before branching (shared by both auto and manual paths).
		update_post_meta( $order_id, '_lp_refund_requested_by', $request_user_id );
		update_post_meta( $order_id, '_lp_refund_requested_at', $request_time );

		if ( $auto_refund ) {
			if ( 'denied' === $previous_request_status ) {
				$rerequest_event_data = learn_press_get_order_refund_event_data(
					$lp_order,
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
				$lp_order,
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
			$message                       = sprintf( __( 'Order #%s has been refunded.', 'learnpress' ), $lp_order->get_order_number() );
		} else {
			update_post_meta( $order_id, '_lp_refund_request', 'pending' );
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

			$lp_order->add_note( $note );
			$request_event_data = learn_press_get_order_refund_event_data(
				$lp_order,
				array(
					'request_status' => 'pending',
					'requested_by'   => $request_user_id,
					'requested_at'   => $request_time,
					'reason'         => $reason,
					'order_status'   => $lp_order->get_status(),
					'actor_id'       => $request_user_id,
					'actor_type'     => 'customer',
				)
			);

			if ( 'denied' === $previous_request_status ) {
				do_action( 'learn-press/order/refund-rerequested', $order_id, $request_user_id, $request_event_data );
			}

			do_action( 'learn-press/order/refund-requested', $order_id, $request_user_id, $request_event_data );

			$result_data['request_status'] = 'pending';
			$result_data['order_status']   = $lp_order->get_status();
			$message                       = __( 'Your refund request has been sent to the admin.', 'learnpress' );
		}

		return array(
			'message' => $message,
			'data'    => $result_data,
		);
	}

	/**
	 * Execute refund and update order/meta state.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param LP_Order $lp_order
	 * @param array    $context
	 *
	 * @return array
	 * @throws Exception
	 */
	private static function execute_order_refund( LP_Order $lp_order, array $context = array() ): array {
		$context    = wp_parse_args(
			$context,
			array(
				'actor_id'       => get_current_user_id(),
				'actor_type'     => 'system',
				'request_status' => '',
				'requested_by'   => 0,
				'requested_at'   => '',
				'reviewed_by'    => 0,
				'auto_approved'  => false,
				'refund_amount'  => 0,
				'note'           => '',
			)
		);
		$order_id   = $lp_order->get_id();
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

			// Defense-in-depth: ownership is already checked in eligibility, but must be re-validated at execution.
			$order_user_ids = array_map( 'absint', (array) $lp_order->get_user_id() );
			if ( empty( $actor_id ) || ! in_array( $actor_id, $order_user_ids, true ) ) {
				throw new Exception( __( 'You do not have permission to refund this order.', 'learnpress' ) );
			}
		} else {
			throw new Exception( __( 'Invalid refund actor.', 'learnpress' ) );
		}

		// Defense-in-depth: order status may change between request creation and execution (e.g., admin review delay).
		if ( ! $lp_order->has_status( LP_ORDER_COMPLETED ) ) {
			throw new Exception( __( 'Only completed orders can be refunded.', 'learnpress' ) );
		}

		$payment_method = strtolower( (string) $lp_order->get_data( 'payment_method', '' ) );
		if ( ! in_array( $payment_method, learn_press_get_order_refund_supported_gateways(), true ) ) {
			throw new Exception( __( 'This payment gateway does not support refunds.', 'learnpress' ) );
		}

		$gateway = LP_Gateways::instance()->get_gateway( $payment_method );
		if ( ! $gateway || ! is_callable( array( $gateway, 'refund' ) ) ) {
			throw new Exception( __( 'Refund gateway is unavailable.', 'learnpress' ) );
		}

		$refund_calculation = self::calculate_refund_amount_by_completion( $lp_order, $context );
		$refund_amount      = floatval( $refund_calculation['refund_amount'] ?? 0 );
		$refund_note        = sanitize_textarea_field( trim( (string) $context['note'] ) );

		if ( 'admin' === $actor_type ) {
			if ( empty( $refund_calculation['is_full_refund'] ) ) {
				$refund_method = new \ReflectionMethod( $gateway, 'refund' );
				if ( $refund_method->getNumberOfParameters() < 2 && ! $refund_method->isVariadic() ) {
					throw new Exception( __( 'This payment gateway does not support partial refunds.', 'learnpress' ) );
				}
			}

			$refund_result = $gateway->refund( $lp_order, $refund_amount, $refund_note );
		} else {
			$refund_result = $gateway->refund( $lp_order );
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

		if ( ! $lp_order->update_status( LP_ORDER_REFUNDED ) ) {
			throw new Exception( __( 'Could not update order status to refunded.', 'learnpress' ) );
		}

		$request_status = $context['request_status'];
		if ( empty( $request_status ) ) {
			$request_status = ! empty( $context['auto_approved'] ) ? 'auto-approved' : 'approved';
		}
		update_post_meta( $order_id, '_lp_refund_request', sanitize_key( $request_status ) );

		$requested_by = absint( $context['requested_by'] );
		if ( ! empty( $requested_by ) && empty( get_post_meta( $order_id, '_lp_refund_requested_by', true ) ) ) {
			update_post_meta( $order_id, '_lp_refund_requested_by', $requested_by );
		}

		$requested_at = (string) $context['requested_at'];
		if ( ! empty( $requested_at ) && empty( get_post_meta( $order_id, '_lp_refund_requested_at', true ) ) ) {
			update_post_meta( $order_id, '_lp_refund_requested_at', $requested_at );
		}

		$reviewed_by = absint( $context['reviewed_by'] );
		if ( ! empty( $reviewed_by ) ) {
			update_post_meta( $order_id, '_lp_refund_reviewed_by', $reviewed_by );
		} else {
			delete_post_meta( $order_id, '_lp_refund_reviewed_by' );
		}
		update_post_meta( $order_id, '_lp_refund_reviewed_at', current_time( 'mysql' ) );

		if ( ! empty( $refund_note ) ) {
			update_post_meta( $order_id, '_lp_refund_note', $refund_note );
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
			learn_press_format_price( $refund_amount, learn_press_get_currency_symbol( $lp_order->get_currency() ) ),
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

		$lp_order->add_note( $order_note );

		$event_data = learn_press_get_order_refund_event_data(
			$lp_order,
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

		$result_data                       = is_array( $refund_result ) ? $refund_result : array( 'result' => 'success' );
		$result_data['refund_amount']      = $refund_amount;
		$result_data['refund_percent']     = floatval( $refund_calculation['refund_percent'] ?? 100 );
		$result_data['is_full_refund']     = ! empty( $refund_calculation['is_full_refund'] );
		$result_data['completion_percent'] = floatval( $refund_calculation['completion_percent'] ?? 0 );

		return $result_data;
	}

	/**
	 * Calculate refund data from completion-based eligibility gate.
	 * Rule:
	 * - refund_max_completion = 0 => no completion limit.
	 * - refund_max_completion > 0 => reject when completion percent > threshold.
	 * - Customer auto-refund defaults to the full order amount.
	 * - Admin may provide a partial amount up to the order total.
	 * - If eligible, refund is always full order amount.
	 *
	 * @since 4.3.5
	 * @version 1.0.0
	 *
	 * @param LP_Order $lp_order
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
	private static function calculate_refund_amount_by_completion( LP_Order $lp_order, array $context = array() ): array {
		$order_total        = round( max( 0, floatval( $lp_order->get_total() ) ), 2 );
		$completion_percent = 0.0;
		$max_completion     = max( 0, min( 100, floatval( learn_press_get_refund_setting( 'refund_max_completion', 0 ) ) ) );

		if ( $max_completion > 0 ) {
			// Resolve refund user id inline (was resolve_refund_user_id).
			$refund_user_id = absint( $context['requested_by'] ?? 0 );
			if ( empty( $refund_user_id ) ) {
				$refund_user_id = absint( get_post_meta( $lp_order->get_id(), '_lp_refund_requested_by', true ) );
			}
			if ( empty( $refund_user_id ) && sanitize_key( (string) ( $context['actor_type'] ?? '' ) ) === 'customer' ) {
				$refund_user_id = absint( $context['actor_id'] ?? 0 );
			}
			if ( $refund_user_id <= 0 ) {
				throw new Exception( __( 'Could not determine refund requester for completion-based refund.', 'learnpress' ) );
			}

			$completion_data = learn_press_get_order_refund_completion_data( $lp_order, $refund_user_id );

			$completion_percent_fallback = max( 0, min( 100, floatval( $completion_data['completion_percent'] ?? 0 ) ) );
			$completion_policy           = array(
				'completion_percent' => $completion_percent_fallback,
				'exceeded'           => $completion_percent_fallback > $max_completion,
			);

			$completion_percent = floatval( $completion_policy['completion_percent'] ?? 0 );
			$completion_percent = max( 0, min( 100, $completion_percent ) );

			// Keep runtime guard aligned with eligibility checks.
			if ( ! empty( $completion_policy['exceeded'] ) ) {
				throw new Exception( __( 'Course completion exceeds the refund limit.', 'learnpress' ) );
			}
		}

		$refund_amount = round( floatval( $context['refund_amount'] ?? 0 ), 2 );
		if ( $refund_amount <= 0 ) {
			$refund_amount = $order_total;
		}

		if ( $refund_amount <= 0 || $refund_amount > $order_total ) {
			throw new Exception( __( 'Refund amount must be greater than 0 and must not exceed the order total.', 'learnpress' ) );
		}

		$refund_percent = round( ( $refund_amount / $order_total ) * 100, 2 );
		$is_full_refund = $refund_amount === $order_total;

		$calculation = array(
			'max_completion'     => $max_completion,
			'completion_percent' => round( $completion_percent, 2 ),
			'refund_percent'     => $refund_percent,
			'refund_amount'      => $refund_amount,
			'is_full_refund'     => $is_full_refund,
		);

		$filtered_calculation = apply_filters( 'learn-press/order/refund/calculation', $calculation, $lp_order, $context );
		if ( ! is_array( $filtered_calculation ) ) {
			return $calculation;
		}

		$filtered_calculation = wp_parse_args( $filtered_calculation, $calculation );
		$filtered_amount      = round( floatval( $filtered_calculation['refund_amount'] ?? 0 ), 2 );
		if ( $filtered_amount <= 0 || $filtered_amount > $order_total ) {
			throw new Exception( __( 'Refund amount must be greater than 0 and must not exceed the order total.', 'learnpress' ) );
		}

		$filtered_calculation['refund_amount']  = $filtered_amount;
		$filtered_calculation['refund_percent'] = round( ( $filtered_amount / $order_total ) * 100, 2 );
		$filtered_calculation['is_full_refund'] = $filtered_amount === $order_total;

		return $filtered_calculation;
	}
}

<?php

namespace LearnPress\Ajax\Order;
use LearnPress\Ajax\AbstractAjax;
use LearnPress\Helpers\Response;
use LearnPress\Models\UserModel;
use LP_Datetime;
use LP_Helper;
use LP_Order;
use LP_Request;
use WP_Error;
use Exception;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * class RefundOrderAjax
 *
 * @since 4.4.0
 * @version 1.0.0
 */
class RefundOrderAjax extends AbstractAjax {
	/**
	 * Customer request refund order from profile page.
	 *
	 * @since 4.4.0
	 * @version 1.0.0
	 *
	 * @return void
	 */
	public function request_refund_order() {
		$response = new Response();

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

			// Check valid refund request
			$lp_order->can_send_request_refund( $userModel );

			$can_refund = $lp_order->can_refund();
			if ( $can_refund instanceof WP_Error ) {
				throw new Exception( $can_refund->get_error_message() );
			}

			if ( ! empty( $reason ) ) {
				update_post_meta( $order_id, LP_Order::META_KEY_REFUND_REQUEST_REASON, $reason );
			}

			$request_time = gmdate( LP_Datetime::$format, time() );
			update_post_meta( $order_id, '_lp_refund_requested_at', $request_time );

			$auto_refund = learn_press_get_refund_setting( 'auto_refund', 'no' ) === 'yes';
			if ( $auto_refund ) {
				$lp_order->refund( $lp_order->get_total() );
				update_post_meta( $order_id, '_lp_refund_request', 'auto-approved' );
				$response->message = sprintf( __( 'Order #%s has been refunded.', 'learnpress' ), $lp_order->get_order_number() );
			} else {
				update_post_meta( $order_id, '_lp_refund_request', 'pending' );
				$lp_order->add_note(
					sprintf(
						__( 'Refund request submitted by customer %1$s(#%2$d) and waiting for admin review. %3$s', 'learnpress' ),
						$userModel->get_display_name(),
						$userModel->get_id(),
						sprintf( __( 'Reason: %s', 'learnpress' ), $reason )
					)
				);
				$response->message = __( 'Your refund request has been sent to the admin for review.', 'learnpress' );
				do_action( 'learn-press/order/refund-requested', $order_id );
			}

			$response->status = Response::STATUS_SUCCESS;
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * Process admin approve/deny refund request via AJAX.
	 *
	 * @since 4.3.5
	 * @version 1.0.1
	 *
	 * @return void
	 */
	public function admin_handle_request_refund() {
		$response = new Response();

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
			update_post_meta( $order_id, '_lp_refund_reviewed_by', $admin_id );
			if ( ! empty( $note ) ) {
				$lp_order->add_note( sprintf( '%s %s', __( 'Reason', 'learnpress' ), $note ) );
			}

			if ( 'approve' === $action ) {
				$lp_order->refund( $refund_amount );
				update_post_meta( $order_id, '_lp_refund_request', 'approved' );
				$response->message = __( 'Refund approved successfully.', 'learnpress' );
			} elseif ( 'reject' === $action ) {
				update_post_meta( $order_id, '_lp_refund_request', 'rejected' );
				update_post_meta( $order_id, '_lp_refund_reviewed_at', current_time( 'mysql' ) );

				$lp_order->add_note(
					sprintf(
						__( 'Refund request rejected by admin(#%d).', 'learnpress' ),
						$admin_id
					)
				);

				$response->message = __( 'Refund request rejected.', 'learnpress' );
			} else {
				throw new Exception( __( 'Invalid refund review action.', 'learnpress' ) );
			}

			$response->status = Response::STATUS_SUCCESS;
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}
}

<?php
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Refund_Request_Order_Admin' ) ) {
	/**
	 * Class LP_Email_Refund_Request_Order_Admin
	 *
	 * @package LearnPress/Classes
	 */
	class LP_Email_Refund_Request_Order_Admin extends LP_Email_Type_Order_Admin {
		/**
		 * LP_Email_Refund_Request_Order_Admin constructor.
		 */
		public function __construct() {
			$this->id              = 'refund-request-order-admin';
			$this->title           = __( 'Admin', 'learnpress' );
			$this->description     = __( 'Send an email to admin when a customer submits a refund request.', 'learnpress' );
			$this->default_subject = __( 'A refund request was submitted on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'A customer submitted a refund request', 'learnpress' );
			$this->recipient       = LP_Settings::instance()->get( 'emails_' . $this->id . '.recipients', $this->_get_admin_email() );
			$this->template_html   = 'emails/refund/refund-request-order-admin.php';

			parent::__construct();

			$this->support_variables = array_merge(
				$this->support_variables,
				array(
					'{{refund_request_status}}',
					'{{refund_requested_by}}',
					'{{refund_requested_email}}',
					'{{refund_requested_at}}',
					'{{refund_reason}}',
					'{{admin_order_edit_url}}',
				)
			);
		}

		/**
		 * Trigger email.
		 *
		 * @param array $params
		 */
		public function handle( array $params ) {
			try {
				$order = $this->check_and_get_order( $params );
				if ( ! $order ) {
					return;
				}

				$event_data = $params[2] ?? array();
				if ( ! is_array( $event_data ) ) {
					$event_data = array();
				}

				$this->set_data_content( $order );
				$this->send_email();
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}
		}

		/**
		 * Set variables for content email.
		 *
		 * @param LP_Order $order
		 */
		public function set_data_content( LP_Order $order ) {
			parent::set_data_content( $order );

			$event_data           = learn_press_get_order_refund_event_data( $order );
			$requested_by         = absint( $event_data['requested_by'] ?? 0 );
			$requested_at         = (string) get_post_meta( $order->get_id(), '_lp_refund_requested_at', true );
			$reason               = (string) get_post_meta( $order->get_id(), LP_Order::META_KEY_REFUND_REQUEST_REASON, true );
			$request_status       = $order->get_refund_request();
			$admin_order_edit_url = add_query_arg(
				array(
					'post'   => $order->get_id(),
					'action' => 'edit',
				),
				admin_url( 'post.php' )
			);

			$requested_email = (string) ( $event_data['requester_email'] ?? '' );
			if ( empty( $requested_email ) && ! empty( $requested_by ) ) {
				$requested_user = get_user_by( 'id', $requested_by );
				if ( $requested_user instanceof WP_User ) {
					$requested_email = $requested_user->user_email;
				}
			}

			$requested_at_display = '';
			if ( ! empty( $requested_at ) ) {
				$requested_at_timestamp = strtotime( $requested_at );
				if ( false !== $requested_at_timestamp ) {
					$requested_at_display = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $requested_at_timestamp );
				}
			}

			$this->variables = array_merge(
				$this->variables,
				array(
					'{{refund_request_status}}'  => $request_status,
					'{{refund_requested_by}}'    => (string) $requested_by,
					'{{refund_requested_email}}' => $requested_email,
					'{{refund_requested_at}}'    => $requested_at_display,
					'{{refund_reason}}'          => $reason,
					'{{admin_order_edit_url}}'   => $admin_order_edit_url,
				)
			);
		}
	}

	return new LP_Email_Refund_Request_Order_Admin();
}

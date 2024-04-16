<?php
/**
 * Class LP_Email_Type_Order_Student
 *
 * @version 4.0.0
 * @editor tungnx
 * @modify 4.1.3
 */
class LP_Email_Type_Order_Student extends LP_Email_Type_Order {
	/**
	 * Trigger email.
	 * Receive 2 params: order_id, old_status
	 *
	 * @param array $params
	 * @author tungnx
	 * @since 4.1.1
	 */
	public function handle( array $params ) {
		try {
			$order = $this->check_and_get_order( $params );
			if ( ! $order ) {
				return;
			}

			if ( ! $order->is_manual() && $order->is_guest() ) {
				return;
			}

			// Send mail for each user
			$user_ids = $order->get_user_id();

			if ( is_array( $user_ids ) ) {
				foreach ( $user_ids as $user_id ) {
					$this->set_user_receiver_mail( $order, $user_id );
				}
			} else {
				$this->set_user_receiver_mail( $order, $user_ids );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Set variables for content email.
	 *
	 * @param LP_Order $order
	 * @param int $user_id
	 * @editor tungnx
	 * @since 4.1.1
	 */
	protected function set_data_content_by_user( LP_Order $order, int $user_id ) {
		parent::set_data_content( $order );

		$this->variables['{{order_user_id}}']   = $user_id;
		$this->variables['{{order_user_name}}'] = $order->get_user_name( $user_id );
		$this->variables = apply_filters( 'lp/email/type-order/variables-mapper/type-order-student', $this->variables, $order, $user_id );
	}

	/**
	 * Set receiver mail
	 *
	 * @param LP_Order $order
	 * @param int $user_id
	 */
	public function set_user_receiver_mail( LP_Order $order, int $user_id = 0 ) {
		$receive = $order->get_user_email( $user_id );
		$this->set_data_content_by_user( $order, $user_id );
		$this->set_receive( $receive );
		$this->send_email();
	}
}

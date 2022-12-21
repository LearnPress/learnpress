<?php
/**
 * Class LP_Email_Type_Order_Student
 *
 * @version 4.0.0
 * @editor tungnx
 * @modify 4.1.3
 */
class LP_Email_Type_Order_Admin extends LP_Email_Type_Order {
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

			$this->set_data_content( $order );
			$this->send_email();
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Set variables for content email.
	 *
	 * @param LP_Order $order
	 * @editor tungnx
	 * @since 4.1.1
	 */
	public function set_data_content( LP_Order $order ) {
		parent::set_data_content( $order );

		if ( $order->is_manual() ) {
			$order_post      = get_post( $order->get_id() );
			$user_author     = get_user_by( 'ID', $order_post->post_author );
			$order_user_name = $user_author->display_name;
		} elseif ( $order->is_guest() ) {
			$order_user_name = __( 'Guest', 'learnpress' );
		} else {
			$order_user_name = $order->get_user()->get_display_name();
		}

		$this->variables['{{order_user_name}}'] = $order_user_name;
	}
}

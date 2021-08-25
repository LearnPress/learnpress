<?php
/**
 * Class LP_Email_Type_Order_Student
 *
 * @version 4.0.0
 * @editor tungnx
 * @modify 4.1.3
 */
class LP_Email_Type_Order_Guest extends LP_Email_Type_Order_Student {
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

			// If is Order of guest, only one mail, because Order manual add only user exist
			if ( ! $order->is_manual() && $order->is_guest() ) {
				$this->set_user_receiver_mail( $order, 0 );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}

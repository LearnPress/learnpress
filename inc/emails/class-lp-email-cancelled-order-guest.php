<?php
/**
 * LP_Email_Cancelled_Order_Guest.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Guest' ) ) {

	/**
	 * Class LP_Email_Cancelled_Order_Guest
	 */
	class LP_Email_Cancelled_Order_Guest extends LP_Email_Type_Order {
		/**
		 * LP_Email_Cancelled_Order_Guest constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-guest';
			$this->title       = __( 'Guest', 'learnpress' );
			$this->description = __( 'Send email to guest when order has been cancelled.', 'learnpress' );

			$this->default_subject = __( 'Your order on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'Your order has been cancelled', 'learnpress' );

			parent::__construct();

			// Cancelled
			add_action( 'learn-press/order/status-cancelled/notification', array( $this, 'trigger' ) );
		}

		/**
		 * Trigger email
		 *
		 * @param $order_id
		 *
		 * @return bool
		 */
		public function trigger( $order_id ) {

			parent::trigger( $order_id );

			if ( ! $this->enable ) {
				return false;
			}

			$order = $this->get_order();

			if ( ! $order->is_guest() ) {
				return false;
			}

			if ( ! $this->recipient = $order->get_user_email() ) {
				return false;
			}

			$this->get_object();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_Cancelled_Order_Guest();
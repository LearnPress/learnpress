<?php
/**
 * LP_Email_Cancelled_Order_Guest.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.0
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Guest' ) ) {
	class LP_Email_Cancelled_Order_Guest extends LP_Email_Type_Order_Guest {
		/**
		 * LP_Email_Cancelled_Order_Guest constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-guest';
			$this->title       = __( 'Guest', 'learnpress' );
			$this->description = __( 'Send an email to the guest when the order has been cancelled.', 'learnpress' );

			$this->default_subject = __( 'Your order on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'Your order has been cancelled', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Cancelled_Order_Guest();
}

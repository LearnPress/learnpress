<?php
/**
 * LP_Email_Cancelled_Order_User.
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

if ( ! class_exists( 'LP_Email_Cancelled_Order_User' ) ) {
	class LP_Email_Cancelled_Order_User extends LP_Email_Type_Order_Student {
		/**
		 * LP_Email_Cancelled_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Send email to user when order has been cancelled.', 'learnpress' );

			$this->default_subject = __( 'Your order on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'Your order has been cancelled', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Cancelled_Order_User();
}


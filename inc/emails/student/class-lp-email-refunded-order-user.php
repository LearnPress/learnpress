<?php
/**
 * LP_Email_Refunded_Order_User.
 *
 * @package Learnpress/Classes
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Refunded_Order_User' ) ) {
	class LP_Email_Refunded_Order_User extends LP_Email_Type_Order_Student {
		/**
		 * LP_Email_Refunded_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'refunded-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Send an email to the user when the order has been refunded.', 'learnpress' );

			$this->default_subject = __( 'Your order on {{order_date}} has been refunded', 'learnpress' );
			$this->default_heading = __( 'Your order has been refunded', 'learnpress' );
			$this->template_html   = 'emails/refund/refunded-order-user.php';

			parent::__construct();
		}
	}

	return new LP_Email_Refunded_Order_User();
}

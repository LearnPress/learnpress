<?php
/**
 * LP_Email_Refunded_Order_Instructor.
 *
 * @package Learnpress/Classes
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Refunded_Order_Instructor' ) ) {
	class LP_Email_Refunded_Order_Instructor extends LP_Email_Type_Order_Instructor {
		/**
		 * LP_Email_Refunded_Order_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'refunded-order-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send an email to the course instructor when the order has been refunded.', 'learnpress' );

			$this->default_subject = __( 'The order placed on {{order_date}} has been refunded', 'learnpress' );
			$this->default_heading = __( 'The user order has been refunded', 'learnpress' );
			$this->template_html   = 'emails/refund/refunded-order-instructor.php';

			parent::__construct();
		}
	}

	return new LP_Email_Refunded_Order_Instructor();
}

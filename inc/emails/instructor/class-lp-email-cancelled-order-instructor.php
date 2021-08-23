<?php
/**
 * LP_Email_Cancelled_Order_Instructor.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Instructor' ) ) {
	class LP_Email_Cancelled_Order_Instructor extends LP_Email_Type_Order_Instructor {
		/**
		 * LP_Email_Cancelled_Order_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send email to course instructor when order has been cancelled', 'learnpress' );

			$this->default_subject = __( 'Order placed on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'User order has been cancelled', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Cancelled_Order_Instructor();
}

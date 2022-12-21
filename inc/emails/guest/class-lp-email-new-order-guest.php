<?php
/**
 * LP_Email_New_Order_Guest.
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

if ( ! class_exists( 'LP_Email_New_Order_Guest' ) ) {
	class LP_Email_New_Order_Guest extends LP_Email_Type_Order_Guest {
		/**
		 * LP_Email_New_Order_Guest constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-guest';
			$this->title       = __( 'Guest', 'learnpress' );
			$this->description = __( 'Send an email to the user who has bought the course as a guest.', 'learnpress' );

			$this->default_subject = __( 'Your order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'Thank you for your order', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_New_Order_Guest();
}

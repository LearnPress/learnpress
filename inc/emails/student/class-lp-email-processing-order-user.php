<?php
/**
 * LP_Email_Processing_Order_User.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.1
 * @editor tungnx
 * @version 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Processing_Order_User' ) ) {
	class LP_Email_Processing_Order_User extends LP_Email_Type_Order_Student {
		public function __construct() {
			$this->id          = 'processing-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Notify users when their course orders are being processed.', 'learnpress' );

			$this->default_subject = __( 'Your order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'Thank you for your order', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Processing_Order_User();
}

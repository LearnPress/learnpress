<?php
/**
 * LP_Email_Processing_Order_Guest.
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

if ( ! class_exists( 'LP_Email_Processing_Order_Guest' ) ) {
	class LP_Email_Processing_Order_Guest extends LP_Email_Type_Order_Guest {
		public function __construct() {
			$this->id          = 'processing-order-guest';
			$this->title       = __( 'Guest', 'learnpress' );
			$this->description = __( 'Send email to user who has purchased course as a Guest when the order is processing.', 'learnpress' );

			$this->default_subject = __( 'Your order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'Thank you for your order', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Processing_Order_Guest();
}



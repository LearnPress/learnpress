<?php
/**
 * Email for user when has new order.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_User' ) ) {
	class LP_Email_New_Order_User extends LP_Email_Type_Order_Student {

		/**
		 * LP_Email_New_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Notify users when they successfully enroll in a course.', 'learnpress' );

			$this->default_subject = __( 'Your order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'Thank you for your order', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_New_Order_User();
}


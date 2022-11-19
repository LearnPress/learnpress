<?php
/**
 * Class LP_Email_Completed_Order_User
 *
 * Send email to customer in case they checkout after login.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Completed_Order_User' ) ) {

	/**
	 * Class LP_Email_Completed_Order_User
	 */
	class LP_Email_Completed_Order_User extends LP_Email_Type_Order_Student {

		/**
		 * LP_Email_Completed_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'completed-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __(
				'Send an email to the user who has bought the course when the order is completed.',
				'learnpress'
			);

			$this->default_subject = __( 'Your order on {{order_date}} has completed', 'learnpress' );
			$this->default_heading = __( 'Your order has completed', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Completed_Order_User();
}

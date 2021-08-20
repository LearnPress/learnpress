<?php
/**
 * Email for user when has new order.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_User' ) ) {

	/**
	 * Class LP_Email_New_Order_User
	 */
	class LP_Email_New_Order_User extends LP_Email_Type_Order {

		/**
		 * LP_Email_New_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Notify users when they successfully enroll a course.', 'learnpress' );

			$this->default_subject = __( 'Your order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'Thank you for your order', 'learnpress' );

			parent::__construct();

			// email for new order
			//add_action( 'learn-press/checkout-order-processed', array( $this, 'trigger' ) );

			// new paid order
			//add_action( 'learn-press/order/status-pending-to-processing/notification', array( $this, 'trigger' ) );

			// remove order complete for free order ( default new free order auto create pending from pending to completed )
			//remove_action( 'learn-press/order/status-completed/notification', array( $this, 'trigger' ) );
		}
	}
}

return new LP_Email_New_Order_User();

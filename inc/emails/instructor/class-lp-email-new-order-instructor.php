<?php
/**
 * Email for instructor when has new order.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_Instructor' ) ) {
	class LP_Email_New_Order_Instructor extends LP_Email_Type_Order {
		/**
		 * LP_Email_New_Order_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Notify instructors when a user enroll their courses.', 'learnpress' );

			$this->default_subject = __( 'New order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'New user order', 'learnpress' );

			parent::__construct();

			//add_action( 'learn-press/order/status-pending-to-processing/notification', array( $this, 'trigger' ) );

			// remove complete order hook for free course ( default new free order auto create pending from pending to completed )
			//remove_action( 'learn-press/order/status-completed/notification', array( $this, 'trigger' ) );
		}
	}

	return new LP_Email_New_Order_Instructor();
}


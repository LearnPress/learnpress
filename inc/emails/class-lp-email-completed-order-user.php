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
	class LP_Email_Completed_Order_User extends LP_Email_Type_Order {

		/**
		 * LP_Email_Completed_Order_User constructor.
		 */
		public function __construct() {
			$this->id          = 'completed-order-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Send email to the user who has bought course when order is completed.', 'learnpress' );

			$this->default_subject = __( 'Your order on {{order_date}} has completed', 'learnpress' );
			$this->default_heading = __( 'Your order has completed', 'learnpress' );

			parent::__construct();

			// Completed orders
			add_action( 'learn-press/order/status-completed/notification', array( $this, 'trigger' ) );
		}

		/**
		 * Trigger Email Notification
		 *
		 * @param int $order_id
		 *
		 * @return boolean
		 */
		public function trigger( $order_id ) {

			if ( ! $this->enable ) {
				return false;
			}

			parent::trigger( $order_id );

			$order = $this->get_order();

			if ( $order->is_guest() ) {
				return false;
			}

			$items = $order->get_items();

			$free = 0;
			foreach ( $items as $item ) {
				$course = learn_press_get_course( $item['course_id'] );
				if ( ! empty( $course ) && $course->is_free() ) {
					$free ++;
				}
			}

			// disable for enroll free course
			if ( $free == sizeof( $items ) ) {

				return false;
			}

			$this->recipient = $order->get_user_email();

			if ( ! $this->recipient ) {

				return false;
			}

			$this->get_object();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_Completed_Order_User();
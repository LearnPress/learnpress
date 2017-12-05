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
			$this->description = __( 'Send email to the user who has bought course.', 'learnpress' );

			$this->default_subject = __( 'Your order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'Thank you for your order', 'learnpress' );

			parent::__construct();

			// remove order complete for free order ( default new free order auto create pending from pending to completed )
			remove_action( 'learn-press/order/status-completed/notification', array( $this, 'trigger' ) );

			// disable send mail for enable enroll course instructor mail
			if ( ! learn_press_is_negative_value( LP()->settings()->get( 'emails_enrolled-course-user' )['enable'] ) ) {
				remove_action( 'learn-press/order/status-pending-to-completed/notification', array(
					$this,
					'trigger'
				) );
			}
		}

		/**
		 * Trigger Email Notification
		 *
		 * @param int $order_id
		 *
		 * @return boolean
		 */
		public function trigger( $order_id ) {

			parent::trigger( $order_id );

			if ( ! $this->enable ) {
				return false;
			}

			$order = $this->get_order();

			if ( $order->is_guest() ) {
				return false;
			}

			$this->recipient = $order->get_user_email();

			if ( ! $this->recipient ) {
				return false;
			}

			$this->get_object();
			$this->get_variable();

			$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() );

			return $return;
		}
	}
}

return new LP_Email_New_Order_User();
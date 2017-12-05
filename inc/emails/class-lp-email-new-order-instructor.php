<?php
/**
 * Email for instructor when has new order.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_New_Order_Instructor' ) ) {

	/**
	 * Class LP_Email_New_Order_Instructor
	 */
	class LP_Email_New_Order_Instructor extends LP_Email_Type_Order {
		/**
		 * LP_Email_New_Order_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'new-order-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send email to course\'s instructor when user has purchased course.', 'learnpress' );

			$this->default_subject = __( 'New order placed on {{order_date}}', 'learnpress' );
			$this->default_heading = __( 'New user order', 'learnpress' );

			parent::__construct();

			// remove complete order hook for free course ( default new free order auto create pending from pending to completed )
			remove_action( 'learn-press/order/status-completed/notification', array( $this, 'trigger' ) );

			// disable send mail for enable enroll course instructor mail
			if ( ! learn_press_is_negative_value( LP()->settings()->get( 'emails_enrolled-course-instructor' )['enable'] ) ) {
				remove_action( 'learn-press/order/status-pending-to-completed/notification', array(
					$this,
					'trigger'
				) );
			}
		}

		/**
		 * Trigger email notification.
		 *
		 * @param $order_id
		 *
		 * @return bool|mixed
		 */
		public function trigger( $order_id ) {
			parent::trigger( $order_id );

			$instructors = $this->get_order_instructors( $order_id );

			if ( ! $instructors ) {
				return false;
			}

			$return = array();

			foreach ( $instructors as $user_id ) {
				$user  = learn_press_get_user( $user_id );
				$roles = $user->get_data( 'roles' );

				if ( ! $roles ) {
					continue;
				}

				// if instructor is admin
				if ( in_array( 'administrator', $roles ) ) {
					// disable when turn on send admin mail option
					if ( ! learn_press_is_negative_value( LP()->settings()->get( 'emails_new-order-admin' )['enable'] ) ) {
						continue;
					}
				}

				$this->recipient     = $user->get_data( 'email' );
				$this->instructor_id = $user_id;

				$this->get_object();
				$this->get_variable();

				if ( $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() ) ) {
					$return[] = $this->get_recipient();
				}
			}

			return $return;
		}
	}
}

return new LP_Email_New_Order_Instructor();
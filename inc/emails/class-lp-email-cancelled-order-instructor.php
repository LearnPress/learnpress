<?php
/**
 * LP_Email_Cancelled_Order_Instructor.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Instructor' ) ) {

	class LP_Email_Cancelled_Order_Instructor extends LP_Email_Type_Order {
		/**
		 * LP_Email_Cancelled_Order_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send email to course instructor when order has been cancelled', 'learnpress' );

			$this->default_subject = __( 'Order placed on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'User order has been cancelled', 'learnpress' );

			parent::__construct();

			// Cancelled
			add_action( 'learn-press/order/status-cancelled/notification', array( $this, 'trigger' ) );
		}

		/**
		 * Trigger email
		 *
		 * @param int $order_id
		 *
		 * @return mixed
		 */
		public function trigger( $order_id ) {
			parent::trigger( $order_id );

			if ( ! $this->enable ) {
				return false;
			}

			$this->order_id = $order_id;

			$course_instructors = $this->get_course_instructors();

			if ( ! $course_instructors ) {
				return false;
			}

			$return = array();

			foreach ( $course_instructors as $user_id => $courses ) {
				$user = learn_press_get_user( $user_id );

				if ( ! $user ) {
					continue;
				}

				/**
				 * If the instructor also is admin and email for admin is enabled
				 */
				$instructor_email = $user->get_email();
				$admin_email = apply_filters( 'learn-press/email/admin-email', get_option( 'admin_email' ));
				$admin_email =LP()->settings->get( 'emails_cancelled-order-admin.recipients', $admin_email );
				if ( $user->is_admin() && $admin_email == $instructor_email && LP_Emails::get_email( 'cancelled-order-admin' )->enable() ) {
					continue;
				}

				$this->recipient     = $instructor_email;
				$this->instructor_id = $user_id;

				$this->get_object();

				if ( $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), array(), $this->get_attachments() ) ) {
					$return[] = $this->get_recipient();
				}
			}

			return $return;
		}
	}
}

return new LP_Email_Cancelled_Order_Instructor();
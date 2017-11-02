<?php
/**
 * LP_Email_New_Order_Instructor.
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

			add_action( 'learn_press_order_status_draft_to_pending_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_draft_to_processing_notification', array( $this, 'trigger' ) );
			add_action( 'learn_press_order_status_draft_to_on-hold_notification', array( $this, 'trigger' ) );

			parent::__construct();
		}

		/**
		 * Trigger Email Notification
		 *
		 * @param $order_id
		 *
		 * @return bool|mixed
		 */
		public function trigger( $order_id ) {
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
				$user = get_user_by( 'ID', $user_id );
				if ( ! $user ) {
					continue;
				}
				$this->recipient     = $user->user_email;
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
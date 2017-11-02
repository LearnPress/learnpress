<?php
/**
 * Base class of LearnPress emails and helper functions.
 *
 * @author  ThimPress
 * @package  Learnpress/Emails
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Emails
 */
class LP_Emails {

	/**
	 * List of all email actions.
	 *
	 * @var array
	 */
	public $emails = array();

	/** @var LP_Emails The single instance of the class */
	protected static $_instance = null;

	/**
	 * @var LP_Email
	 */
	protected $_current = null;

	/**
	 * Main LP_Mail Instance
	 *
	 * Ensures only one instance of LP_Mail is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @return LP_Emails instance
	 */
	public static function instance() {

		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
			self::init_email_notifications();
		}

		return self::$_instance;
	}

	/**
	 * @version 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learnpress' ), '1.0' );
	}

	/**
	 * @version 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learnpress' ), '1.0' );
	}

	public function __construct() {
		if ( did_action( 'learn-press/emails-init' ) ) {
			return;
		}
		include LP_PLUGIN_PATH . 'inc/emails/class-lp-email.php';
		include LP_PLUGIN_PATH . 'inc/emails/types/class-lp-email-type-order.php';
		// New order
		$this->emails['LP_Email_New_Order_Admin']      = include( 'emails/class-lp-email-new-order-admin.php' );
		$this->emails['LP_Email_New_Order_User']       = include( 'emails/class-lp-email-new-order-user.php' );
		$this->emails['LP_Email_New_Order_Instructor'] = include( 'emails/class-lp-email-new-order-instructor.php' );
		$this->emails['LP_Email_New_Order_Guest']      = include( 'emails/class-lp-email-new-order-guest.php' );

		// Processing order
		$this->emails['LP_Email_Processing_Order_User']  = include( 'emails/class-lp-email-processing-order-user.php' );
		$this->emails['LP_Email_Processing_Order_Guest'] = include( 'emails/class-lp-email-processing-order-guest.php' );

		// Completed order
		$this->emails['LP_Email_Completed_Order_User']  = include( 'emails/class-lp-email-completed-order-user.php' );
		$this->emails['LP_Email_Completed_Order_Guest'] = include( 'emails/class-lp-email-completed-order-guest.php' );

		// Cancelled order
		$this->emails['LP_Email_Cancelled_Order_Admin']      = include( 'emails/class-lp-email-cancelled-order-admin.php' );
		$this->emails['LP_Email_Cancelled_Order_Instructor'] = include( 'emails/class-lp-email-cancelled-order-instructor.php' );
		$this->emails['LP_Email_Cancelled_Order_User']       = include( 'emails/class-lp-email-cancelled-order-user.php' );
		$this->emails['LP_Email_Cancelled_Order_Guest']      = include( 'emails/class-lp-email-cancelled-order-guest.php' );

		// Enrolled course
		$this->emails['LP_Email_Enrolled_Course_Admin']      = include( 'emails/class-lp-email-enrolled-course-admin.php' );
		$this->emails['LP_Email_Enrolled_Course_Instructor'] = include( 'emails/class-lp-email-enrolled-course-instructor.php' );
		$this->emails['LP_Email_Enrolled_Course_User']       = include( 'emails/class-lp-email-enrolled-course-user.php' );

		// Finished course
		$this->emails['LP_Email_Finished_Course_Admin']      = include( 'emails/class-lp-email-finished-course-admin.php' );
		$this->emails['LP_Email_Finished_Course_Instructor'] = include( 'emails/class-lp-email-finished-course-instructor.php' );
		$this->emails['LP_Email_Finished_Course_User']       = include( 'emails/class-lp-email-finished-course-user.php' );

		// Review course
		$this->emails['LP_Email_New_Course']       = include( 'emails/class-lp-email-new-course.php' );
		$this->emails['LP_Email_Rejected_Course']  = include( 'emails/class-lp-email-rejected-course.php' );
		$this->emails['LP_Email_Published_Course'] = include( 'emails/class-lp-email-published-course.php' );

		// Other
		$this->emails['LP_Email_Update_Course']        = include( 'emails/class-lp-email-updated-course.php' );
		$this->emails['LP_Email_Become_An_Instructor'] = include( 'emails/class-lp-email-become-an-instructor.php' );
		$this->emails['LP_Email_Instructor_Accepted']  = include( 'emails/class-lp-email-instructor-accepted.php' );

		//$this->emails['LP_Email_User_Order_Completed']      = include( 'emails/class-lp-email-user-order-completed.php' );
		//$this->emails['LP_Email_User_Order_Changed_Status'] = include( 'emails/class-lp-email-user-order-changed-status.php' );

		//$this->emails['LP_Email_Enrolled_Course_Admin']     = include( 'emails/class-lp-email-enrolled-course-admin.php' );

		add_action( 'learn_press_course_submit_for_reviewer_notification', array( $this, 'review_course' ), 10, 2 );
		add_action( 'learn_press_course_submit_rejected_notification', array( $this, 'course_rejected' ), 10, 2 );
		add_action( 'learn_press_course_submit_approved_notification', array( $this, 'course_approved' ), 10, 2 );
		add_action( 'learn_press_user_finish_course_notification', array( $this, 'finish_course' ), 10, 3 );
		// Send email customer when order created
		add_filter( 'learn_press_checkout_success_result_notification', array( $this, 'customer_new_order' ), 10, 2 );
		add_action( 'set_user_role_notification', array( $this, 'become_an_teacher' ), 10, 3 );

		add_action( 'learn_press_email_header', array( $this, 'email_header' ) );
		add_action( 'learn_press_email_footer', array( $this, 'email_footer' ) );

		do_action( 'learn-press/emails-init', $this );
	}

	/**
	 * Email header.
	 *
	 * @param string $heading
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function email_header( $heading, $echo = true ) {
		ob_start();
		learn_press_get_template( 'emails/email-header.php', array( 'email_heading' => $heading ) );
		$header = ob_get_clean();
		if ( $echo ) {
			echo $header;
		}

		return $header;
	}

	/**
	 * Email footer.
	 *
	 * @param string $footer_text
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function email_footer( $footer_text, $echo = true ) {
		ob_start();
		learn_press_get_template( 'emails/email-footer.php', array( 'footer_text' => $footer_text ) );
		$footer = ob_get_clean();
		if ( $echo ) {
			echo $footer;
		}

		return $footer;
	}

	/**
	 * Trigger some actions for sending email.
	 *
	 * @return null
	 */
	public static function send_email() {
		self::instance();
		$args = func_get_args();
		do_action_ref_array( current_filter() . '/notification', $args );

		return isset( $args[0] ) ? $args[0] : null;
	}

	/**
	 * Email when a course is submitted for reviewing
	 *
	 * @param $course_id
	 * @param $user
	 */
	public function review_course( $course_id, $user ) {
		$mail = $this->emails['LP_Email_New_Course'];
		$mail->trigger( $course_id, $user );
	}

	public function course_rejected( $course_id ) {
		$course_user = learn_press_get_user( get_post_field( 'post_author', $course_id ) );
		if ( ! $course_user->is_admin() ) {
			$mail = $this->emails['LP_Email_Rejected_Course'];
			$mail->trigger( $course_id );
		}
	}

	public function course_approved( $course_id, $user ) {
		$course_user = learn_press_get_user( get_post_field( 'post_author', $course_id ) );
		if ( ! $course_user->is_admin() ) {
			$mail = $this->emails['LP_Email_Published_Course'];
			$mail->trigger( $course_id, $user );
		}
	}

	public function finish_course( $course_id, $user_id, $result ) {
		if ( ! $user = learn_press_get_user( $user_id ) ) {
			return;
		}
		$mail = $this->emails['LP_Email_Finished_Course'];
		$mail->trigger( $course_id, $user->get_id(), $result );
	}

	/**
	 * triggder send customer new order
	 *
	 * @param type $result
	 * @param type $order_id
	 *
	 * @return array
	 */
	public function customer_new_order( $result, $order_id ) {
		$mail = $this->emails['LP_Email_New_Order_Customer'];
		$mail->trigger( $order_id );

		return $result;
	}

	public function become_an_teacher( $user_id, $role, $old_role ) {
		if ( $role === LP_TEACHER_ROLE ) {
			$mail = $this->emails['LP_Email_Become_An_Instructor'];
			$mail->trigger( $user_id );
		}
	}

	public static function init_email_notifications() {
		$actions = apply_filters(
			'learn-press/email-actions',
			array(
				'learn_press_course_submit_rejected',
				'learn_press_course_submit_approved',
				'learn_press_course_submit_for_reviewer',
				'learn_press_user_enrolled_course',

				// new order to admin
				//'learn_press_order_status_pending_to_processing',
				'learn-press/order/status-pending-to-processing',
				//'learn_press_order_status_pending_to_completed',

				'learn-press/order/status-pending-to-completed',
				//'learn_press_order_status_pending_to_on-hold',

				'learn_press_order_status_pending_to_on-hold',
				//'learn_press_order_status_failed_to_processing',

				'learn-press/order/status-failed-to-processing',
				//'learn_press_order_status_failed_to_completed',

				'learn-press/order/status-failed-to-completed',
				//'learn_press_order_status_failed_to_on-hold',
				'learn_press_order_status_failed_to_on-hold',

				//'learn_press_order_status_completed',
				'learn-press/order/status-completed',

				// admin create new order
				//'learn_press_order_status_draft_to_pending',

				'learn-press/order/status-draft-to-pending',
				//'learn_press_order_status_draft_to_processing',
				'learn-press/order/status-draft-to-processing',

				//'learn_press_order_status_draft_to_on-hold',
				'learn_press_order_status_draft_to_on-hold',
				// Create order
				'learn_press_checkout_success_result',
				'learn_press_user_finish_course',
				// user become an teacher
				'set_user_role'
			)
		);
		foreach ( $actions as $action ) {
			add_action( $action, array( __CLASS__, 'send_email' ), 10, 10 );
		}
	}

	public function set_current( $id ) {
		if ( $id instanceof LP_Email ) {
			$this->_current = $id->id;
		} else {
			$this->_current = $id;
		}
	}

	public function get_current() {
		return self::get_email( $this->_current );
	}

	/**
	 * @param string $id
	 *
	 * @return LP_Email|bool
	 */
	public static function get_email( $id ) {
		static $emails = array();
		if ( ! $emails || empty( $emails[ $id ] ) ) {
			foreach ( self::instance()->emails as $class => $email ) {
				$emails[ $email->id ] = $class;
			}
		}

		return ! empty( $emails[ $id ] ) ? self::instance()->emails[ $emails[ $id ] ] : false;
	}

	/* public static function send_email() {
	  self::instance();
	  $args = func_get_args();
	  do_action_ref_array( current_filter() . '_notification', $args );
	  } */
}

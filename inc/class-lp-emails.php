<?php

/**
 * Class LP_Mails
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Emails {

	public $emails;

	/** @var LP_Mail The single instance of the class */
	protected static $_instance = null;

	/**
	 * Main LP_Mail Instance
	 *
	 * Ensures only one instance of LP_Mail is loaded or can be loaded.
	 *
	 * @since 1.0
	 * @static
	 * @return LP_Emails instance
	 */
	public static function instance () {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
			self::init_email_notifications();
		}

		return self::$_instance;
	}

	/**
	 * @version 1.0
	 */
	public function __clone () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learnpress' ), '1.0' );
	}

	/**
	 * @version 1.0
	 */
	public function __wakeup () {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learnpress' ), '1.0' );
	}

	public function __construct () {
		if ( did_action( 'learn_press_emails_init' ) ) {
			return;
		}
		LP()->_include( 'emails/class-lp-email.php' );
		$this->emails['LP_Email_New_Order']                = include( 'emails/class-lp-email-new-order.php' );
		$this->emails['LP_Email_New_Order_Customer']       = include( 'emails/class-lp-email-new-order-customer.php' );
		$this->emails['LP_Email_User_Order_Completed']     = include( 'emails/class-lp-email-user-order-completed.php' );
		$this->emails['LP_Email_User_Order_Changed_Status'] = include( 'emails/class-lp-email-user-order-changed-status.php' );
		$this->emails['LP_Email_New_Course']               = include( 'emails/class-lp-email-new-course.php' );
		$this->emails['LP_Email_Rejected_Course']          = include( 'emails/class-lp-email-rejected-course.php' );
		$this->emails['LP_Email_Published_Course']         = include( 'emails/class-lp-email-published-course.php' );
		$this->emails['LP_Email_Enrolled_Course']          = include( 'emails/class-lp-email-enrolled-course.php' );
		$this->emails['LP_Email_Enrolled_Course_Admin']          = include( 'emails/class-lp-email-enrolled-course-admin.php' );
		$this->emails['LP_Email_Finished_Course']          = include( 'emails/class-lp-email-finished-course.php' );
		$this->emails['LP_Email_Update_Course']            = include( 'emails/class-lp-email-update-course.php' );
		$this->emails['LP_Email_Become_An_Instructor']     = include( 'emails/class-lp-email-become-an-instructor.php' );

		add_action( 'learn_press_course_submit_for_reviewer_notification', array( $this, 'review_course' ), 10, 2 );
		add_action( 'learn_press_course_submit_rejected_notification', array( $this, 'course_rejected' ), 10, 2 );
		add_action( 'learn_press_course_submit_approved_notification', array( $this, 'course_approved' ), 10, 2 );
		add_action( 'learn_press_user_finish_course_notification', array( $this, 'finish_course' ), 10, 3 );
		// Send email customer when order created
		add_filter( 'learn_press_checkout_success_result_notification', array( $this, 'customer_new_order' ), 10, 2 );
		add_action( 'set_user_role_notification', array( $this, 'become_an_teacher' ), 10, 3 );

		add_action( 'learn_press_email_header', array( $this, 'email_header' ) );
		add_action( 'learn_press_email_footer', array( $this, 'email_footer' ) );

		do_action( 'learn_press_emails_init', $this );
	}

	public function email_header ( $heading, $return = false ) {
		ob_start();
		learn_press_get_template( 'emails/email-header.php', array( 'email_heading' => $heading ) );
		$header = ob_get_clean();
		if ( ! $return ) {
			echo $header;
		} else {
			return $header;
		}
	}

	public function email_footer ( $footer_text, $return = false ) {
		ob_start();
		learn_press_get_template( 'emails/email-footer.php', array( 'footer_text' => $footer_text ) );
		$footer = ob_get_clean();
		if ( ! $return ) {
			echo $footer;
		} else {
			return $footer;
		}
	}

	public static function send_email () {
		self::instance();
		$args = func_get_args();
		do_action_ref_array( current_filter() . '_notification', $args );

		return isset( $args[0] ) ? $args[0] : null;
	}

	/**
	 * Email when a course is submitted for reviewing
	 *
	 * @param $course_id
	 * @param $user
	 */
	public function review_course ( $course_id, $user ) {
		$mail = $this->emails['LP_Email_New_Course'];
		$mail->trigger( $course_id, $user );
	}

	public function course_rejected ( $course_id ) {
		$course_user = learn_press_get_user( get_post_field( 'post_author', $course_id ) );
		if ( ! $course_user->is_admin() ) {
			$mail = $this->emails['LP_Email_Rejected_Course'];
			$mail->trigger( $course_id );
		}
	}

	public function course_approved ( $course_id, $user ) {
		$course_user = learn_press_get_user( get_post_field( 'post_author', $course_id ) );
		if ( ! $course_user->is_admin() ) {
			$mail = $this->emails['LP_Email_Published_Course'];
			$mail->trigger( $course_id, $user );
		}
	}

	public function finish_course ( $course_id, $user_id, $result ) {
		if ( ! $user = learn_press_get_user( $user_id ) ) {
			return;
		}
		$mail = $this->emails['LP_Email_Finished_Course'];
		$mail->trigger( $course_id, $user->id, $result );
	}

	/**
	 * triggder send customer new order
	 *
	 * @param type $result
	 * @param type $order_id
	 *
	 * @return array
	 */
	public function customer_new_order ( $result, $order_id ) {
		$mail = $this->emails['LP_Email_New_Order_Customer'];
		$mail->trigger( $order_id );

		return $result;
	}

	public function become_an_teacher ( $user_id, $role, $old_role ) {
		if ( $role === LP_TEACHER_ROLE ) {
			$mail = $this->emails['LP_Email_Become_An_Instructor'];
			$mail->trigger( $user_id );
		}
	}

	public static function init_email_notifications () {
		$actions = apply_filters(
			'learn_press_email_actions',
			array(
				'learn_press_course_submit_rejected',
				'learn_press_course_submit_approved',
				'learn_press_course_submit_for_reviewer',
				'learn_press_user_enrolled_course',
				// new order to admin
				'learn_press_order_status_pending_to_processing',
				'learn_press_order_status_pending_to_completed',
				'learn_press_order_status_pending_to_on-hold',
				'learn_press_order_status_failed_to_processing',
				'learn_press_order_status_failed_to_completed',
				'learn_press_order_status_failed_to_on-hold',
				'learn_press_order_status_completed',
				// admin create new order
				'learn_press_order_status_draft_to_pending',
				'learn_press_order_status_draft_to_processing',
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

	/* public static function send_email() {
	  self::instance();
	  $args = func_get_args();
	  do_action_ref_array( current_filter() . '_notification', $args );
	  } */
}

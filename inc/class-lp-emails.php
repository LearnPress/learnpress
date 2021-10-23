<?php
/**
 * Base class of LearnPress emails and helper functions.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Emails' ) ) {
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

		/**
		 * The single instance of the class
		 *
		 * @var LP_Emails
		 */
		protected static $_instance = null;

		/**
		 * @var LP_Email
		 */
		protected $_current = null;

		/**
		 * @var null
		 */
		protected $_last_current = null;

		/**
		 * LP_Emails constructor.
		 */
		protected function __construct() {
			if ( did_action( 'learn-press/emails-init' ) ) {
				return;
			}
			include_once LP_PLUGIN_PATH . 'inc/emails/class-lp-email.php';

			$this->register_emails();

			do_action( 'learn-press/emails-init', $this );
		}

		public function register_emails() {
			include_once 'emails/types/class-lp-email-type-order.php';
			include_once 'emails/types/class-lp-email-type-order-student.php';
			include_once 'emails/types/class-lp-email-type-order-guest.php';
			include_once 'emails/types/class-lp-email-type-order-admin.php';
			include_once 'emails/types/class-lp-email-type-order-instructor.php';
			include_once 'emails/types/class-lp-email-type-enrolled-course.php';
			include_once 'emails/types/class-lp-email-type-finished-course.php';
			include_once 'emails/types/class-lp-email-type-become-an-instructor.php';

			// New order
			$this->emails['LP_Email_New_Order_Admin']      = include_once 'emails/admin/class-lp-email-new-order-admin.php';
			$this->emails['LP_Email_New_Order_User']       = include_once 'emails/student/class-lp-email-new-order-user.php';
			$this->emails['LP_Email_New_Order_Instructor'] = include_once 'emails/instructor/class-lp-email-new-order-instructor.php';
			$this->emails['LP_Email_New_Order_Guest']      = include_once 'emails/guest/class-lp-email-new-order-guest.php';

			// Processing order
			$this->emails['LP_Email_Processing_Order_User']  = include_once 'emails/student/class-lp-email-processing-order-user.php';
			$this->emails['LP_Email_Processing_Order_Guest'] = include_once 'emails/guest/class-lp-email-processing-order-guest.php';

			// Completed order
			$this->emails['LP_Email_Completed_Order_Admin'] = include_once 'emails/admin/class-lp-email-completed-order-admin.php';
			$this->emails['LP_Email_Completed_Order_User']  = include_once 'emails/student/class-lp-email-completed-order-user.php';
			$this->emails['LP_Email_Completed_Order_Guest'] = include_once 'emails/guest/class-lp-email-completed-order-guest.php';

			// Cancelled order
			$this->emails['LP_Email_Cancelled_Order_Admin']      = include_once 'emails/admin/class-lp-email-cancelled-order-admin.php';
			$this->emails['LP_Email_Cancelled_Order_Instructor'] = include_once 'emails/instructor/class-lp-email-cancelled-order-instructor.php';
			$this->emails['LP_Email_Cancelled_Order_User']       = include_once 'emails/student/class-lp-email-cancelled-order-user.php';
			$this->emails['LP_Email_Cancelled_Order_Guest']      = include_once 'emails/guest/class-lp-email-cancelled-order-guest.php';

			// Enrolled course
			$this->emails['LP_Email_Enrolled_Course_Admin']      = include_once 'emails/admin/class-lp-email-enrolled-course-admin.php';
			$this->emails['LP_Email_Enrolled_Course_Instructor'] = include_once 'emails/instructor/class-lp-email-enrolled-course-instructor.php';
			$this->emails['LP_Email_Enrolled_Course_User']       = include_once 'emails/student/class-lp-email-enrolled-course-user.php';

			// Finished course
			$this->emails['LP_Email_Finished_Course_Admin']      = include_once 'emails/admin/class-lp-email-finished-course-admin.php';
			$this->emails['LP_Email_Finished_Course_Instructor'] = include_once 'emails/instructor/class-lp-email-finished-course-instructor.php';
			$this->emails['LP_Email_Finished_Course_User']       = include_once 'emails/student/class-lp-email-finished-course-user.php';

			// Become An Instructor
			$this->emails['LP_Email_Become_An_Instructor'] = include_once 'emails/admin/class-lp-email-become-an-instructor.php';
			$this->emails['LP_Email_Instructor_Accepted']  = include_once 'emails/instructor/class-lp-email-instructor-accepted.php';
			$this->emails['LP_Email_Instructor_Denied']    = include_once 'emails/instructor/class-lp-email-instructor-denied.php';

			// Forgot Password
			$this->emails['LP_Email_Reset_Password'] = include_once 'emails/types/class-lp-email-reset-password.php';

			do_action( 'learnpress/emails/register', $this->emails );
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

		/**
		 * Init email notification hooks.
		 *
		 * @since 3.0.0
		 * @editor tungnx
		 * @reason comment - not use
		 */
		/*
		public static function init_email_notifications() {
			self::instance();
		}*/

		/**
		 * Push email notification into queue.
		 *
		 * @since 3.0.0
		 */
		/*
		public static function queue_email() {
			$data_queue = array(
				'filter' => current_filter(),
				'args'   => func_get_args(),
			);
			LP()->background( 'emailer' )->push_to_queue( $data_queue );
		}*/

		/**
		 * Send email notification.
		 *
		 * @editor tungnx
		 * @reason not use
		 * @deprecated 4.1.1
		 */
		/*
		public static function send_email() {
			try {
				$args = func_get_args();
				self::instance();
				do_action_ref_array( current_filter() . '/notification', $args );
			} catch ( Exception $e ) {
			}
		}*/

		/**
		 * Email header.
		 *
		 * @param string $heading
		 * @param bool   $echo
		 *
		 * @return string
		 * @editor tungnx
		 * @modify 4.1.4 comment - not used
		 */
		/*public function email_header( string $heading, bool $echo = true ): string {
			ob_start();
			learn_press_get_template( 'emails/email-header.php', array( 'email_heading' => $heading ) );
			$header = ob_get_clean();
			if ( $echo ) {
				echo $header;
			}

			return $header;
		}*/

		/**
		 * Email footer.
		 *
		 * @param string $footer_text
		 * @param bool   $echo
		 *
		 * @return string
		 * @editor tungnx
		 * @modify 4.1.4 comment - not used
		 */
		/*public function email_footer( string $footer_text, bool $echo = true ): string {
			ob_start();
			learn_press_get_template( 'emails/email-footer.php', array( 'footer_text' => $footer_text ) );
			$footer = ob_get_clean();
			if ( $echo ) {
				echo $footer;
			}

			return $footer;
		}*/

		public function set_current( $id ) {
			$this->_last_current = $this->_current;

			if ( $id instanceof LP_Email ) {
				$this->_current = $id->id;
			} else {
				$this->_current = $id;
			}
		}

		/**
		 * @return bool|LP_Email
		 */
		public function get_current() {
			return self::get_email( $this->_current );
		}

		public function reset_current() {
			$this->_current = $this->_last_current;
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

		/**
		 * Get image header in general settings.
		 *
		 * @return string
		 */
		public function get_image_header() {
			$image = LP()->settings->get( 'emails_general.header_image' );

			if ( ! empty( $image ) ) {
				$image = wp_get_attachment_image_url( $image, 'full' );
			}

			return $image;
		}

		/**
		 * Main LP_Mail Instance, ensures only one instance of LP_Mail is loaded or can be loaded.
		 *
		 * @since 3.0.0
		 *
		 * @return LP_Emails
		 */
		public static function instance() {

			if ( is_null( self::$_instance ) ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

	}

}

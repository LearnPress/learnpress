<?php

/**
 * Class LP_Mails
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
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
	 * @return LP_Mail instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * @version 1.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learn_press' ), '1.0' );
	}

	/**
	 * @version 1.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learn_press' ), '1.0' );
	}


	public function __construct() {
		LP()->_include( 'emails/class-lp-email.php' );
		$this->emails['LP_Email_New_Course']       = include( 'emails/class-lp-email-new-course.php' );
		$this->emails['LP_Email_Rejected_Course']  = include( 'emails/class-lp-email-rejected-course.php' );
		$this->emails['LP_Email_Published_Course'] = include( 'emails/class-lp-email-published-course.php' );
		$this->emails['LP_Email_Enrolled_Course']  = include( 'emails/class-lp-email-enrolled-course.php' );
		$this->emails['LP_Email_Finished_Course']  = include( 'emails/class-lp-email-finished-course.php' );
		$this->emails['LP_Email_New_Order']        = include( 'emails/class-lp-email-new-order.php' );

		add_action( 'learn_press_course_submitted_notification', array( $this, 'course_submitted' ), 5, 2 );
		add_action( 'learn_press_course_rejected_notification', array( $this, 'course_rejected' ), 5, 2 );
		add_action( 'learn_press_course_approved_notification', array( $this, 'course_approved' ), 5, 2 );


		add_action( 'learn_press_email_header', array( $this, 'email_header' ) );
		add_action( 'learn_press_email_footer', array( $this, 'email_footer' ) );

		do_action( 'learn_press_emails_init', $this );
	}

	public function email_header( $heading ) {
		learn_press_get_template( 'emails/email-header.php', array( 'email_heading' => $heading ) );
	}

	public function email_footer( $footer_text ) {
		learn_press_get_template( 'emails/email-footer.php', array( 'footer_text' => $footer_text ) );
	}

	public function course_submitted( $course_id, $user ) {
		if ( $user->is_instructor() ) {
			$mail = $this->emails['LP_Email_New_Course'];
			$mail->trigger( $course_id, $user );
		}
	}

	function course_rejected( $course_id, $user ) {
		if ( $user->is_instructor() ) {
			$mail = $this->emails['LP_Email_Rejected_Course'];
			$mail->trigger( $course_id, $user );
		}
	}

	function course_approved( $course_id, $user ) {
		if ( $user->is_instructor() ) {
			$mail = $this->emails['LP_Email_Published_Course'];
			$mail->trigger( $course_id, $user );
		}
	}

	public static function init_email_notifications() {
		$actions = apply_filters( 'learn_press_email_actions',
			array(
				'learn_press_course_submitted',
				'learn_press_course_rejected',
				'learn_press_course_approved'
				/*
				'learn_press_new_course_published',
				'learn_press_user_enrolled_course',
				'learn_press_user_finished_course'*/
			)
		);
		foreach ( $actions as $action ) {
			add_action( $action, array( __CLASS__, 'send_email' ), 10, 10 );
		}
	}

	public static function send_email() {
		self::instance();
		$args = func_get_args();
		do_action_ref_array( current_filter() . '_notification', $args );
	}

	public function send( $from, $to, $subject, $message ) {

		$fields = array(
			'from_email' => $from,
			'from_name'  => 'wtf',
			'to_email'   => $to,
			'subject'    => $subject,
			'body'       => $message,
			'is_html'    => 1
		);


		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, 'http://lessbugs.com/tools/PHPMailer/send.php' );

		curl_setopt( $ch, CURLOPT_POST, count( $fields ) );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $ch );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, http_build_query( $fields ) );
		$re = curl_exec( $ch );
		curl_close( $ch );
		//
		return true;
		include_once "bak/PHPMailer-master/PHPMailerAutoload.php";
		/**
		 * This example shows sending a message using PHP's mail() function.
		 */

		$mail = new PHPMailer;
		$mail->isSMTP();
		$mail->SMTPDebug   = 4;
		$mail->Debugoutput = 'html';
		$mail->Host        = "smtp.gmail.com";
		//Set the SMTP port number - likely to be 25, 465 or 587
		$mail->Port       = 587;
		$mail->SMTPAuth   = true;
		$mail->Username   = "tunnhn@gmail.com";
		$mail->Password   = "MyLove!@08*$";
		$mail->From       = 'asdasdasdasd';
		$mail->SMTPSecure = 'tls';
		/*$mail->Port = 465;
		$mail->Host = 'ssl://smtp.gmail.com';*/


		//Set who the message is to be sent from
		$mail->setFrom( $from, 'First Last' );
		//Set an alternative reply-to address
		//$mail->addReplyTo('replyto@example.com', 'First Last');
		//Set who the message is to be sent to
		$mail->addAddress( $to, 'John Doe' );
		//Set the subject line
		$mail->Subject = $subject;
		//Read an HTML message body from an external file, convert referenced images to embedded,
		//convert HTML into a basic plain-text alternative body
		$mail->msgHTML( $message );
		//Replace the plain text body with one created manually
		//$mail->AltBody = 'This is a plain-text message body';
		//Attach an image file
		//$mail->addAttachment('images/phpmailer_mini.png');

		//send the message, check for errors
		if ( !$mail->send() ) {
			return $mail->ErrorInfo;
		} else {
			//echo "Message sent!";
		}
		return true;
	}
}
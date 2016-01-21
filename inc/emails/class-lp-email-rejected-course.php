<?php

/**
 * Class LP_Email_Rejected_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Email_Rejected_Course extends LP_Email {
	function __construct() {
		$this->id    = 'rejected_course';
		$this->title = __( 'Rejected course', 'learn_press' );

		$this->template_html  = 'emails/rejected-course.php';
		$this->template_plain = 'emails/plain/rejected-course.php';

		$this->default_subject = __( '[{site_title}] Your course {course_name} has rejected', 'learn_press' );
		$this->default_heading = __( 'Rejected course', 'learn_press' );


		parent::__construct();
	}

	function admin_options( $obj ) {
		$view = learn_press_get_admin_view( 'settings/emails/rejected-course.php' );
		include_once $view;
	}

	function trigger( $course_id, $user ) {

		if ( is_numeric( $user ) ) {
			$user = learn_press_get_user( $user );
		}

		$this->find['site_title']  = '{site_title}';
		$this->find['course_name'] = '{course_name}';

		$this->replace['site_title']  = $this->get_blogname();
		$this->replace['course_name'] = get_the_title( $course_id );

		$this->object = array(
			'course' => $course_id,
			'user'   => $user
		);

		if ( $user ) {
			$this->user_email = stripslashes( $user->user_email );
			$this->recipient = $this->user_email;
		}

		if ( ( $this->enable != 'yes' ) || !$this->get_recipient() ) {
			return;
		}
		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		return $return;
	}

	function get_content_html() {
		ob_start();
		learn_press_get_template( $this->template_html, array(
			'email_heading' => $this->get_heading(),
			'footer_text' 	=> $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course_name'   => get_the_title( $this->object['course'] ),
			'login_url'     => learn_press_get_login_url(),
			'user_name'		=> $this->object['user']->user_nicename,
			'plain_text'    => false
		) );
		return ob_get_clean();
	}

	function get_content_plain() {
		ob_start();
		learn_press_get_template( $this->template_plain, array(
			'email_heading' => $this->get_heading(),
			'footer_text' 	=> $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course_name'   => get_the_title( $this->object['course'] ),
			'login_url'     => learn_press_get_login_url(),
			'user_name'		=> $this->object['user']->user_nicename,
			'plain_text'    => true
		) );
		return ob_get_clean();
	}
}

return new LP_Email_Rejected_Course();
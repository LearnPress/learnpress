<?php

/**
 * Class LP_Email_New_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Email_New_Course extends LP_Email {
	function __construct() {
		$this->id    = 'new_course';
		$this->title = __( 'New course', 'learn_press' );

		$this->template_html  = 'emails/new-course.php';
		$this->template_plain = 'emails/plain/new-course.php';

		$this->default_subject = __( '[{site_title}] New course has submitted for review ({course_name})', 'learn_press' );
		$this->default_heading = __( 'New course', 'learn_press' );
		$this->recipient       = LP()->settings->get( 'emails_new_course.recipient' );
		parent::__construct();
	}

	function admin_options( $obj ) {
		$view = learn_press_get_admin_view( 'settings/emails/new-course.php' );
		include_once $view;
	}

	function trigger( $course_id, $user ) {

		$this->find['site_title']  = '{site_title}';
		$this->find['course_name'] = '{course_name}';

		$this->replace['site_title']  = $this->get_blogname();
		$this->replace['course_name'] = get_the_title( $course_id );

		$this->object = array(
			'course' => $course_id,
			'user'   => $user
		);

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
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course_id'     => $this->object['course'],
			'plain_text'    => false
		) );
		return ob_get_clean();
	}

	function get_content_plain() {
		ob_start();
		learn_press_get_template( $this->template_plain, array(
			'email_heading' => $this->get_heading(),
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course_id'     => $this->object['course'],
			'login_url'     => learn_press_get_login_url(),
			'plain_text'    => true
		) );
		return ob_get_clean();
	}
}

return new LP_Email_New_Course();
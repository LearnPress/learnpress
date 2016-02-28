<?php

/**
 * Class LP_Email_Published_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_Published_Course extends LP_Email {
	function __construct() {
		$this->id    = 'published_course';
		$this->title = __( 'Approved course', 'learnpress' );

		$this->template_html  = 'emails/published-course.php';
		$this->template_plain = 'emails/plain/published-course.php';

		$this->default_subject = __( '[{site_title}] Your course {course_name} has approved', 'learnpress' );
		$this->default_heading = __( 'Course approved', 'learnpress' );


		parent::__construct();
	}

	function admin_options( $settings_class ) {
		$view = learn_press_get_admin_view( 'settings/emails/published-course.php' );
		include_once $view;
	}

	function trigger( $course_id, $user ) {

		if ( !$this->enable ) {
			return;
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
			$this->recipient  = $this->user_email;
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
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course_id'     => $this->object['course'],
			'login_url'     => learn_press_get_login_url(),
			'user_name'     => $this->object['user']->user_nicename,
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
			'user_name'     => $this->object['user']->user_nicename,
			'plain_text'    => true
		) );
		return ob_get_clean();
	}
}

return new LP_Email_Published_Course();
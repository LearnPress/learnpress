<?php

/**
 * Class LP_Email_Enrolled_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_Become_An_Instructor extends LP_Email {
	public function __construct() {
		$this->id    = 'become_an_instructor';
		$this->title = __( 'Become an instructor', 'learnpress' );

		$this->template_html  = 'emails/enrolled-course.php';
		$this->template_plain = 'emails/plain/enrolled-course.php';

		$this->default_subject = __( '[{site_title}] You have enrolled course ({course_name})', 'learnpress' );
		$this->default_heading = __( 'Enrolled course', 'learnpress' );

		add_action( 'learn_press_user_enrolled_course_notification', array( $this, 'trigger' ), 99, 3 );

		parent::__construct();
	}

	public function admin_options( $settings_class ) {
		$view = learn_press_get_admin_view( 'settings/emails/enrolled-course.php' );
		include_once $view;
	}

	public function trigger( $user, $course_id, $user_course_id ) {
		if ( !$this->enable ) {
			return;
		}

		$this->recipient = $user->user_email;

		$this->find['site_title']  = '{site_title}';
		$this->find['course_name'] = '{course_name}';
		$this->find['course_date'] = '{course_date}';

		$this->replace['site_title']  = $this->get_blogname();
		$this->replace['course_name'] = get_the_title( $course_id );
		$this->replace['course_date'] = get_the_date( null, $course_id );

		$this->object = array(
			'course' => $course_id,
			'user'   => $user
		);

		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		return $return;
	}

	public function get_content_html() {
		ob_start();
		learn_press_get_template( $this->template_html, array(
			'email_heading' => $this->get_heading(),
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course_id'     => $this->object['course'],
			'login_url'     => learn_press_get_login_url(),
			'plain_text'    => false
		) );
		return ob_get_clean();
	}

	public function get_content_plain() {
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

return new LP_Email_Become_An_Instructor();
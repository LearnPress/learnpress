<?php

/**
 * Class LP_Email_Finished_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Email_Finished_Course extends LP_Email {
	function __construct() {
		$this->id    = 'finished_course';
		$this->title = __( 'Finished course', 'learn_press' );

		$this->template_html  = 'emails/finished-course.php';
		$this->template_plain = 'emails/plain/finished-course.php';

		$this->default_subject = __( '[{site_title}] You have finished course ({course_name})', 'learn_press' );
		$this->default_heading = __( 'Finished course', 'learn_press' );


		parent::__construct();
	}

	function admin_options( $settings_class ) {
		$view = learn_press_get_admin_view( 'settings/emails/finished-course.php' );
		include_once $view;
	}

	function trigger( $course_id, $user ) {

		if ( !$this->enable ) {
			return;
		}

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

	function get_content_html() {
		ob_start();
		learn_press_get_template( $this->template_html, array(
			'email_heading' => $this->get_heading(),
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
			'site_title'    => $this->get_blogname(),
			'course_id'     => $this->object['course'],
			'plain_text'    => true
		) );
		return ob_get_clean();
	}
}

return new LP_Email_Finished_Course();
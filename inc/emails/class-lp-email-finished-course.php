<?php

/**
 * Class LP_Email_Finished_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_Finished_Course extends LP_Email {
	function __construct() {
		$this->id    = 'finished_course';
		$this->title = __( 'Finished course', 'learnpress' );

		$this->template_html  = 'emails/finished-course.php';
		$this->template_plain = 'emails/plain/finished-course.php';

		$this->default_subject = __( '[{site_title}] You have finished course ({course_name})', 'learnpress' );
		$this->default_heading = __( 'Finished course', 'learnpress' );


		parent::__construct();
	}

	function admin_options( $settings_class ) {
		$view = learn_press_get_admin_view( 'settings/emails/finished-course.php' );
		include_once $view;
	}

	function trigger( $course_id, $user_id, $result ) {

		if ( !$this->enable || !( $user = learn_press_get_user( $user_id ) ) ) {
			return;
		}

		$this->find['site_title']  = '{site_title}';
		$this->find['course_name'] = '{course_name}';
		$this->find['course_date'] = '{course_date}';

		$this->replace['site_title']  = $this->get_blogname();
		$this->replace['course_name'] = get_the_title( $course_id );
		$this->replace['course_date'] = get_the_date( null, $course_id );

		$this->object = array(
			'course' => learn_press_get_course( $course_id ),
			'user'   => $user
		);

		$course = learn_press_get_course( $course_id );

		LP_Debug::instance()->add( $course->evaluate_course_results( $user_id ) );
		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		return $return;
	}

	function get_recipient() {
		if ( !empty( $this->object['user'] ) ) {
			$this->recipient = $this->object['user']->user_email;
		}
		return parent::get_recipient();
	}

	function get_content_html() {
		ob_start();
		learn_press_get_template( $this->template_html, $this->get_template_data( 'html' ) );
		return ob_get_clean();
	}

	function get_content_plain() {
		ob_start();
		learn_press_get_template( $this->template_plain, $this->get_template_data( 'plain' ) );
		return ob_get_clean();
	}

	function get_template_data( $content_type = 'plain' ) {
		return array(
			'email_heading' => $this->get_heading(),
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course_id'     => $this->object['course']->id,
			'profile_url'   => learn_press_user_profile_link( $this->object['user']->id ),
			'plain_text'    => $content_type == 'plain'
		);
	}
}

return new LP_Email_Finished_Course();
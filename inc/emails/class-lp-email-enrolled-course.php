<?php

/**
 * Class LP_Email_Enrolled_Course
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Email_Enrolled_Course extends LP_Email {
	/**
	 * LP_Email_Enrolled_Course constructor.
	 */
	public function __construct() {
		$this->id    = 'enrolled_course';
		$this->title = __( 'Enrolled course', 'learnpress' );

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

		global $wpdb;

		$user_course_data = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}learnpress_user_courses WHERE user_course_id = %d", $user_course_id )
		);

		if ( !$user_course_data ) {
			// TODO: ...
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
			'course' => learn_press_get_course( $course_id ),
			'user'   => learn_press_get_user( $user_course_data->user_id )
		);

		$return = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		return $return;
	}

	public function get_content_html() {
		ob_start();
		learn_press_get_template( $this->template_html, $this->get_template_data( 'html' ) );
		return ob_get_clean();
	}

	public function get_content_plain() {
		ob_start();
		learn_press_get_template( $this->template_plain, $this->get_template_data( 'plain' ) );
		return ob_get_clean();
	}

	public function get_template_data( $format = 'plain' ) {
		return array(
			'email_heading' => $this->get_heading(),
			'footer_text'   => $this->get_footer_text(),
			'site_title'    => $this->get_blogname(),
			'course'        => $this->object['course'],
			'user'          => $this->object['user'],
			'login_url'     => learn_press_get_login_url(),
			'plain_text'    => $format == 'plain'
		);
	}
}

return new LP_Email_Enrolled_Course();
<?php


class LP_Email_New_Course extends LP_Email {
	function __construct() {
		$this->id = 'new_course';

		$this->template_html  = 'emails/new-course.php';
		$this->template_plain = 'emails/plain/new-course.php';

		$this->subject = __( '[{site_title}] New course for review ({course_name}) - {course_date}', 'learn_press' );
		$this->heading = __( 'New course', 'learn_press' );



		parent::__construct();
	}

	function trigger( $course_id, $user ) {

		if(! $this->enable ){
			return;
		}

		$this->find['site_title']      = '{site_title}';
		$this->find['course_name']    = '{course_name}';
		$this->find['course_date']    = '{course_date}';

		$this->replace['site_title']   = $this->get_blogname();
		$this->replace['course_name'] = get_the_title( $course_id );
		$this->replace['course_date'] = get_the_date( $course_id );

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
			'email_heading'    => $this->get_heading(),
			'site_title'       => $this->get_blogname(),
			'course_name'      => get_the_title( $this->object['course'] ),
			'course_date'      => get_the_date( $this->object['course'] ),
			'course_link'      => get_the_permalink( $this->object['course'] ),
			'course_edit_link' => get_edit_post_link( $this->object['course'] ),
			'plain_text'       => false
		) );
		return ob_get_clean();
	}

	function get_content_plain() {
		ob_start();
		learn_press_get_template( $this->template_plain, array(
			'email_heading'    => $this->get_heading(),
			'site_title'       => $this->get_blogname(),
			'course_name'      => get_the_title( $this->object['course'] ),
			'course_date'      => get_the_date( $this->object['course'] ),
			'course_link'      => get_the_permalink( $this->object['course'] ),
			'course_edit_link' => get_edit_post_link( $this->object['course'] ),
			'plain_text'       => true
		) );
		return ob_get_clean();
	}
}

return new LP_Email_New_Course();
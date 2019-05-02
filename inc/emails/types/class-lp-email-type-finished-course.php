<?php

/**
 * Class LP_Email_Type_Finished_Course
 */
class LP_Email_Type_Finished_Course extends LP_Email {
	/**
	 * Course ID
	 *
	 * @var int
	 */
	public $course_id = 0;

	/**
	 * User ID
	 *
	 * @var int
	 */
	public $user_id = 0;

	/**
	 * User Item ID
	 *
	 * @var int
	 */
	public $user_item_id = 0;

	/**
	 * LP_Email_Type_Finished_Course constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->support_variables = array_merge(
			$this->general_variables,
			array(
				'{{course_id}}',
				'{{course_name}}',
				'{{course_url}}',
				'{{user_id}}',
				'{{user_name}}',
				'{{user_email}}',
				'{{course_grade}}',
				'{{course_result_percent}}',
			)
		);

		add_action( 'learn_press_user_enrolled_course_notification', array( $this, 'trigger' ), 99, 3 );
		add_action( 'learn-press/user-course-finished/notification', array( $this, 'trigger' ), 99, 3 );
	}

	public function get_object( $object_id = '', $more = '' ) {

		$user = learn_press_get_user( $this->user_id );
		if ( ! $user ) {
			print_r( $this );
			die();
		}
		wp_cache_delete( 'course-' . $user->get_id() . '-' . $this->course_id, 'lp-user-course-data' );
		LP_Object_Cache::delete( 'course-' . $this->course_id . '-' . $user->get_id(), 'course-results' );
		$course      = learn_press_get_course( $this->course_id );
		$course_data = $user->get_course_data( $this->course_id );
		$object      = array();

		if ( $course ) {
			$object = array_merge(
				$object,
				array(
					'course_id'   => $course->get_id(),
					'course_name' => $course->get_title(),
					'course_url'  => $course->get_permalink()
				)
			);
		}

		if ( $user ) {
			$object = array_merge(
				$object,
				array(
					'user_id'           => $user->get_id(),
					'user_name'         => $user->get_username(),
					'user_email'        => $user->get_email(),
					'user_display_name' => $user->get_display_name()
				)
			);
		}

		if ( $course_data ) {
			$object = array_merge(
				$object,
				array(
					'course_start_date'     => $course_data->get_start_time(),
					'course_grade'          => $course_data->get_grade( 'display' ),
					'course_result_percent' => $course_data->get_percent_result()
				)
			);
		}

		$this->object = $this->get_common_template_data(
			$this->email_format,
			$object
		);

		$this->get_variable();
	}

	/**
	 * Get instructor of the course.
	 *
	 * @return LP_User|mixed
	 */
	public function get_instructor() {
		return learn_press_get_user( get_post_field( 'post_author', $this->course_id ) );
	}

	/**
	 * @param int $course_id
	 * @param int $user_id
	 * @param int $user_item_id
	 */
	public function trigger( $course_id, $user_id, $user_item_id ) {
		if ( ! $this->enable ) {
			return;
		}

		$this->course_id    = $course_id;
		$this->user_id      = $user_id;
		$this->user_item_id = $user_item_id;

		LP_Emails::instance()->set_current( $this->id );
	}
}
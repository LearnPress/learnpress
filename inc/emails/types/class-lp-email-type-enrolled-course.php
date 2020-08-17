<?php
/**
 * LP_Email_Type_Enrolled_Course.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email
 * @version 3.0.9
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Email_Type_Enrolled_Course
 */
class LP_Email_Type_Enrolled_Course extends LP_Email {
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
	 * LP_Email_Type_Enrolled_Course constructor.
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
				'{{user_email}}'
			)
		);

		if ( LP()->settings->get( 'auto_enroll' ) == 'yes' ) {
			add_action( 'learn-press/order/status-completed', array( $this, 'auto_enroll_trigger' ), 10, 2 );
		}

		add_action( 'learn_press_user_enrolled_course_notification', array( $this, 'trigger' ), 99, 3 );
		add_action( 'learn-press/user-enrolled-course/notification', array( $this, 'trigger' ), 99, 3 );

	}

	/**
	 * @param string $object_id
	 * @param string $more
	 *
	 * @return array|object|void
	 */
	public function get_object( $object_id = '', $more = '' ) {

		$user   = learn_press_get_user( $this->user_id );
		$course = learn_press_get_course( $this->course_id );

		$object = array();

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

		if ( $course_data = $user->get_course_data( $this->course_id ) ) {
			$object['course_start_date'] = $course_data->get_start_time();
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

		$this->course_id    = $course_id;
		$this->user_id      = $user_id;
		$this->user_item_id = $user_item_id;

		LP_Emails::instance()->set_current( $this->id );
	}

	/**
	 * @param $order_id
	 */
	public function auto_enroll_trigger( $order_id, $status ) {

		if ( ! $this->enable ) {
			return;
		}

		$order   = learn_press_get_order( $order_id );
		$user    = $order->get_user();
		$courses = $order->get_items();

		if ( $courses ) {
			foreach ( $courses as $course ) {
				if ( ! isset( $course['course_id'] ) ) {
					return;
				}

				$course_id = $course['course_id'];

				$course_data = new LP_User_Item_Course( $course_id );
				$this->trigger( $course_id, $user->get_id(), $course_data->get_item_id() );
			}
		}
	}
}
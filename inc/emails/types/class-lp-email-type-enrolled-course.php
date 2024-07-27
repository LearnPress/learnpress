<?php
/**
 * LP_Email_Type_Enrolled_Course.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email
 * @version 3.0.9
 * @editor tungnx
 * @modify 4.1.3 - send email on background
 */

/**
 * Prevent loading this file directly
 */

use LearnPress\Models\UserItems\UserCourseModel;

defined( 'ABSPATH' ) || exit();

class LP_Email_Type_Enrolled_Course extends LP_Email {
	/**
	 * @var LP_Order
	 */
	protected $_order;
	/**
	 * @var LP_Course
	 */
	protected $_course;
	/**
	 * @var LP_User
	 */
	protected $_user;

	/**
	 * LP_Email_Type_Enrolled_Course constructor.
	 */
	public function __construct() {
		parent::__construct();

		$variable_on_email_support = apply_filters(
			'lp/email/enrolled-course/variables-support',
			[
				'{{course_id}}',
				'{{course_name}}',
				'{{course_url}}',
				'{{user_id}}',
				'{{user_name}}',
				'{{user_email}}',
				'{{user_display_name}}',
			]
		);

		$this->support_variables = array_merge( $this->support_variables, $variable_on_email_support );
	}

	/**
	 * Check email enable option
	 * Check param valid: 3 params: order_id, course_id, user_id
	 * Return Order
	 *
	 * @param array $params
	 * @return bool
	 */
	final function check_and_set( array $params ): bool {
		try {
			if ( count( $params ) < 3 ) {
				return false;
			}

			if ( ! $this->enable ) {
				return false;
			}

			$order_id  = $params[0] ?? 0;
			$course_id = $params[1] ?? 0;
			$user_id   = $params[2] ?? 0;

			$user = learn_press_get_user( $user_id );
			if ( ! $user ) {
				return false;
			}

			$filter = new LP_User_Items_Filter();
			$filter->user_id = $user_id;
			$filter->item_id = $course_id;
			$filter->item_type = LP_COURSE_CPT;
			$filter->ref_type = LP_ORDER_CPT;
			$filter->ref_id = $order_id;
			$userCourse = UserCourseModel::get_user_item_model_from_db( $filter );

			if ( LP_COURSE_ENROLLED != $userCourse->status ) {
				throw new Exception( 'User not enrolled course' );
			}

			$this->_order  = new LP_Order( $order_id );
			$this->_user   = $user;
			$this->_course = learn_press_get_course( $course_id );
		} catch ( Throwable $e ) {
			return false;
		}

		return true;
	}

	/**
	 * Set variables for content email.
	 * @editor tungnx
	 * @since 4.1.1
	 */
	protected function set_data_content() {
		$this->variables = apply_filters(
			'lp/email/type-enrolled-course/variables-mapper',
			[
				'{{course_id}}'         => $this->_course->get_id(),
				'{{course_name}}'       => $this->_course->get_title(),
				'{{course_url}}'        => $this->_course->get_permalink(),
				'{{user_id}}'           => $this->_user->get_id(),
				'{{user_name}}'         => $this->_user->get_username(),
				'{{user_email}}'        => $this->_user->get_email(),
				'{{user_display_name}}' => $this->_user->get_display_name(),
			]
		);

		$variables_common = $this->get_common_variables( $this->email_format );
		$this->variables  = array_merge( $this->variables, $variables_common );
	}
}

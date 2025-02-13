<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;

/**
 * Class LP_Email_Type_Finished_Course
 *
 * @editor tungnx
 * @modify 4.1.3
 * @version 3.0.2
 */
class LP_Email_Type_Finished_Course extends LP_Email {
	/**
	 * @var int $course_id
	 */
	public $course_id = 0;
	/**
	 * @var int $course_id
	 */
	public $user_id = 0;
	/**
	 * @var CourseModel
	 */
	public $courseModel;
	/**
	 * @var UserModel
	 */
	public $userModel;

	/**
	 * LP_Email_Type_Finished_Course constructor.
	 */
	public function __construct() {
		parent::__construct();

		$variable_on_email_support = apply_filters(
			'lp/email/type-finished-course/variables-support',
			[
				'{{course_id}}',
				'{{course_name}}',
				'{{course_url}}',
				'{{user_id}}',
				'{{user_name}}',
				'{{user_display_name}}',
				'{{user_email}}',
				'{{course_grade}}',
				'{{course_result_percent}}',
			]
		);

		$this->support_variables = array_merge( $this->support_variables, $variable_on_email_support );
	}

	/**
	 * Check email enable option
	 * Check param valid: 3 params: $course_id, $user_id, $user_item_id
	 * Set values
	 *
	 * @param array $params
	 * @return bool
	 * @throws Exception
	 */
	protected function check_and_set( array $params ): bool {
		if ( ! $this->enable ) {
			return false;
		}

		if ( count( $params ) < 2 ) {
			return false;
		}

		$course_id = $params[0];
		$user_id   = $params[1];

		$courseModel = CourseModel::find( $course_id, true );
		$userModel   = UserModel::find( $user_id, true );
		if ( ! $courseModel || ! $userModel ) {
			return false;
		}

		$this->course_id   = $course_id;
		$this->user_id     = $user_id;
		$this->courseModel = $courseModel;
		$this->userModel   = $userModel;

		return true;
	}

	/**
	 * Trigger email.
	 * Receive 3 params: $course_id, $user_id, $user_item_id
	 *
	 * @param array $params
	 * @throws Exception
	 * @since 4.1.1
	 * @version 1.0.1
	 */
	public function handle( array $params ) {
		if ( ! $this->check_and_set( $params ) ) {
			return;
		}

		$this->set_data_content();
		if ( $this instanceof LP_Email_Finished_Course_Instructor ) {
			$courseModel = $this->courseModel;
			if ( $courseModel instanceof CourseModel ) {
				$authorModel = $courseModel->get_author_model();
				if ( $authorModel instanceof UserModel && ! empty( $authorModel->get_email() ) ) {
					$this->set_receive( $authorModel->get_email() );
				}
			}
		} elseif ( $this instanceof LP_Email_Finished_Course_User ) {
			$userModel = $this->userModel;
			if ( $userModel instanceof UserModel && ! empty( $userModel->get_email() ) ) {
				$this->set_receive( $userModel->get_email() );
			}
		}

		$this->send_email();
	}

	/**
	 * Set variables for content email.
	 * @editor tungnx
	 * @since 4.1.3
	 * @version 1.0.1
	 */
	public function set_data_content() {
		$courseModel = $this->courseModel;
		$userModel   = $this->userModel;
		if ( ! $courseModel instanceof CourseModel
			|| ! $userModel instanceof UserModel ) {
			return;
		}

		$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
		if ( ! $userCourseModel ) {
			return;
		}

		$userCourseResult = $userCourseModel->calculate_course_results();

		$this->variables = apply_filters(
			'lp/email/type-finished-course/variables-mapper',
			[
				'{{course_id}}'             => $courseModel->get_id(),
				'{{course_name}}'           => $courseModel->get_title(),
				'{{course_url}}'            => $courseModel->get_permalink(),
				'{{user_id}}'               => $userModel->get_id(),
				'{{user_name}}'             => $userModel->get_username(),
				'{{user_display_name}}'     => $userModel->get_display_name(),
				'{{user_email}}'            => $userModel->get_email(),
				'{{course_grade}}'          => $userCourseModel->get_string_i18n( $userCourseModel->get_graduation() ),
				'{{course_result_percent}}' => $userCourseResult['result'],
			]
		);

		$variables_common = $this->get_common_variables( $this->email_format );
		$this->variables  = array_merge( $this->variables, $variables_common );
	}
}

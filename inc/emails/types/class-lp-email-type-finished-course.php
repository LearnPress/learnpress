<?php

/**
 * Class LP_Email_Type_Finished_Course
 *
 * @editor tungnx
 * @modify 4.1.3
 * @version 3.0.1
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

		if ( count( $params ) < 3 ) {
			return false;
		}

		$this->course_id    = $params[0];
		$this->user_id      = $params[1];
		$this->user_item_id = $params[2];

		return true;
	}

	/**
	 * Trigger email.
	 * Receive 3 params: $course_id, $user_id, $user_item_id
	 *
	 * @param array $params
	 * @throws Exception
	 * @since 4.1.1
	 * @author tungnx
	 */
	public function handle( array $params ) {
		if ( ! $this->check_and_set( $params ) ) {
			return;
		}

		$this->set_data_content();
		if ( $this instanceof LP_Email_Finished_Course_Instructor ) {
			$instructor = learn_press_get_user( get_post_field( 'post_author', $this->course_id ) );
			if ( $instructor && ! empty( $instructor->get_email() ) ) {
				$this->set_receive( $instructor->get_email() );
			}
		} elseif ( $this instanceof LP_Email_Finished_Course_User ) {
			$user = learn_press_get_user( $this->user_id );
			if ( $user && ! empty( $user->get_email() ) ) {
				$this->set_receive( $user->get_email() );
			}
		}
		$this->send_email();
	}

	/**
	 * Set variables for content email.
	 * @editor tungnx
	 * @since 4.1.3
	 */
	public function set_data_content() {
		$course = learn_press_get_course( $this->course_id );
		$user   = learn_press_get_user( $this->user_id );
		if ( ! $course || ! $user ) {
			return;
		}

		$user_course_data = $user->get_course_data( $this->course_id );
		if ( ! $user_course_data ) {
			return;
		}

		$this->variables = apply_filters(
			'lp/email/type-finished-course/variables-mapper',
			[
				'{{course_id}}'             => $this->course_id,
				'{{course_name}}'           => $course->get_title(),
				'{{course_url}}'            => $course->get_permalink(),
				'{{user_id}}'               => $this->user_id,
				'{{user_name}}'             => $user->get_username(),
				'{{user_display_name}}'     => $user->get_display_name(),
				'{{user_email}}'            => $user->get_email(),
				'{{course_grade}}'          => $user_course_data->get_graduation( 'display' ),
				'{{course_result_percent}}' => $user_course_data->get_percent_result( 2 ),
			]
		);

		$variables_common = $this->get_common_variables( $this->email_format );
		$this->variables  = array_merge( $this->variables, $variables_common );
	}
}

<?php

/**
 * Class UserLessonModel
 *
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 4.2.7.6
 */

namespace LearnPress\Models\UserItems;

use Exception;
use LearnPress\Models\CourseModel;
use LearnPress\Models\LessonPostModel;
use LP_Datetime;
use WP_Error;

class UserLessonModel extends UserItemModel {
	/**
	 * Item type Lesson
	 *
	 * @var string Item type
	 */
	public $item_type = LP_LESSON_CPT;
	/**
	 * Ref type Course
	 *
	 * @var string
	 */
	public $ref_type = LP_COURSE_CPT;

	public function __construct( $data = null ) {
		parent::__construct( $data );
	}

	/**
	 * Get lesson model
	 *
	 * @return bool|LessonPostModel
	 * @since 4.2.5
	 * @version 1.0.1
	 */
	public function get_lesson_post_model() {
		return LessonPostModel::find( $this->item_id, true );
	}

	/**
	 * Get course model
	 *
	 * @return false|CourseModel
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function get_course_model() {
		return CourseModel::find( $this->ref_id, true );
	}

	/**
	 * Get user course model
	 *
	 * @return false|UserCourseModel
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function get_user_course_model() {
		return UserCourseModel::find( $this->user_id, $this->ref_id, true );
	}

	/**
	 * Complete lesson
	 *
	 * @throws Exception
	 * @version 1.0.1
	 * @since 4.2.7.6
	 */
	public function set_complete() {
		if ( $this->get_status() === self::STATUS_COMPLETED ) {
			throw new Exception( __( 'Lesson is already completed.', 'learnpress' ) );
		}

		$userCourseModel = $this->get_user_course_model();
		if ( ! $userCourseModel ) {
			throw new Exception( __( 'You have not started course', 'learnpress' ) );
		}

		$can_impact_lesson = $userCourseModel->can_impact_item();
		if ( $can_impact_lesson instanceof WP_Error ) {
			throw new Exception( $can_impact_lesson->get_error_message() );
		}

		$this->status     = self::STATUS_COMPLETED;
		$this->end_time   = gmdate( LP_Datetime::$format, time() );
		$this->graduation = self::GRADUATION_PASSED;
		$this->save();

		do_action( 'learn-press/user-completed-lesson', $this->item_id, $this->ref_id, $this->user_id );
		do_action( 'learn-press/user-lesson/completed', $this );
	}
}

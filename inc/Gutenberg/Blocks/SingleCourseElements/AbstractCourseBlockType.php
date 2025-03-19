<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
abstract class AbstractCourseBlockType extends AbstractBlockType {
	/**
	 * Get course model
	 *
	 * @param $attributes
	 *
	 * @return false|CourseModel
	 */
	public function get_course( $attributes ) {
		$courseId = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
		return CourseModel::find( $courseId, true );
	}

	/**
	 * Get user model
	 *
	 * @return false|UserModel
	 */
	public function get_user() {
		$userId = get_current_user_id();
		return UserModel::find( $userId, true );
	}
}

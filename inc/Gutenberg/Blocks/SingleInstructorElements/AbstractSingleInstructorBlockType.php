<?php
namespace LearnPress\Gutenberg\Blocks\SingleInstructorElements;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;

/**
 * Class AbstractSingleInstructorBlockType
 *
 * Handle register, render block template
 */
abstract class AbstractSingleInstructorBlockType extends AbstractBlockType {
	/**
	 * @var string Screen Template want to display - Field of LP
	 */
	// public $display_on_templates = [
	// 	'lp-instructor',
	// ];
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

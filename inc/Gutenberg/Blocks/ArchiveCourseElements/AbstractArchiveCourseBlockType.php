<?php
namespace LearnPress\Gutenberg\Blocks\ArchiveCourseElements;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;

/**
 * Class AbstractArchiveCourseBlockType
 *
 * Handle register, render block template
 */
abstract class AbstractArchiveCourseBlockType extends AbstractBlockType {
	/**
	 * @var string Screen Template want to display - Field of LP
	 */
	public $display_on_templates = [
		'learnpress/learnpress//archive-lp_course',
		'learnpress/learnpress//taxonomy-course_tag',
		'learnpress/learnpress//taxonomy-course_category',
	];
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

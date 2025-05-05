<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use WP_Block;

/**
 * Class AbstractCourseBlockType
 *
 * Handle register, render block template
 */
abstract class AbstractCourseBlockType extends AbstractBlockType {
	/**
	 * @var string Screen Template want to display - Field of LP
	 */
	/*public $display_on_templates = [
		'learnpress/learnpress//single-lp_course',
	];*/

	/**
	 * Show block when block parent is course item template, single course active
	 * @return string[]
	 */
	/*public function get_ancestor() {
		return [ 'learnpress/single-course', 'learnpress/course-item-template' ];
	}*/

	/**
	 * Get course model
	 *
	 * @param $attributes
	 * @param WP_Block|null $block
	 *
	 * @return false|CourseModel
	 */
	public function get_course( $attributes, $block = null ) {
		if ( $block instanceof WP_Block ) {
			$courseModel = $block->context['courseModel'] ?? false;
			if ( $courseModel instanceof CourseModel ) {
				return $courseModel;
			}
		}

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

<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourseItemElements;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use WP_Block;

/**
 * Class AbstractCourseItemBlockType
 *
 * Handle register, render block template
 */
abstract class AbstractCourseItemBlockType extends AbstractBlockType {
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

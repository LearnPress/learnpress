<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;
use LearnPress\Helpers\Template;
use LP_Global;
use LP_Debug;
use Throwable;

/**
 * Class CourseItemCurriculumBlockType
 *
 * Handle register, render block template
 */
class CourseItemCurriculumBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-item-curriculum';

	public function get_ancestor() {
		return [ 'learnpress/single-course' ];
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		try {
			global $post;
			setup_postdata( $post );
			$course_item = LP_Global::course_item();
			if ( ! $course_item ) {
				return $html;
			}
			$html = Template::instance()->get_frontend_template( 'content-single-item.php' );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

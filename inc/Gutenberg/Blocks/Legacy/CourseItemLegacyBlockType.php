<?php

namespace LearnPress\Gutenberg\Blocks\Legacy;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Helpers\Template;
use LP_Global;
use LP_Debug;
use Throwable;

/**
 * Class CourseItemLegacyBlockType
 *
 * Handle register, render block template
 */
class CourseItemLegacyBlockType extends AbstractBlockType {
	public $block_name = 'course-item-curriculum';

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
			ob_start();
			Template::instance()->get_frontend_template( 'content-single-item.php' );
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LP_Debug;
use Throwable;
use WP_Block;
use WP_Query;

/**
 * Class SingleCourseBlock
 *
 * Handle register, render block template
 */
class CourseItemTemplateBlock extends AbstractBlockType {
	public $name = 'course-item-template';

	public function get_attributes() {
		return [ 'columns' ];
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
			$content = '';

			$wrapper_attributes = get_block_wrapper_attributes();

			$block_instance = $block->parsed_block;

			$content = $block->render( [ 'dynamic' => false ] );

			return sprintf(
				'<li %1$s>%2$s</li>',
				$wrapper_attributes,
				$content
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class ListCoursesBlockType
 *
 */
class ListCoursesBlockType extends AbstractBlockType {
	public $name            = 'list-courses';
	public $source_js       = LP_PLUGIN_URL . 'assets/js/dist/blocks/list-courses.js';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/courses/list-courses';

/*	public function get_supports(): array {
		return [
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [ 'fontSize' => true ],
		];
	}*/

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

			$wrapper = get_block_wrapper_attributes();
			ob_start();
			echo sprintf(
				'<div %s>%s</div>',
				$wrapper,
				'List Courses'
			);
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

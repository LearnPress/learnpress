<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourse;
use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;
/**
 * Class SingleCourseBlockType
 *
 * Handle register, render block template
 */
class SingleCourseBlockType extends AbstractBlockType {
	public $block_name = 'single-course';

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
			// Set temporary.
			$this->get_output( $content );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

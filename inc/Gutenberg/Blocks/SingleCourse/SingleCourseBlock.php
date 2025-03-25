<?php
namespace LearnPress\Gutenberg\Blocks\SingleCourse;
use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LP_Debug;
use Throwable;
/**
 * Class SingleCourseBlock
 *
 * Handle register, render block template
 */
class SingleCourseBlock extends AbstractBlockType {
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
			ob_start();
			echo sprintf(
				'<div class="lp-single-course"> %s </div>',
				$content
			);
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

<?php
namespace LearnPress\Gutenberg\Blocks\SingleInstructor;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LP_Debug;
use Throwable;
/**
 * Class SingleInstructorBlockType
 *
 * Handle register, render block template
 */
class SingleInstructorBlockType extends AbstractBlockType {
	public $block_name = 'single-instructor';

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		$class_name = $attributes['className'] ?? '';

		try {
			$html = sprintf(
				'<article class="lp-content-area %s"> <div class="lp-single-instructor"> %s </div> </article>',
				$class_name,
				$content
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

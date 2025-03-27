<?php
namespace LearnPress\Gutenberg\Blocks\ArchiveCourse;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LP_Debug;
use Throwable;
/**
 * Class ArchiveCourseBlockType
 *
 * Handle register, render block template
 */
class ArchiveCourseBlockType extends AbstractBlockType {
	public $block_name = 'archive-course';

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
			ob_start();
			echo sprintf(
				'<div class="lp-archive-courses %s"> %s </div>',
				$class_name,
				$content,
			);
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\Legacy;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Settings;

/**
 * Class ArchiveCourseBlockLegacy
 *
 * Handle register, render block template
 */
class ArchiveCourseBlockLegacy extends AbstractBlockType {
	public $block_name           = 'archive-course-legacy';
	/*public $display_on_templates = [
		'learnpress/learnpress//archive-lp_course',
		'learnpress/learnpress//taxonomy-course_tag',
		'learnpress/learnpress//taxonomy-course_category',
	];*/

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		wp_enqueue_style( 'learnpress' );
		$page_template = 'archive-course.php';

		ob_start();
		Template::instance()->get_frontend_template( $page_template, compact( 'attributes' ) );
		$html = ob_get_clean();

		return $html;
	}
}

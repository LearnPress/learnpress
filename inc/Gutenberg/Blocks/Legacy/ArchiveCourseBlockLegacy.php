<?php

namespace LearnPress\Gutenberg\Blocks\Legacy;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Settings;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class ArchiveCourseBlockLegacy extends AbstractBlockType {
	public $name                 = 'archive-course-legacy';
	public $source_js            = LP_PLUGIN_URL . 'assets/js/dist/blocks/archive-course-legacy.js';
	public $display_on_templates = [ 'learnpress/learnpress//archive-lp_course' ];

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$page_template = 'archive-course.php';

		ob_start();
		Template::instance()->get_frontend_template( $page_template, compact( 'attributes' ) );
		$html = ob_get_clean();

		return $html;
	}
}

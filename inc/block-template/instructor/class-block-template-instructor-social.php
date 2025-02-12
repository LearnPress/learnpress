<?php

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor_Social
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Social extends Abstract_Block_Template {
	public $slug        = 'instructor-social';
	public $name        = 'learnpress/instructor-social';
	public $title       = 'Instructor - Social (LearnPress)';
	public $description = 'Instructor Social Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-social.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return;
			}
			ob_start();
			echo SingleInstructorTemplate::instance()->html_social( $instructor );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

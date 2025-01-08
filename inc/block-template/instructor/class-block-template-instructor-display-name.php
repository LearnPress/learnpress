<?php

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor_Display_Name
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Display_Name extends Abstract_Block_Template {
	public $slug        = 'instructor-display-name';
	public $name        = 'learnpress/instructor-display-name';
	public $title       = 'Instructor - Display Name (LearnPress)';
	public $description = 'Instructor Display Name Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-display-name.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->can_create_course() ) {
				return;
			}
			ob_start();
			echo sprintf( '<h2>%s</h2>', SingleInstructorTemplate::instance()->html_display_name( $instructor ) );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

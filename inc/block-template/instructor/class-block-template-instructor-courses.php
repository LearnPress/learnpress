<?php

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Courses extends Abstract_Block_Template {
	public $slug        = 'instructor-courses';
	public $name        = 'learnpress/instructor-courses';
	public $title       = 'Instructor - Courses (LearnPress)';
	public $description = 'Instructor Courses Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-courses.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return;
			}
			ob_start();
			echo SingleInstructorTemplate::instance()->section_list_courses( $instructor );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

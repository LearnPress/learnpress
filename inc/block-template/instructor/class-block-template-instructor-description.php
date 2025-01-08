<?php

use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;

/**
 * Class Block_Template_Instructor_Description
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Description extends Abstract_Block_Template {
	public $slug        = 'instructor-description';
	public $name        = 'learnpress/instructor-description';
	public $title       = 'Instructor - Description (LearnPress)';
	public $description = 'Instructor Description Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-description.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->can_create_course() ) {
				return;
			}
			ob_start();
			echo SingleInstructorTemplate::instance()->html_description( $instructor );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

<?php

use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;

/**
 * Class Block_Template_Instructor_Avatar
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Avatar extends Abstract_Block_Template {
	public $slug        = 'instructor-avatar';
	public $name        = 'learnpress/instructor-avatar';
	public $title       = 'Instructor - Avatar (LearnPress)';
	public $description = 'Instructor Avatar Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-avatar.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return;
			}
			ob_start();
			echo SingleInstructorTemplate::instance()->html_avatar( $instructor );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

<?php

use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;

/**
 * Class Block_Template_Instructor_Background
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Background extends Abstract_Block_Template {
	public $slug        = 'instructor-background';
	public $name        = 'learnpress/instructor-background';
	public $title       = 'Instructor - Background (LearnPress)';
	public $description = 'Instructor Background Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-background.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();
			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return $content;
			}

			$userModel = UserModel::find( $instructor->get_id(), true );
			ob_start();
			echo ProfileTemplate::instance()->html_cover_image( $userModel );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

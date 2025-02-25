<?php

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor_Total_Students
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Total_Students extends Abstract_Block_Template {
	public $slug        = 'instructor-total-students';
	public $name        = 'learnpress/instructor-total-students';
	public $title       = 'Instructor - Total Students (LearnPress)';
	public $description = 'Instructor Total Students Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-total-students.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return;
			}
			ob_start();
			$html_wrapper = [
				'wrapper'     => '<div class="wrapper-instructor-total-students">',
				'span'        => '<span class="lp-ico lp-icon-students">',
				'content'     => SingleInstructorTemplate::instance()->html_count_students( $instructor ),
				'end_span'    => '</span>',
				'end_wrapper' => '</div>',
			];
			$html         = Template::combine_components( $html_wrapper );
			echo $html;
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

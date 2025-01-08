<?php

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor_Total_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Total_Courses extends Abstract_Block_Template {
	public $slug        = 'instructor-total-courses';
	public $name        = 'learnpress/instructor-total-courses';
	public $title       = 'Instructor - Total Courses (LearnPress)';
	public $description = 'Instructor Total Courses Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-total-courses.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->can_create_course() ) {
				return;
			}
			ob_start();
			$html_wrapper = [
				'wrapper'     => '<div class="wrapper-instructor-total-courses">',
				'span'        => '<span class="lp-ico lp-icon-courses">',
				'content'     => SingleInstructorTemplate::instance()->html_count_courses( $instructor ),
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

<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Related_Course
 *
 * Handle register, render block template
 */

class Block_Template_Related_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'related-single-lp_course';
	public $name                          = 'learnpress/related-course';
	public $title                         = 'Related Course (LearnPress)';
	public $description                   = 'Related Course Block Template';
	public $path_html_block_template_file = 'html/single-course/related-single-course.html';
	public $single_course_func            = 'html_related';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/related-course.js';

	public function render_content_block_template( array $attributes ) {
		$content   = '';
		$course_id = get_the_ID();
		if ( $course_id ) {
			ob_start();
			$course = CourseModel::find( $course_id, true );
			do_action( 'learn-press/single-course/courses-related/layout', $course, 4 );
			$content = ob_get_clean();
		}

		return $content;
	}
}

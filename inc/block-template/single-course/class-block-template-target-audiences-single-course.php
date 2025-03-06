<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Target_Audiences_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Target_Audiences_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'target-audiences-single-course';
	public $name                          = 'learnpress/target-audiences-single-course';
	public $title                         = 'Target audiences Course (LearnPress)';
	public $description                   = 'Target audiences Course Block Template';
	public $path_html_block_template_file = 'html/single-course/target-audiences-single-course.html';
	public $single_course_func            = 'html_course_box_extra';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/target-audiences-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$course              = CourseModel::find( get_the_ID(), true );
		if ( ! $course ) {
			return;
		}
		ob_start();
		echo SingleCourseTemplate::instance()->html_target( $course );
		$content = ob_get_clean();
		return $content;
	}
}

<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Features_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Features_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'features-single-course';
	public $name                          = 'learnpress/features-single-course';
	public $title                         = 'Features Course (LearnPress)';
	public $description                   = 'Features Course Block Template';
	public $path_html_block_template_file = 'html/single-course/features-single-course.html';
	public $single_course_func            = 'html_course_box_extra';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/features-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';
		$course  = CourseModel::find( get_the_ID(), true );
		ob_start();
		echo SingleCourseTemplate::instance()->html_features( $course );
		$content = ob_get_clean();

		return $content;
	}
}

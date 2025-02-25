<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Curriculum_Single_Course
 *
 * Handle register, render block template
 */

class Block_Template_Curriculum_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'curriculum-single-lp_course';
	public $name                          = 'learnpress/curriculum-single-course';
	public $title                         = 'Curriculum Course (LearnPress)';
	public $description                   = 'Curriculum Course Block Template';
	public $path_html_block_template_file = 'html/single-course/curriculum-single-course.html';
	public $single_course_func            = 'html_curriculum';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/curriculum-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$user                 = UserModel::find( get_current_user_id(), true );
		$course               = CourseModel::find( get_the_ID(), true );
		$singleCourseTemplate = SingleCourseTemplate::instance();
		ob_start();
		echo $singleCourseTemplate->html_curriculum( $course, $user );
		$content = ob_get_clean();

		return $content;
	}
}

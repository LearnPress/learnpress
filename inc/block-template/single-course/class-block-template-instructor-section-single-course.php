<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Instructor_Section_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Section_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'instructor-section-single-lp_course';
	public $name                          = 'learnpress/instructor-section-single-course';
	public $title                         = 'Instructor Section (LearnPress)';
	public $description                   = 'Instructor Section Block Template';
	public $path_html_block_template_file = 'html/single-course/instructor-section-single-course.html';
	public $single_course_func            = 'html_instructor_section';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-section-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$singleCourseClassicTemplate = SingleCourseModernLayout::instance();
		$course                      = CourseModel::find( get_the_ID(), true );
		$user                        = UserModel::find( get_current_user_id(), true );
		ob_start();
		echo $singleCourseClassicTemplate->html_instructor_section( $course, $user );
		$content = ob_get_clean();

		return $content;
	}
}

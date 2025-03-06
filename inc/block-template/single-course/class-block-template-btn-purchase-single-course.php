<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Btn_Purchase_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Btn_Purchase_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'btn-purchase-single-lp_course';
	public $name                          = 'learnpress/btn-purchase-single-course';
	public $title                         = 'Button Purchase Course (LearnPress)';
	public $description                   = 'Button Purchase Course Block Template';
	public $path_html_block_template_file = 'html/single-course/btn-purchase-single-course.html';
	public $single_course_func            = 'html_btn';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/btn-purchase-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';
		$course  = CourseModel::find( get_the_ID(), true );
		$user    = UserModel::find( get_current_user_id(), true );
		ob_start();
		echo SingleCourseModernLayout::instance()->html_button( $course, $user );
		$content = ob_get_clean();

		return $content;
	}
}

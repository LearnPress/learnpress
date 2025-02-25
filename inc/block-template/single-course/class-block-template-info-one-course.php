<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Info_One_Course
 *
 * Handle register, render block template
 */
class Block_Template_Info_One_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'info-one-course';
	public $name                          = 'learnpress/info-one-course';
	public $title                         = 'Info One Course (LearnPress)';
	public $description                   = 'Info One Course Block Template';
	public $path_html_block_template_file = 'html/single-course/info-one-course.html';
	public $single_course_func            = 'html_info_one_course';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/info-one-course.js';

	public function render_content_block_template( array $attributes ) {
		$singleCourseClassicTemplate = SingleCourseModernLayout::instance();
		$course                      = CourseModel::find( get_the_ID(), true );
		$user                        = UserModel::find( get_current_user_id(), true );
		ob_start();
		echo $singleCourseClassicTemplate->html_info_one( $course, $user );
		$content = ob_get_clean();

		return $content;
	}
}

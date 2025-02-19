<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Share_Course
 *
 * Handle register, render block template
 */
class Block_Template_Share_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'share-course';
	public $name                          = 'learnpress/share-course';
	public $title                         = 'Share Course (LearnPress)';
	public $description                   = 'Share Course Block Template';
	public $path_html_block_template_file = 'html/single-course/share-course.html';
	public $single_course_func            = 'html_share';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/share-course.js';

	public function render_content_block_template( array $attributes ) {
		$singleCourseClassicTemplate = SingleCourseModernLayout::instance();
		$course                      = CourseModel::find( get_the_ID(), true );
		ob_start();
		echo $singleCourseClassicTemplate->html_share( $course );
		$content = ob_get_clean();

		return $content;
	}
}

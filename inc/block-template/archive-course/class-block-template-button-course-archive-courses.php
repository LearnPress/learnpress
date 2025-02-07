<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Button_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Button_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'button-course-archive-course';
	public $name                          = 'learnpress/button-course-archive-course';
	public $title                         = 'Button Course (LearnPress)';
	public $description                   = 'Button Course Block Template';
	public $path_html_block_template_file = 'html/list-course/button-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/button-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{button-course}}';
		return $output;
	}
}

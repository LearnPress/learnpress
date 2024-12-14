<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Title_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Title_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'title-course-archive-course';
	public $name                          = 'learnpress/title-course-archive-course';
	public $title                         = 'Title Course (LearnPress)';
	public $description                   = 'Title Course Block Template';
	public $path_html_block_template_file = 'html/list-course/title-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/title-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{title-course}}';
		return $output;
	}
}

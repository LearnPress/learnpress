<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Duration_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Duration_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'duration-course-archive-course';
	public $name                          = 'learnpress/duration-course-archive-course';
	public $title                         = 'Duration Course (LearnPress)';
	public $description                   = 'Duration Course Block Template';
	public $path_html_block_template_file = 'html/list-course/duration-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/duration-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{duration-course}}';
		return $output;
	}
}

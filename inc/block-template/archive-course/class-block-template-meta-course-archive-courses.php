<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Meta_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Meta_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'meta-course-archive-course';
	public $name                          = 'learnpress/meta-course-archive-course';
	public $title                         = 'Meta Course (LearnPress)';
	public $description                   = 'Meta Course Block Template';
	public $path_html_block_template_file = 'html/list-course/meta-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/meta-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{meta-course}}';
		return $output;
	}
}

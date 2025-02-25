<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Author_Filter_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Author_Filter_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'author-filter-archive-course';
	public $name                          = 'learnpress/author-filter-archive-course';
	public $title                         = 'Author Filter (LearnPress)';
	public $description                   = 'Author Filter Block Template';
	public $path_html_block_template_file = 'html/list-course/author-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/author-filter-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = 'author';
		return $output;
	}
}

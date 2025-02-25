<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Search_Filter_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Search_Filter_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'search-filter-archive-course';
	public $name                          = 'learnpress/search-filter-archive-course';
	public $title                         = 'Search Filter (LearnPress)';
	public $description                   = 'Search Filter Block Template';
	public $path_html_block_template_file = 'html/list-course/search-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/search-filter-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = 'search';
		return $output;
	}
}

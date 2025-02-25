<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Category_Filter_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Category_Filter_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'category-filter-archive-course';
	public $name                          = 'learnpress/category-filter-archive-course';
	public $title                         = 'Category Filter (LearnPress)';
	public $description                   = 'Category Filter Block Template';
	public $path_html_block_template_file = 'html/list-course/category-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/category-filter-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = 'category';
		return $output;
	}
}

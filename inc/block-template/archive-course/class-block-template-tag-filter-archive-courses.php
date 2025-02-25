<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Tag_Filter_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Tag_Filter_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'tag-filter-archive-course';
	public $name                          = 'learnpress/tag-filter-archive-course';
	public $title                         = 'Tag Filter (LearnPress)';
	public $description                   = 'Tag Filter Block Template';
	public $path_html_block_template_file = 'html/list-course/tag-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/tag-filter-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = 'tag';
		return $output;
	}
}

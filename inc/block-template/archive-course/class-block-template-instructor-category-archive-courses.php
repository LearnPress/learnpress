<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Instructor_Category_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Category_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'instructor-category-archive-course';
	public $name                          = 'learnpress/instructor-category-archive-course';
	public $title                         = 'Instructor Category (LearnPress)';
	public $description                   = 'Instructor Category Block Template';
	public $path_html_block_template_file = 'html/list-course/instructor-category-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-category-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{instructor-category}}';
		return $output;
	}
}

<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Description_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Description_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'description-course-archive-course';
	public $name                          = 'learnpress/description-course-archive-course';
	public $title                         = 'Description Course (LearnPress)';
	public $description                   = 'Description Course Block Template';
	public $path_html_block_template_file = 'html/list-course/description-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/description-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{description-course}}';
		return $output;
	}
}

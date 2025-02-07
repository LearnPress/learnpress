<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Price_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Price_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'price-course-archive-course';
	public $name                          = 'learnpress/price-course-archive-course';
	public $title                         = 'Price Course (LearnPress)';
	public $description                   = 'Price Course Block Template';
	public $path_html_block_template_file = 'html/list-course/price-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/price-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{price-course}}';
		return $output;
	}
}

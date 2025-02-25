<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Level_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Level_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'level-course-archive-course';
	public $name                          = 'learnpress/level-course-archive-course';
	public $title                         = 'Level Course (LearnPress)';
	public $description                   = 'Level Course Block Template';
	public $path_html_block_template_file = 'html/list-course/level-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/level-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{level-course}}';
		return $output;
	}
}

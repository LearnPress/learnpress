<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Student_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Student_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'student-course-archive-course';
	public $name                          = 'learnpress/student-course-archive-course';
	public $title                         = 'Student Course (LearnPress)';
	public $description                   = 'Student Course Block Template';
	public $path_html_block_template_file = 'html/list-course/student-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/student-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{student-course}}';
		return $output;
	}
}

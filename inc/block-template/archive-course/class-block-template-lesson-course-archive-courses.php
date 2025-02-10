<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Lesson_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Lesson_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'lesson-course-archive-course';
	public $name                          = 'learnpress/lesson-course-archive-course';
	public $title                         = 'Lesson Course (LearnPress)';
	public $description                   = 'Lesson Course Block Template';
	public $path_html_block_template_file = 'html/list-course/lesson-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lesson-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{lesson-course}}';
		return $output;
	}
}

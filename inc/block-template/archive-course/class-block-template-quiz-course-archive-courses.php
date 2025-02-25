<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Quiz_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Quiz_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'quiz-course-archive-course';
	public $name                          = 'learnpress/quiz-course-archive-course';
	public $title                         = 'Quiz Course (LearnPress)';
	public $description                   = 'Quiz Course Block Template';
	public $path_html_block_template_file = 'html/list-course/quiz-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/quiz-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$output = '{{quiz-course}}';
		return $output;
	}
}

<?php
/**
 * Class Block_Template_Progress_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Progress_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'progress-single-course';
	public $name                          = 'learnpress/progress-single-course';
	public $title                         = 'Progress Single Course (LearnPress)';
	public $description                   = 'Progress Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/progress-single-course.html';
	public $single_course_func            = 'html_progress';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/progress-single-course.js';
}

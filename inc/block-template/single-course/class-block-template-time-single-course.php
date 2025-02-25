<?php
/**
 * Class Block_Template_Time_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Time_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'time-single-course';
	public $name                          = 'learnpress/time-single-course';
	public $title                         = 'Time Single Course (LearnPress)';
	public $description                   = 'Time Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/time-single-course.html';
	public $single_course_func            = 'html_time';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/time-single-course.js';
}

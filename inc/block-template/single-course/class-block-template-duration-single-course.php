<?php
/**
 * Class Block_Template_Duration_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Duration_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'duration-single-lp_course';
	public $name                          = 'learnpress/duration-single-course';
	public $title                         = 'Duration Course (LearnPress)';
	public $description                   = 'Duration Course Block Template';
	public $path_html_block_template_file = 'html/single-course/duration-single-course.html';
	public $single_course_func            = 'html_duration';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/duration-single-course.js';
}

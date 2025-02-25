<?php
/**
 * Class Block_Template_Level_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Level_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'level-single-lp_course';
	public $name                          = 'learnpress/level-single-course';
	public $title                         = 'Level Course (LearnPress)';
	public $description                   = 'Level Course Block Template';
	public $path_html_block_template_file = 'html/single-course/level-single-course.html';
	public $single_course_func            = 'html_level';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/level-single-course.js';
}

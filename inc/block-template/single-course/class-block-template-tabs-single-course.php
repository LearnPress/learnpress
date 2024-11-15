<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Tabs_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'tabs-single-lp_course';
	public $name                          = 'learnpress/tabs-single-course';
	public $title                         = 'Tabs Single Course (LearnPress)';
	public $description                   = 'Tabs Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/tabs-single-course.html';
	public $single_course_func            = 'html_tabs';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/tabs-single-course.js';
}

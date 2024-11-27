<?php
/**
 * Class Block_Template_Switch_Layout_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Switch_Layout_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'switch-layout-archive-course';
	public $name                          = 'learnpress/switch-layout-archive-course';
	public $title                         = 'Switch Layout (LearnPress)';
	public $description                   = 'Switch Layout Block Template';
	public $path_html_block_template_file = 'html/list-course/switch-layout-archive-course.html';
	public $single_course_func            = 'switch_layout';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/switch-layout-archive-course.js';
}

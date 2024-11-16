<?php
/**
 * Class Block_Template_Description_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Description_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'description-single-lp_course';
	public $name                          = 'learnpress/description-single-course';
	public $title                         = 'Description Single Course (LearnPress)';
	public $description                   = 'Description Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/description-single-course.html';
	public $single_course_func            = 'html_description';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/description-single-course.js';
}

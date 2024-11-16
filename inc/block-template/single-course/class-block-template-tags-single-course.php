<?php
/**
 * Class Block_Template_Tags_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Tags_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'tags-single-lp_course';
	public $name                          = 'learnpress/tags-single-course';
	public $title                         = 'Tags Single Course (LearnPress)';
	public $description                   = 'Tags Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/tags-single-course.html';
	public $single_course_func            = 'html_tags';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/tags-single-course.js';
}

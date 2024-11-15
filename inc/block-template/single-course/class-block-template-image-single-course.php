<?php

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Image_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'image-single-lp_course';
	public $name                          = 'learnpress/image-single-course';
	public $title                         = 'Image Single Course (LearnPress)';
	public $description                   = 'Image Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/image-single-course.html';
	public $single_course_func            = 'html_image';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/image-single-course.js';
}

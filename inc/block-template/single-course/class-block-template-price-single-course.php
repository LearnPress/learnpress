<?php
/**
 * Class Block_Template_Price_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Price_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'price-single-lp_course';
	public $name                          = 'learnpress/price-single-course';
	public $title                         = 'Price Single Course (LearnPress)';
	public $description                   = 'Price Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/price-single-course.html';
	public $single_course_func            = 'html_price';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/price-single-course.js';
}

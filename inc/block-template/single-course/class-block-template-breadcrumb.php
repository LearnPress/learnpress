<?php
/**
 * Class Block_Template_Breadcrumb
 *
 * Handle register, render block template
 */
class Block_Template_Breadcrumb extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'breadcrumb';
	public $name                          = 'learnpress/breadcrumb';
	public $title                         = 'Breadcrumb (LearnPress)';
	public $description                   = 'Breadcrumb Block Template';
	public $path_html_block_template_file = 'html/single-course/breadcrumb.html';
	public $single_course_func            = 'html_breadcrumb';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/breadcrumb.js';
}

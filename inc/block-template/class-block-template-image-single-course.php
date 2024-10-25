<?php

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Image_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'image-single-lp_course';
	public $name                          = 'learnpress/image-single-course';
	public $title                         = 'Image Single Course (LearnPress)';
	public $description                   = 'Image Single Course Block Template';
	public $path_html_block_template_file = 'html/image-single-course.html';
	public $path_template_render_default  = 'widget/image-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/image-single-course.js';

	public function __construct() {
		parent::__construct();
	}
}

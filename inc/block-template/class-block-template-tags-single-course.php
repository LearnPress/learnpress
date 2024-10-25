<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Tags_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'tags-single-lp_course';
	public $name                          = 'learnpress/tags-single-course';
	public $title                         = 'Tags Single Course (LearnPress)';
	public $description                   = 'Tags Single Course Block Template';
	public $path_html_block_template_file = 'html/tags-single-course.html';
	public $path_template_render_default  = 'widgets/tags-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/tags-single-course.js';

	public function __construct() {
		parent::__construct();
	}
}

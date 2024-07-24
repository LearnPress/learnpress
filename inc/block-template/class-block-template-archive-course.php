<?php

/**
 * Class Block_Template_Archive_Course
 *
 * Handle register, render block template
 */
class Block_Template_Archive_Course extends Abstract_Block_Template {
	public $slug                          = 'archive-lp_course';
	public $name                          = 'learnpress/archive-course';
	public $title                         = 'Course Archive (LearnPress)';
	public $description                   = 'Course Archive Block Template';
	public $path_html_block_template_file = 'html/archive-lp_course.html';
	public $path_template_render_default  = 'archive-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/archive-course.js';

	public function __construct() {
		parent::__construct();
	}
}

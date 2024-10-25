<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Title_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'title-single-lp_course';
	public $name                          = 'learnpress/title-single-course';
	public $title                         = 'Title Single Course (LearnPress)';
	public $description                   = 'Title Single Course Block Template';
	public $path_html_block_template_file = 'html/title-single-course.html';
	public $path_template_render_default  = 'widget/title-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/title-single-course.js';

	public function __construct() {
		parent::__construct();
	}
}

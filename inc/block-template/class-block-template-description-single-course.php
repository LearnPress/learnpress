<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Description_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'description-single-lp_course';
	public $name                          = 'learnpress/description-single-course';
	public $title                         = 'Description Single Course (LearnPress)';
	public $description                   = 'Description Single Course Block Template';
	public $path_html_block_template_file = 'html/description-single-course.html';
	public $path_template_render_default  = 'widget/description-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/description-single-course.js';

	public function __construct() {
		parent::__construct();
	}
}

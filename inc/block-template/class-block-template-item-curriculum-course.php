<?php

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Item_Curriculum_Course extends Abstract_Block_Template {
	public $slug                          = 'item-curriculum-course';
	public $name                          = 'learnpress/item-curriculum-course';
	public $title                         = 'Item curriculum Course (LearnPress)';
	public $description                   = 'Item Curriculum Course Block Template';
	public $path_html_block_template_file = 'html/item-curriculum-course.html';
	public $path_template_render_default  = 'content-single-item.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/item-curriculum-course.js';

	public function __construct() {
		parent::__construct();
	}
}

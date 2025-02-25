<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Content_Area
 *
 * Handle register, render block layout
 */
class Block_Layout_Single_Course extends Abstract_Block_Layout {
	public $slug                          = 'lp-single-course';
	public $name                          = 'learnpress/lp-single-course';
	public $title                         = 'Single Course (LearnPress)';
	public $description                   = 'Layout Single Course Block';
	public $path_html_block_template_file = 'html/lp-single-course.html';
	public $path_template_render_default  = 'block/render/single-course/lp-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lp-single-course.js';
}

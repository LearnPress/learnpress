<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Content_Area
 *
 * Handle register, render block layout
 */
class Block_Layout_Content_Area extends Abstract_Block_Layout {
	public $slug                          = 'lp-content-area';
	public $name                          = 'learnpress/lp-content-area';
	public $title                         = 'Content Area (LearnPress)';
	public $description                   = 'Layout Content Area Block';
	public $path_html_block_template_file = 'html/lp-content-area.html';
	public $path_template_render_default  = 'block/render/single-course/lp-content-area.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lp-content-area.js';
}

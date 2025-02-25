<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Content_Left
 *
 * Handle register, render block layout
 */
class Block_Layout_Content_Left extends Abstract_Block_Layout {
	public $slug                          = 'content-left';
	public $name                          = 'learnpress/content-left';
	public $title                         = 'Content Left (LearnPress)';
	public $description                   = 'Layout Content Left Block';
	public $path_html_block_template_file = 'html/content-left.html';
	public $path_template_render_default  = 'block/render/single-course/content-left.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/content-left.js';
}

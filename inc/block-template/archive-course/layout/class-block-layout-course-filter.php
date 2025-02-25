<?php
/**
 * Class Block_Layout_Course_Filter
 *
 * Handle register, render block layout
 */
class Block_Layout_Course_Filter extends Abstract_Block_Layout {
	public $slug                          = 'course-filter';
	public $name                          = 'learnpress/course-filter';
	public $title                         = 'Course Filter (LearnPress)';
	public $description                   = 'Layout Course Filter Block';
	public $path_html_block_template_file = 'html/course-filter.html';
	public $path_template_render_default  = 'block/render/archive-course/course-filter.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-filter.js';
}

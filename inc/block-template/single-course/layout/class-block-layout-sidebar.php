<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Sidebar
 *
 * Handle register, render block layout
 */
class Block_Layout_Sidebar extends Abstract_Block_Layout {
	public $slug                          = 'course-summary-sidebar';
	public $name                          = 'learnpress/course-summary-sidebar';
	public $title                         = 'Sidebar Course (LearnPress)';
	public $description                   = 'Layout Course Content Block';
	public $path_html_block_template_file = 'html/course-summary-sidebar.html';
	public $path_template_render_default  = 'block/render/single-course/course-summary-sidebar.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-summary-sidebar.js';
}

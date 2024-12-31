<?php
/**
 * Class Block_Layout_Sidebar_Archive_Course
 *
 * Handle register, render block layout
 */
class Block_Layout_Sidebar_Archive_Course extends Abstract_Block_Layout {
	public $slug                          = 'sidebar-archive-course';
	public $name                          = 'learnpress/sidebar-archive-course';
	public $title                         = 'Sidebar (LearnPress)';
	public $description                   = 'Layout Sidebar Block';
	public $path_html_block_template_file = 'html/sidebar-archive-course.html';
	public $path_template_render_default  = 'block/render/archive-course/sidebar-archive-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/sidebar-archive-course.js';
}

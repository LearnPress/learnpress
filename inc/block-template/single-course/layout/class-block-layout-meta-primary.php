<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Content_Area
 *
 * Handle register, render block layout
 */
class Block_Layout_Meta_Primary extends Abstract_Block_Layout {
	public $slug                          = 'course-meta-primary';
	public $name                          = 'learnpress/course-meta-primary';
	public $title                         = 'Course Meta Primary (LearnPress)';
	public $description                   = 'Layout Course Meta Primary Block';
	public $path_html_block_template_file = 'html/course-meta-primary.html';
	public $path_template_render_default  = 'block/render/single-course/course-meta-primary.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-meta-primary.js';
}

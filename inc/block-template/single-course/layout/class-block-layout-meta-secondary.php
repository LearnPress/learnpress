<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Meta_Secondary
 *
 * Handle register, render block layout
 */
class Block_Layout_Meta_Secondary extends Abstract_Block_Layout {
	public $slug                          = 'course-meta-secondary';
	public $name                          = 'learnpress/course-meta-secondary';
	public $title                         = 'Course Meta Secondary (LearnPress)';
	public $description                   = 'Layout Course Meta Secondary Block';
	public $path_html_block_template_file = 'html/course-meta-secondary.html';
	public $path_template_render_default  = 'block/render/single-course/course-meta-secondary.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-meta-secondary.js';
}

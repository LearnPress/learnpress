<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Archive_Course
 *
 * Handle register, render block layout
 */
class Block_Layout_Archive_Course extends Abstract_Block_Layout {
	public $slug                          = 'lp-archive-course';
	public $name                          = 'learnpress/lp-archive-course';
	public $title                         = 'Archive Course (LearnPress)';
	public $description                   = 'Layout Archive Course Block';
	public $path_html_block_template_file = 'html/lp-archive-course.html';
	public $path_template_render_default  = 'block/render/archive-course/lp-archive-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lp-archive-course.js';
}

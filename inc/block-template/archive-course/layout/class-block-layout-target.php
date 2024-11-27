<?php
/**
 * Class Block_Layout_Target_Archive_Course
 *
 * Handle register, render block layout
 */
class Block_Layout_Target_Archive_Course extends Abstract_Block_Layout {
	public $slug                          = 'target-archive-course';
	public $name                          = 'learnpress/target-archive-course';
	public $title                         = 'Course Target (LearnPress)';
	public $description                   = 'Layout Course Target Block';
	public $path_html_block_template_file = 'html/target-archive-course.html';
	public $path_template_render_default  = 'block/render/archive-course/target-archive-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/target-archive-course.js';
}

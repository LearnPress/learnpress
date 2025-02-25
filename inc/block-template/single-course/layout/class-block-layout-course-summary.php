<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Content_Area
 *
 * Handle register, render block layout
 */
class Block_Layout_Course_Summary extends Abstract_Block_Layout {
	public $slug                          = 'course-summary';
	public $name                          = 'learnpress/course-summary';
	public $title                         = 'Course Summary (LearnPress)';
	public $description                   = 'Layout Course Summary Block';
	public $path_html_block_template_file = 'html/course-summary.html';
	public $path_template_render_default  = 'block/render/single-course/course-summary.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-summary.js';
}

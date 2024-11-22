<?php
use LearnPress\Models\CourseModel;
/**
 * Class Block_Layout_Content_Area
 *
 * Handle register, render block layout
 */
class Block_Layout_Detail_Info extends Abstract_Block_Layout {
	public $slug                          = 'course-detail-info';
	public $name                          = 'learnpress/course-detail-info';
	public $title                         = 'Course Detail Info (LearnPress)';
	public $description                   = 'Layout Course Detail Info Block';
	public $path_html_block_template_file = 'html/course-detail-info.html';
	public $path_template_render_default  = 'block/render/single-course/course-detail-info.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-detail-info.js';
}

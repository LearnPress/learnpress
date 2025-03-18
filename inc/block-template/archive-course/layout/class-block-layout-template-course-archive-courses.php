<?php

use LearnPress\Helpers\Template;

/**
 * Class Block_Layout_Template_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Layout_Template_Course_Archive_Courses extends Abstract_Block_Layout {
	public $slug                          = 'template-course-archive-course';
	public $name                          = 'learnpress/template-course-archive-course';
	public $title                         = 'Template Course (LearnPress)';
	public $description                   = 'Template Course Block Template';
	public $path_html_block_template_file = 'html/template-course/template-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/template-course-archive-course.js';
	public $path_template_render_default  = 'block/render/archive-course/template-course-archive-course.php';

	public function render_content_block_template( array $attributes, $inner_content = '' ) {
		$content = '';

		try {
			ob_start();
			echo sprintf( '{{template-course}}%s{{end-template-course}}', $inner_content );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

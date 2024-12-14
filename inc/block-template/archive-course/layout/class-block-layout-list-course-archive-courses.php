<?php

use LearnPress\Helpers\Template;

/**
 * Class Block_Layout_List_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Layout_List_Course_Archive_Courses extends Abstract_Block_Layout {
	public $slug                          = 'list-course-archive-course';
	public $name                          = 'learnpress/list-course-archive-course';
	public $title                         = 'List Course (LearnPress)';
	public $description                   = 'List Course Block Template';
	public $path_html_block_template_file = 'html/list-course/list-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/list-course-archive-course.js';
	public $path_template_render_default  = 'block/render/archive-course/list-course-archive-course.php';

	public function render_content_block_template( array $attributes, $inner_content = '' ) {
		$content = '';

		try {
			ob_start();
			$template = $this->path_template_render_default;
			Template::instance()->get_frontend_template( $template, compact( 'attributes', 'inner_content' ) );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

<?php

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor
 *
 * Handle register, render block template
 */
class Block_Template_Instructor extends Abstract_Block_Layout {
	public $slug                          = 'lp-instructor';
	public $name                          = 'learnpress/lp-instructor';
	public $title                         = 'Instructor (LearnPress)';
	public $description                   = 'Instructor Block Template';
	public $path_html_block_template_file = 'html/lp-instructor.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/lp-instructor.js';

	public function render_content_block_template( array $attributes, $inner_content = '' ) {
		$content = '';

		try {
			wp_enqueue_style( 'lp-instructor' );
			ob_start();
			echo SingleInstructorTemplate::instance()->html_instructor( $inner_content );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}
}

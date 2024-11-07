<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Duration_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'duration-single-lp_course';
	public $name                          = 'learnpress/duration-single-course';
	public $title                         = 'Duration Course (LearnPress)';
	public $description                   = 'Duration Course Block Template';
	public $path_html_block_template_file = 'html/duration-single-course.html';
	public $path_template_render_default  = 'block/render/duration-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/duration-single-course.js';

	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_assets' ) );
		parent::__construct();
	}

	public function enqueue_block_assets() {
		wp_enqueue_script(
			'my-block-script',
			plugins_url( 'block.js', __FILE__ ),
			array( 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' )
		);
	}
}

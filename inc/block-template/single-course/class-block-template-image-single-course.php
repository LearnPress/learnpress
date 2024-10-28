<?php

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Image_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'image-single-lp_course';
	public $name                          = 'learnpress/image-single-course';
	public $title                         = 'Image Single Course (LearnPress)';
	public $description                   = 'Image Single Course Block Template';
	public $path_html_block_template_file = 'html/image-single-course.html';
	public $path_template_render_default  = 'block/render/image-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/image-single-course.js';

	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_assets' ) );

		parent::__construct();
	}

	public function enqueue_block_assets() {
		wp_enqueue_script(
			'my-block-script',
			plugins_url( 'block.js', __FILE__ ),
			array( 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' ) // Đảm bảo wp-block-editor có trong danh sách phụ thuộc
		);
	}
}

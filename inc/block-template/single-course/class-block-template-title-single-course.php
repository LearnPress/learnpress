<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Title_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'title-single-lp_course';
	public $name                          = 'learnpress/title-single-course';
	public $title                         = 'Title Single Course (LearnPress)';
	public $description                   = 'Title Single Course Block Template';
	public $path_html_block_template_file = 'html/title-single-course.html';
	public $path_template_render_default  = 'block/render/title-single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/title-single-course.js';

	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_assets' ) );
		parent::__construct();
	}

		/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes ) {
		global $wp;
		$object = get_queried_object();
		$vars   = $wp->query_vars;

		if ( ! empty( $vars['course-item'] ) ) {
			global $post;
			setup_postdata( $post );
			$this->path_template_render_default = 'content-single-item.php';
		} elseif ( $object ) {
			$course = CourseModel::find( $object->ID, true );
			if ( $course && $course->is_offline() ) {
				$this->path_template_render_default = 'single-course-offline.php';
			}
		}

		return parent::render_content_block_template( $attributes );
	}

	public function enqueue_block_assets() {
		wp_enqueue_script(
			'my-block-script',
			plugins_url( 'block.js', __FILE__ ),
			array( 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' )
		);
	}
}

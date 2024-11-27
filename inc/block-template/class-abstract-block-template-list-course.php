<?php

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

/**
 * Class Abstract_Block_Template_Widget_Archive_Courses
 *
 * Handle register, render block template
 */
class Abstract_Block_Template_Widget_Archive_Courses extends Abstract_Block_Template {
	public $single_course_func = '';

	public function __construct() {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_assets' ) );
		parent::__construct();
	}

	public function render_content_block_template( array $attributes ) {
		$content = '';
		if ( empty( $this->single_course_func ) ) {
			return $content;
		}

		try {
			$listCourseTemplate = ListCoursesTemplate::instance();
			$method             = new ReflectionMethod( $listCourseTemplate, $this->single_course_func );
			ob_start();
			echo $method->invokeArgs( $listCourseTemplate, $attributes );
			$content = ob_get_clean();

		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}

	public function enqueue_block_assets() {
		wp_enqueue_script(
			'my-block-script',
			plugins_url( 'block.js', __FILE__ ),
			array( 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' )
		);
	}
}

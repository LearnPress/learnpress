<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Abstract_Block_Template_Widget_Single_Course extends Abstract_Block_Template {
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
			$attributes['courseId'] = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
			$attributes['courseId'] = CourseModel::find( $attributes['courseId'], true );
			if ( empty( $attributes['courseId'] ) ) {
				return $content;
			}

			$singleCourseTemplate = SingleCourseTemplate::instance();
			$method               = new ReflectionMethod( $singleCourseTemplate, $this->single_course_func );
			ob_start();
			echo $method->invokeArgs( $singleCourseTemplate, $attributes );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}

	public function enqueue_block_assets() {
		wp_enqueue_script(
			'my-block-script',
			'',
			array( 'wp-blocks', 'wp-editor', 'wp-element', 'wp-components', 'wp-i18n', 'wp-block-editor' )
		);
	}
}

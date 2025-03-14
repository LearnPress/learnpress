<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Target_Audiences_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Target_Audiences_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'target-audiences-single-course';
	public $name                          = 'learnpress/target-audiences-single-course';
	public $title                         = 'Target audiences Course (LearnPress)';
	public $description                   = 'Target audiences Course Block Template';
	public $path_html_block_template_file = 'html/single-course/target-audiences-single-course.html';
	public $single_course_func            = 'html_course_box_extra';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/target-audiences-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$course = CourseModel::find( get_the_ID(), true );
		if ( ! $course ) {
			return;
		}

		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'margin', 'padding' ] );
		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			SingleCourseTemplate::instance()->html_target( $course )
		);
		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return '.lp-single-course .lp-single-course-main__left .course-target ul, 
		.lp-single-course .lp-single-course-main__left .course-target .extra-box__title 
		{' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

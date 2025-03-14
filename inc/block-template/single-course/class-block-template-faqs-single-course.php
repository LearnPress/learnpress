<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Faqs_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Faqs_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'faqs-single-course';
	public $name                          = 'learnpress/faqs-single-course';
	public $title                         = 'Faqs Course (LearnPress)';
	public $description                   = 'Faqs Course Block Template';
	public $path_html_block_template_file = 'html/single-course/features-single-course.html';
	public $single_course_func            = 'html_course_box_extra';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/faqs-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );

		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		$content                   = '';
		$course                    = CourseModel::find( get_the_ID(), true );

		ob_start();
		echo sprintf(
			'<div class="%s">%s</div>',
			$border_classes_and_styles['classes'],
			SingleCourseTemplate::instance()->html_faqs( $course ),
		);
		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return '.lp-single-course .lp-single-course-main .course-faqs {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

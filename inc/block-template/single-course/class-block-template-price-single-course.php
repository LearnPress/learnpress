<?php
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Price_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Price_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'price-single-lp_course';
	public $name                          = 'learnpress/price-single-course';
	public $title                         = 'Price Single Course (LearnPress)';
	public $description                   = 'Price Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/price-single-course.html';
	public $single_course_func            = 'html_price';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/price-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content                   = '';
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'padding', 'margin' ] );
		$attributes['courseId']    = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
		$course                    = CourseModel::find( $attributes['courseId'], true );
		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			SingleCourseTemplate::instance()->html_price( $course )
		);
		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'padding', 'margin' ] );
		return '.lp-single-course .lp-single-course-main .course-price .price {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

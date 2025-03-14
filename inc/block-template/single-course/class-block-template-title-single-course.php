<?php
/**
 * Class Block_Template_Title_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Title_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'title-single-lp_course';
	public $name                          = 'learnpress/title-single-course';
	public $title                         = 'Title Single Course (LearnPress)';
	public $description                   = 'Title Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/title-single-course.html';
	public $single_course_func            = 'html_title';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/title-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'margin', 'padding' ] );

		$order = [ 'courseId', 'tag' ];
		foreach ( $order as $key ) {
			$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
		}

		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			parent::render_content_block_template( $sortedAttributes )
		);
		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return '.lp-single-course__header .course-title {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

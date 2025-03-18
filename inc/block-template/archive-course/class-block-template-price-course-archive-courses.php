<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Price_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Price_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'price-course-archive-course';
	public $name                          = 'learnpress/price-course-archive-course';
	public $title                         = 'Price Course (LearnPress)';
	public $description                   = 'Price Course Block Template';
	public $path_html_block_template_file = 'html/list-course/price-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/price-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$output = '{{price-course}}';
		return $output;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.learn-press-courses .course-item .course-info .course-price {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

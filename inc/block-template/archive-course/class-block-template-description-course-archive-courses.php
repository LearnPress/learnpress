<?php
/**
 * Class Block_Template_Description_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Description_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'description-course-archive-course';
	public $name                          = 'learnpress/description-course-archive-course';
	public $title                         = 'Description Course (LearnPress)';
	public $description                   = 'Description Course Block Template';
	public $path_html_block_template_file = 'html/list-course/description-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/description-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$output = '{{description-course}}';
		return $output;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.learn-press-courses .course-short-description {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

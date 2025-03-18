<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Tag_Filter_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Tag_Filter_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'tag-filter-archive-course';
	public $name                          = 'learnpress/tag-filter-archive-course';
	public $title                         = 'Tag Filter (LearnPress)';
	public $description                   = 'Tag Filter Block Template';
	public $path_html_block_template_file = 'html/list-course/tag-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/tag-filter-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$output = 'tag';
		return $output;
	}

	public function get_inline_style( $attributes ) {
		$text_transform            = StyleAttributes::get_text_transform_class_and_style( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin', 'border_width', 'border_color' ] );
		return 'form.lp-form-course-filter .lp-form-course-filter__item:has(input[name="tag_id"]) {' . $border_classes_and_styles['styles'] . '}
		form.lp-form-course-filter .lp-form-course-filter__item:has(input[name="tag_id"]) label {' . $text_transform['style'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

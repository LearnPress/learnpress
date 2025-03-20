<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Title_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Title_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'title-course-archive-course';
	public $name                          = 'learnpress/title-course-archive-course';
	public $title                         = 'Title Course (LearnPress)';
	public $description                   = 'Title Course Block Template';
	public $path_html_block_template_file = 'html/list-course/title-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/title-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$tag     = $attributes['tag'] ?? 'h3';
		$is_link = ( isset( $attributes['isLink'] ) && $attributes['isLink'] === false ) ? 'false' : 'true';
		$new_tab = ( isset( $attributes['target'] ) && $attributes['target'] === true ) ? 'true' : 'false';
		$class   = $attributes['className'] ?? '';
		$output  = '{{title-course class="' . $class . '" tag="' . $tag . '" is_link="' . $is_link . '" new_tab="' . $new_tab . '"}}';
		return $output;
	}

	public function get_inline_style( $attributes ) {
		$link_classes_and_styles       = StyleAttributes::get_link_color_class_and_style( $attributes );
		$link_hover_classes_and_styles = StyleAttributes::get_link_hover_color_class_and_style( $attributes );
		$border_classes_and_styles     = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.learn-press-courses span.course-title {' . $border_classes_and_styles['styles'] . '}
				.learn-press-courses a.course-permalink span.course-title {' . $link_classes_and_styles['style'] . '}
				.learn-press-courses a.course-permalink span.course-title:hover, .learn-press-courses a.course-permalink span.course-title:focus {' . $link_hover_classes_and_styles['style'] . '}
				';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

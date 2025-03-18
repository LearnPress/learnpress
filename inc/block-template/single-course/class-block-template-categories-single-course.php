<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Categories_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Categories_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'categories-single-lp_course';
	public $name                          = 'learnpress/categories-single-course';
	public $title                         = 'List Categories Single Course (LearnPress)';
	public $description                   = 'List Categories Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/categories-single-course.html';
	public $single_course_func            = 'html_categories';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/categories-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'margin', 'padding' ] );

		$content   = '';
		$course    = CourseModel::find( get_the_ID(), true );
		$show_text = ( isset( $attributes['showText'] ) && $attributes['showText'] === false ) ? 'false' : 'true';
		$is_link   = ( isset( $attributes['isLink'] ) && $attributes['isLink'] === false ) ? 'false' : 'true';
		$new_tab   = ( isset( $attributes['target'] ) && $attributes['target'] === true ) ? 'true' : 'false';
		$setting   = [
			'is_link' => $is_link,
			'new_tab' => $new_tab,
		];
		$label     = sprintf( '<label>%s</label>', __( 'in', 'learnpress' ) );
		if ( $show_text === 'false' ) {
			$label = '';
		}
		$html_categories = SingleCourseTemplate::instance()->html_categories( $course, $setting );
		if ( ! empty( $html_categories ) ) {
			$content = sprintf(
				'<div class="course-categories__wrapper ' . $border_classes_and_styles['classes'] . '">%s %s</div>',
				$label,
				$html_categories
			);
		}

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$link_classes_and_styles       = StyleAttributes::get_link_color_class_and_style( $attributes );
		$link_hover_classes_and_styles = StyleAttributes::get_link_hover_color_class_and_style( $attributes );
		$border_classes_and_styles     = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );

		return '.course-categories__wrapper {' . $border_classes_and_styles['styles'] . '}
				.lp-single-course__header .course-instructor-category .course-categories a {' . $link_classes_and_styles['style'] . '}
				.lp-single-course__header .course-instructor-category .course-categories a:hover, .lp-single-course__header .course-instructor-category .course-categories a:focus {' . $link_hover_classes_and_styles['style'] . '}
		';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

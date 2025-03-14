<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Instructor_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'instructor-single-lp_course';
	public $name                          = 'learnpress/instructor-single-course';
	public $title                         = 'Instructor Single Course (LearnPress)';
	public $description                   = 'Instructor Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/instructor-single-course.html';
	public $single_course_func            = 'html_instructor';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'margin', 'padding' ] );

		$course  = CourseModel::find( get_the_ID(), true );
		$content = sprintf(
			'<div class="course-instructor__wrapper ' . $border_classes_and_styles['classes'] . '">%s %s</div>',
			sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) ),
			SingleCourseTemplate::instance()->html_instructor( $course )
		);

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$link_classes_and_styles       = StyleAttributes::get_link_color_class_and_style( $attributes );
		$link_hover_classes_and_styles = StyleAttributes::get_link_hover_color_class_and_style( $attributes );
		$border_classes_and_styles     = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );

		return '.course-instructor__wrapper {' . $border_classes_and_styles['styles'] . '}
				.lp-single-course__header .course-instructor-category .course-instructor a {' . $link_classes_and_styles['style'] . '}
				.lp-single-course__header .course-instructor-category .course-instructor a:hover, .lp-single-course__header .course-instructor-category .course-instructor a:focus {' . $link_hover_classes_and_styles['style'] . '}
		';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Student_Single_Course
 *
 * Handle register, render block template
 */

class Block_Template_Student_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'student-single-lp_course';
	public $name                          = 'learnpress/student-single-course';
	public $title                         = 'Student Course (LearnPress)';
	public $description                   = 'Student Course Block Template';
	public $path_html_block_template_file = 'html/single-course/student-single-course.html';
	public $single_course_func            = 'html_count_student';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/student-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content                   = '';
		$course                    = CourseModel::find( get_the_ID(), true );
		$value                     = SingleCourseTemplate::instance()->html_count_student( $course );
		$label                     = __( 'Student', 'learnpress' );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		ob_start();
		echo sprintf(
			'<div class="info-meta-item ' . $border_classes_and_styles['classes'] . '">
					<span class="info-meta-left"><i class="lp-icon-user-graduate"></i>%s:</span>
					<span class="info-meta-right"><div class="course-count-student">%s</div></span>
				</div>',
			$label,
			$value
		);

		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return '.lp-single-course .lp-single-course-main .info-metas .info-meta-item:has(.course-count-student) {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Curriculum_Single_Course
 *
 * Handle register, render block template
 */

class Block_Template_Curriculum_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'curriculum-single-lp_course';
	public $name                          = 'learnpress/curriculum-single-course';
	public $title                         = 'Curriculum Course (LearnPress)';
	public $description                   = 'Curriculum Course Block Template';
	public $path_html_block_template_file = 'html/single-course/curriculum-single-course.html';
	public $single_course_func            = 'html_curriculum';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/curriculum-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$user                      = UserModel::find( get_current_user_id(), true );
		$course                    = CourseModel::find( get_the_ID(), true );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		$singleCourseTemplate      = SingleCourseTemplate::instance();
		ob_start();
		echo sprintf(
			'<div class="%s">%s</div>',
			$border_classes_and_styles['classes'],
			$singleCourseTemplate->html_curriculum( $course, $user ),
		);
		$content = ob_get_clean();

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return '.lp-single-course .lp-single-course-main .lp-course-curriculum {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

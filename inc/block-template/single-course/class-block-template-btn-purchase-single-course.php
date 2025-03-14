<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseClassicTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Btn_Purchase_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Btn_Purchase_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'btn-purchase-single-lp_course';
	public $name                          = 'learnpress/btn-purchase-single-course';
	public $title                         = 'Button Purchase Course (LearnPress)';
	public $description                   = 'Button Purchase Course Block Template';
	public $path_html_block_template_file = 'html/single-course/btn-purchase-single-course.html';
	public $single_course_func            = 'html_btn';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/btn-purchase-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'padding', 'text_color','background_color', 'border_color', 'border_radius','border_width' ] );

		$course = CourseModel::find( get_the_ID(), true );
		$user   = UserModel::find( get_current_user_id(), true );
		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			SingleCourseModernLayout::instance()->html_button( $course, $user )
		);
		$content = ob_get_clean();

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'padding', 'text_color','background_color', 'border_color', 'border_radius','border_width' ] );
		return '.lp-single-course .lp-single-course-main .lp-single-course-main__right__inner .course-buttons .lp-button {' . $border_classes_and_styles['styles'] . '}
		.lp-single-course .lp-single-course-main .lp-single-course-main__right__inner .has-border-color .course-buttons .lp-button { border-style: solid; }';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

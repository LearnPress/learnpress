<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Progress_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Progress_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'progress-single-course';
	public $name                          = 'learnpress/progress-single-course';
	public $title                         = 'Progress Single Course (LearnPress)';
	public $description                   = 'Progress Single Course Block Template';
	public $path_html_block_template_file = 'html/single-course/progress-single-course.html';
	public $single_course_func            = 'html_progress';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/progress-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content                   = '';
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		$course                    = CourseModel::find( get_the_ID(), true );
		$user                      = UserModel::find( get_current_user_id(), true );
		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			SingleCourseModernLayout::instance()->html_info_learning( $course, $user )
		);
		$content = ob_get_clean();

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.info-learning {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

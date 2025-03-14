<?php

use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;

/**
 * Class Block_Template_Info_One_Course
 *
 * Handle register, render block template
 */
class Block_Template_Info_One_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'info-one-course';
	public $name                          = 'learnpress/info-one-course';
	public $title                         = 'Info One Course (LearnPress)';
	public $description                   = 'Info One Course Block Template';
	public $path_html_block_template_file = 'html/single-course/info-one-course.html';
	public $single_course_func            = 'html_info_one_course';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/info-one-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$singleCourseClassicTemplate = SingleCourseModernLayout::instance();
		$course                      = CourseModel::find( get_the_ID(), true );
		$user                        = UserModel::find( get_current_user_id(), true );
		$border_classes_and_styles   = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		ob_start();
		echo sprintf(
			'<div class="last-updated %s">%s</div>',
			$border_classes_and_styles['classes'],
			$singleCourseClassicTemplate->html_info_one( $course, $user ),
		);
		$content = ob_get_clean();

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return '.lp-single-course .lp-single-course-info-one .last-updated {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

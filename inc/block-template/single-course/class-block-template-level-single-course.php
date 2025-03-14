<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Level_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Level_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'level-single-lp_course';
	public $name                          = 'learnpress/level-single-course';
	public $title                         = 'Level Course (LearnPress)';
	public $description                   = 'Level Course Block Template';
	public $path_html_block_template_file = 'html/single-course/level-single-course.html';
	public $single_course_func            = 'html_level';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/level-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content                   = '';
		$course                    = CourseModel::find( get_the_ID(), true );
		$value                     = SingleCourseTemplate::instance()->html_level( $course );
		$label                     = __( 'Level', 'learnpress' );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );

		ob_start();
		echo sprintf(
			'<div class="info-meta-item ' . $border_classes_and_styles['classes'] . '">
					<span class="info-meta-left"><i class="lp-icon-signal"></i>%s:</span>
					<span class="info-meta-right"><div class="course-count-level">%s</div></span>
				</div>',
			$label,
			$value
		);

		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return '.lp-single-course .lp-single-course-main .info-metas .info-meta-item:has(.course-count-level) {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

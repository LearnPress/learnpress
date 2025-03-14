<?php

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor_Total_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Total_Courses extends Abstract_Block_Template {
	public $slug        = 'instructor-total-courses';
	public $name        = 'learnpress/instructor-total-courses';
	public $title       = 'Instructor - Total Courses (LearnPress)';
	public $description = 'Instructor Total Courses Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-total-courses.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return;
			}
			$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'border_color', 'border_radius','border_width' ] );
			$hidden                    = $attributes['hidden'] ?? '';
			ob_start();
			$html_wrapper = [
				'wrapper'     => '<div class="wrapper-instructor-total-courses">',
				'span'        => $hidden === 'icon' ? '' : '<span class="lp-ico lp-icon-courses">',
				'content'     => SingleInstructorTemplate::instance()->html_count_courses( $instructor, $hidden ),
				'end_span'    => '</span>',
				'end_wrapper' => '</div>',
			];
			$html         = Template::combine_components( $html_wrapper );
			echo sprintf(
				'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
				$html
			);
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.lp-single-instructor .wrapper-instructor-total-courses .instructor-total-courses,
		.lp-single-instructor .wrapper-instructor-total-courses .lp-icon-courses:before {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

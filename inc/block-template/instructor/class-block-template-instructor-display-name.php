<?php

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

/**
 * Class Block_Template_Instructor_Display_Name
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Display_Name extends Abstract_Block_Template {
	public $slug        = 'instructor-display-name';
	public $name        = 'learnpress/instructor-display-name';
	public $title       = 'Instructor - Display Name (LearnPress)';
	public $description = 'Instructor Display Name Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-display-name.js';

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
			ob_start();
			echo sprintf(
				'<div class="' . $border_classes_and_styles['classes'] . '"><h2>%s</h2></div>',
				SingleInstructorTemplate::instance()->html_display_name( $instructor )
			);
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.lp-single-instructor .lp-single-instructor__info__right .lp-single-instructor__info__right__content .instructor-display-name {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

<?php

use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;

/**
 * Class Block_Template_Instructor_Avatar
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Avatar extends Abstract_Block_Template {
	public $slug        = 'instructor-avatar';
	public $name        = 'learnpress/instructor-avatar';
	public $title       = 'Instructor - Avatar (LearnPress)';
	public $description = 'Instructor Avatar Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-avatar.js';

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
				'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
				SingleInstructorTemplate::instance()->html_avatar( $instructor )
			);
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'border_color', 'border_radius','border_width' ] );
		return '.lp-single-instructor .lp-single-instructor__info .instructor-avatar img {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

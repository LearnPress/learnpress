<?php

use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;

/**
 * Class Block_Template_Instructor_Background
 *
 * Handle register, render block template
 */
class Block_Template_Instructor_Background extends Abstract_Block_Template {
	public $slug        = 'instructor-background';
	public $name        = 'learnpress/instructor-background';
	public $title       = 'Instructor - Background (LearnPress)';
	public $description = 'Instructor Background Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/instructor-background.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content = '';

		try {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();
			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return $content;
			}

			$userModel = UserModel::find( $instructor->get_id(), true );
			ob_start();
			echo ProfileTemplate::instance()->html_cover_image( $userModel );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
		}

		return $content;
	}

	public function get_inline_style( $attributes ) {
		$style = '';
		if ( $attributes['repeat'] ) {
			$style .= 'background-repeat: repeat;';
		}
		if ( $attributes['size'] ) {
			$style .= sprintf( 'background-size: %s;', $attributes['size'] );
		}
		if ( $attributes['position'] ) {
			$style .= sprintf( 'background-position: %s;', $attributes['position'] );
		}
		return '.lp-single-instructor__info .lp-user-cover-image_background {' . $style . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

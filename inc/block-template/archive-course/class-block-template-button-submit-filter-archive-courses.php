<?php

/**
 * Class Block_Template_Button_Submit_Filter_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Button_Submit_Filter_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug        = 'button-submit-filter';
	public $name        = 'learnpress/button-submit-filter';
	public $title       = 'Button Submit Filter (LearnPress)';
	public $description = 'Button Submit Filter Block Template';
	public $source_js   = LP_PLUGIN_URL . 'assets/js/dist/blocks/button-submit-filter.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$output = 'btn_submit';
		return $output;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'padding', 'text_color','background_color', 'border_color', 'border_radius','border_width' ] );
		return '.lp-form-course-filter button.course-filter-submit {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

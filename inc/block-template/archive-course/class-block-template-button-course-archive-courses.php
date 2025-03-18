<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Button_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Button_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'button-course-archive-course';
	public $name                          = 'learnpress/button-course-archive-course';
	public $title                         = 'Button Course (LearnPress)';
	public $description                   = 'Button Course Block Template';
	public $path_html_block_template_file = 'html/list-course/button-course-archive-course.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/button-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'padding', 'text_color','background_color', 'border_color', 'border_radius','border_width' ] );

		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			'{{button-course}}'
		);
		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'padding', 'text_color','background_color', 'border_color', 'border_radius','border_width' ] );
		return '.learn-press-courses div.course-readmore a {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

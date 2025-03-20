<?php
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
/**
 * Class Block_Template_Breadcrumb
 *
 * Handle register, render block template
 */
class Block_Template_Breadcrumb extends Abstract_Block_Template {
	public $slug                          = 'breadcrumb';
	public $name                          = 'learnpress/breadcrumb';
	public $title                         = 'Breadcrumb (LearnPress)';
	public $description                   = 'Breadcrumb Block Template';
	public $path_html_block_template_file = 'html/single-course/breadcrumb.html';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/breadcrumb.js';

	public function render_content_block_template( array $attributes ) {
		$this->enqueue_assets( $attributes );
		$this->inline_styles( $attributes );
		$content   = '';
		$args      = [];
		$show_home = $attributes['showHome'] ?? true;

		if ( ! $show_home ) {
			$args['home'] = '';
		} else {
			$label = ! empty( $attributes['homeLabel'] ) ? $attributes['homeLabel'] : '';
			if ( $label ) {
				$args['home'] = esc_html( $label );
			}
		}
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		ob_start();
		echo sprintf(
			'<div class="' . $border_classes_and_styles['classes'] . '">%s</div>',
			SingleCourseTemplate::instance()->html_breadcrumb( $args )
		);
		$content = ob_get_clean();
		return $content;
	}

	public function get_inline_style( $attributes ) {
		$link_classes_and_styles       = StyleAttributes::get_link_color_class_and_style( $attributes );
		$link_hover_classes_and_styles = StyleAttributes::get_link_hover_color_class_and_style( $attributes );
		$border_classes_and_styles     = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return '.learn-press-breadcrumb {' . $border_classes_and_styles['styles'] . '}
				div > ul.learn-press-breadcrumb li a {' . $link_classes_and_styles['style'] . '}
				div > ul.learn-press-breadcrumb li a:hover, div > ul.learn-press-breadcrumb li a:focus {' . $link_hover_classes_and_styles['style'] . '}
		';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

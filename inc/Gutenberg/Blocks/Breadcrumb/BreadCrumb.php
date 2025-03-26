<?php
namespace LearnPress\Gutenberg\Blocks\BreadCrumb;
use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
use LP_Debug;
use LP_Template_General;
use Throwable;
/**
 * Class BreadCrumb
 *
 * Handle register, render block template
 */
class BreadCrumb extends AbstractBlockType {
	public $block_name = 'breadcrumb';

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';
		$args = [];
		try {
			$this->enqueue_assets( $attributes );
			$this->inline_styles( $attributes );
			$show_home = $attributes['showHome'] ?? true;

			if ( ! $show_home ) {
				$args['home'] = '';
			} else {
				$label = ! empty( $attributes['homeLabel'] ) ? $attributes['homeLabel'] : '';
				if ( $label ) {
					$args['home'] = esc_html( $label );
				}
			}

			ob_start();
			$template = new LP_Template_General();
			$template->breadcrumb( $args );
			$html_breadcrumb = ob_get_clean();
			$html            = $this->get_output_with_class_hash( $attributes, $html_breadcrumb );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
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

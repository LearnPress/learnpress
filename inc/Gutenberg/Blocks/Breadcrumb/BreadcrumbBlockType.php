<?php
namespace LearnPress\Gutenberg\Blocks\Breadcrumb;
use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
use LP_Debug;
use LP_Template_General;
use Throwable;

/**
 * Class BreadcrumbBlockType
 *
 * Handle register, render block template
 */
class BreadcrumbBlockType extends AbstractBlockType {
	public $block_name = 'breadcrumb';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			],
			'spacing'    => [
				'padding' => true,
				'margin'  => true,
			],
		];
	}

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
			$html            = $this->get_output( $html_breadcrumb );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

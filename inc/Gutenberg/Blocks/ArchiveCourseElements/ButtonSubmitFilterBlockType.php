<?php

namespace LearnPress\Gutenberg\Blocks\ArchiveCourseElements;

use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\AbstractArchiveCourseBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
/**
 * Class ButtonSubmitFilterBlockType
 *
 * Handle register, render block template
 */
class ButtonSubmitFilterBlockType extends AbstractArchiveCourseBlockType {
	public $block_name = 'button-submit-filter';

	public function get_supports(): array {
		return [
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

	public function get_ancestor() {
		return [ 'learnpress/course-filter' ];
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
		$this->enqueue_assets();
		$this->inline_styles( $attributes );
		$html = 'btn_submit';

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin', 'background_color', 'border_color', 'border_radius','border_width' ] );
		return '.lp-form-block-course-filter button.course-filter-submit {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

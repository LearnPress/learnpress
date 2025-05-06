<?php

namespace LearnPress\Gutenberg\Blocks\CourseFilter;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
/**
 * Class CourseTagFilterBlockType
 *
 * Handle register, render block template
 */
class CourseTagFilterBlockType extends AbstractBlockType {
	public $block_name = 'course-tag-filter';

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
		$html = 'tag';

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$text_transform            = StyleAttributes::get_text_transform_class_and_style( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin', 'border_width', 'border_color' ] );
		return 'form.lp-form-block-course-filter .lp-form-course-filter__item:has(input[name="tag_id"]) {' . $border_classes_and_styles['styles'] . '}
		form.lp-form-block-course-filter .lp-form-course-filter__item:has(input[name="tag_id"]) label {' . $text_transform['style'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

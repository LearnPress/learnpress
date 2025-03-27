<?php

namespace LearnPress\Gutenberg\Blocks\ArchiveCourseElements;

use LearnPress\Gutenberg\Blocks\ArchiveCourseElements\AbstractArchiveCourseBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
/**
 * Class CourseSearchFilterBlockType
 *
 * Handle register, render block template
 */
class CourseSearchFilterBlockType extends AbstractArchiveCourseBlockType {
	public $block_name = 'course-search-filter';

	public function get_supports(): array {
		return [
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [
				'fontSize'                 => true,
				'__experimentalFontWeight' => true,
				'textTransform'            => true,
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
		$html = 'search';

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$text_transform            = StyleAttributes::get_text_transform_class_and_style( $attributes );
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin', 'border_width', 'border_color' ] );
		return 'form.lp-form-course-filter .lp-form-course-filter__item:has(.lp-course-filter-search-field) {' . $border_classes_and_styles['styles'] . '}
		form.lp-form-course-filter .lp-form-course-filter__item:has(.lp-course-filter-search-field) .lp-form-course-filter__title {' . $text_transform['style'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

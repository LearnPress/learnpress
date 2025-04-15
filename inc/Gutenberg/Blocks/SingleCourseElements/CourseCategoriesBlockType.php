<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseCategoriesBlockType
 *
 * Handle register, render block template
 */
class CourseCategoriesBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-categories';

	public function get_supports(): array {
		return [
			'color'                => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography'           => [
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			],
			'spacing'              => [
				'padding' => true,
				'margin'  => true,
			],
			'__experimentalBorder' => [
				'color'  => true,
				'radius' => true,
				'width'  => true,
			],
		];
	}

	public function get_ancestor() {
		return [ 'learnpress/single-course', 'learnpress/course-item-template' ];
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

		try {
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$show_text = ( isset( $attributes['showText'] ) && $attributes['showText'] === false ) ? 'false' : 'true';
			$is_link   = ( isset( $attributes['isLink'] ) && $attributes['isLink'] === false ) ? 'false' : 'true';
			$new_tab   = ( isset( $attributes['target'] ) && $attributes['target'] === true ) ? 'true' : 'false';
			$setting   = [
				'is_link' => $is_link,
				'new_tab' => $new_tab,
			];
			$label     = sprintf( '<label>%s</label>', __( 'in', 'learnpress' ) );
			if ( $show_text === 'false' ) {
				$label = '';
			}

			$html_categories = SingleCourseTemplate::instance()->html_categories( $courseModel, $setting );

			if ( empty( $html_categories ) ) {
				return $html;
			}

			$content = sprintf(
				'<div class="is-layout-flex c-gap-4">%s %s</div>',
				$label,
				$html_categories
			);
			$html    = $this->get_output( $content );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

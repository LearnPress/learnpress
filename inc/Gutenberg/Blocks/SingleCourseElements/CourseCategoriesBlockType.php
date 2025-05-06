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
			'align'      => [ 'wide', 'full' ],
			'typography' => [
				'fontSize'                      => true,
				'lineHeight'                    => false,
				'fontWeight'                    => true,
				'__experimentalFontFamily'      => false,
				'__experimentalTextDecoration'  => false,
				'__experimentalFontStyle'       => false,
				'__experimentalFontWeight'      => true,
				'__experimentalLetterSpacing'   => false,
				'__experimentalTextTransform'   => true,
				'__experimentalDefaultControls' => [ 'fontSize' => true ],
			],
			'color'      => [
				'background'                    => false,
				'text'                          => true,
				'link'                          => true,
				'__experimentalDefaultControls' => [
					'link' => false,
					'text' => true,
				],
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

		try {
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$show_text = $attributes['showText'] ?? true;
			$is_link   = $attributes['isLink'] ?? true;
			$new_tab   = $attributes['target'] ?? false;
			$setting   = [
				'is_link' => $is_link,
				'new_tab' => $new_tab,
			];
			$label     = sprintf( '<label>%s</label>', __( 'in', 'learnpress' ) );
			if ( empty( $show_text ) ) {
				$label = '';
			}

			$html_categories = SingleCourseTemplate::instance()->html_categories( $courseModel, $setting );
			if ( empty( $html_categories ) ) {
				return $html;
			}

			$content = sprintf(
				'<div>%s %s</div>',
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

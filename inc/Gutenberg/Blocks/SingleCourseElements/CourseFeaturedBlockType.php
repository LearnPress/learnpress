<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Blocks\SingleCourseElements\AbstractCourseBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseFeaturedBlockType
 *
 * Handle register, render block template
 */
class CourseFeaturedBlockType extends AbstractCourseBlockType {
	public $block_name      = 'course-featured';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/course-elements/course-featured';

	public function get_supports(): array {
		return [
			'align'                => [ 'wide', 'full' ],
			'typography'           => [
				'fontSize'                      => true,
				'lineHeight'                    => false,
				'fontWeight'                    => true,
				'textTransform'                 => false,
				'__experimentalFontFamily'      => false,
				'__experimentalTextDecoration'  => false,
				'__experimentalFontStyle'       => true,
				'__experimentalFontWeight'      => true,
				'__experimentalLetterSpacing'   => false,
				'__experimentalTextTransform'   => true,
				'__experimentalDefaultControls' => [
					'fontSize'      => true,
					'textTransform' => false,
				],
			],
			'color'                => [
				'text'                          => true,
				'background'                    => true,
				'__experimentalDefaultControls' => [
					'text'       => true,
					'background' => true,
				],
			],
			'__experimentalBorder' => [
				'color'  => true,
				'radius' => true,
				'width'  => true,
			],
			'spacing'              => [
				'padding'                       => true,
				'margin'                        => true,
				'__experimentalDefaultControls' => [
					'margin'  => false,
					'padding' => false,
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

			$singleCourseTemplate = SingleCourseTemplate::instance();

			$html_content = $singleCourseTemplate->html_featured( $courseModel );
			if ( ! $html_content ) {
				return $html;
			}

			$html = $this->get_output( $html_content, 'div', [ 'style' => 'display:inline-block;' ] );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

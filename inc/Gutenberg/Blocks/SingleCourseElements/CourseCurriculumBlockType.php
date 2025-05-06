<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseCurriculumBlockType
 *
 * Handle register, render block template
 */
class CourseCurriculumBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-curriculum';

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
				'link'                          => false,
				'heading'                       => true,
				'gradients'                     => false,
				'__experimentalDefaultControls' => [
					'text' => true,
					'h3'   => true,
				],
			],
			'spacing'    => [
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
			$userModel   = $this->get_user();
			if ( ! $courseModel ) {
				return $html;
			}

			$html_curriculum = SingleCourseTemplate::instance()->html_curriculum( $courseModel, $userModel );
			if ( empty( $html_curriculum ) ) {
				return $html;
			}

			$html = $this->get_output( $html_curriculum );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

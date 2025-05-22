<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\SingleCourseOfflineTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseAddressBlockType
 *
 * Handle register, render block template
 */
class CourseAddressBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-address';

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
				'gradients'                     => false,
				'__experimentalDefaultControls' => [
					'text' => true,
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
			if ( ! $courseModel || ! $courseModel->is_offline() ) {
				return $html;
			}

			$html_address = SingleCourseOfflineTemplate::instance()->html_address( $courseModel );
			$html         = $this->get_output( $html_address );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

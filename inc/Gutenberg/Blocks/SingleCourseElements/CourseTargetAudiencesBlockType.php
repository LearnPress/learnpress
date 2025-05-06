<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseTargetAudiencesBlockType
 *
 * Handle register, render block template
 */
class CourseTargetAudiencesBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-target-audiences';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'color'                => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
				'heading'    => true,
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
			$courseModel = $this->get_course( $attributes );
			if ( ! $courseModel ) {
				return $html;
			}

			$html_target_audiences = SingleCourseTemplate::instance()->html_target( $courseModel );
			if ( empty( $html_target_audiences ) ) {
				return $html;
			}

			$html = $this->get_output( $html_target_audiences );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

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

	public function get_ancestor() {
		return [ 'learnpress/single-course' ];
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

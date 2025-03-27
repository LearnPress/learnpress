<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LP_Debug;
use Throwable;

/**
 * Class CourseButtonBlockType
 *
 * Handle register, render block template
 */
class CourseButtonBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-button';

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

			$html_button = SingleCourseModernLayout::instance()->html_button( $courseModel, $userModel );

			if ( empty( $html_button ) ) {
				return $html;
			}

			$html = $this->get_output( $html_button );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

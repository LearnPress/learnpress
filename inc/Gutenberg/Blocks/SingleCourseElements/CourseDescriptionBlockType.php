<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseDescriptionBlockType
 *
 * Handle register, render block template
 */
class CourseDescriptionBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-description';

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
			if ( ! $courseModel ) {
				return $html;
			}
			$description = SingleCourseTemplate::instance()->html_description( $courseModel );
			if ( empty( $description ) ) {
				return $html;
			}
			$html = $this->get_output( $description );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseDurationBlockType
 *
 * Handle register, render block template
 */
class CourseDurationBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-duration';

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
			$value         = SingleCourseTemplate::instance()->html_duration( $courseModel );
			$label         = __( 'Duration', 'learnpress' );
			$html_duration = sprintf(
				'<div class="info-meta-item">
						<span class="info-meta-left"><i class="lp-icon-clock-o"></i>%s:</span>
						<span class="info-meta-right"><div class="course-count-duration">%s</div></span>
					</div>',
				$label,
				$value
			);

			if ( empty( $html_duration ) ) {
				return $html;
			}

			$html = $this->get_output( $html_duration );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

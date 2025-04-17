<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseLevelBlockType
 *
 * Handle register, render block template
 */
class CourseLevelBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-level';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			],
			'spacing'    => [
				'padding' => true,
				'margin'  => true,
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

			$value       = SingleCourseTemplate::instance()->html_level( $courseModel ) ?? 0;
			$html_label  = $attributes['showIcon'] ?? '<i class="lp-icon-signal"></i>';
			$html_label .= $attributes['showLabel'] ?? __( 'Level', 'learnpress' ) . ':';
			$html_left   = $html_label ? sprintf( '<span class="info-meta-left">%s</span>', $html_label ) : '';

			$html_right = sprintf(
				'<span class="info-meta-right"><div class="course-count-level">%s</div></span>',
				$value
			);

			$html_level = sprintf(
				'<div class="info-meta-item">%s %s</div>',
				$html_left,
				$html_right
			);

			if ( empty( $html_level ) ) {
				return $html;
			}

			$html = $this->get_output( $html_level );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

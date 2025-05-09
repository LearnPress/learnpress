<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseInstructorBlockType
 *
 * Handle register, render block template
 */
class CourseInstructorBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-instructor';

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
			$label     = sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) );

			if ( ! $show_text ) {
				$label = '';
			}

			$html_instructor = SingleCourseTemplate::instance()->html_instructor( $courseModel, false, $setting );

			if ( empty( $html_instructor ) ) {
				return $html;
			}

			$content = sprintf(
				'<div>%s %s</div>',
				$label,
				$html_instructor
			);
			$html    = $this->get_output( $content );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

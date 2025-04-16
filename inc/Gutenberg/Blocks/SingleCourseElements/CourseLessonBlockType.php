<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseLessonBlockType
 *
 * Handle register, render block template
 */
class CourseLessonBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-lesson';

	public function get_supports(): array {
		return [
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

			$hidden_label = ( isset( $attributes['showLabel'] ) && $attributes['showLabel'] === false ) ? true : false;
			$hidden_icon  = ( isset( $attributes['showIcon'] ) && $attributes['showIcon'] === false ) ? true : false;
			$value        = SingleCourseTemplate::instance()->html_count_item( $courseModel, LP_LESSON_CPT ) ?? 0;
			$label        = $hidden_label ? '' : __( 'Lesson', 'learnpress' );
			$icon         = $hidden_icon ? '' : '<i class="lp-icon-file-o"></i>';
			$html_left    = sprintf( '<div class="info-meta-left">%s<span>%s:</span></div>', $icon, $label );
			$html_right   = sprintf( '<span class="info-meta-right"><div class="course-count-lesson">%s</div></span>', $value );

			if ( $hidden_label && $hidden_icon ) {
				$html_left = '';
			}

			$html_lesson = sprintf(
				'<div class="info-meta-item">%s %s</div>',
				$html_left,
				$html_right
			);

			if ( empty( $html_lesson ) ) {
				return $html;
			}

			$html = $this->get_output( $html_lesson );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

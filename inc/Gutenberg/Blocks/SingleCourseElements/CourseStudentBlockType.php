<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseStudentBlockType
 *
 * Handle register, render block template
 */
class CourseStudentBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-student';

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
			$value        = SingleCourseTemplate::instance()->html_count_student( $courseModel );
			$label        = __( 'Student', 'learnpress' );
			$html_student = sprintf(
				'<div class="info-meta-item">
						<div class="info-meta-left"><i class="lp-icon-user-graduate"></i><span>%s:</span></div>
						<span class="info-meta-right"><div class="course-count-student">%s</div></span>
					</div>',
				$label,
				$value
			);

			if ( empty( $html_student ) ) {
				return $html;
			}

			$html = $this->get_output( $html_student );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

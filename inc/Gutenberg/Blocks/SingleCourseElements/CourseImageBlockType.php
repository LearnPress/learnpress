<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseImageInfoBlockType
 *
 * Handle register, render block template
 */
class CourseImageBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-image';

	public function get_supports(): array {
		return [
			'align'                => [ 'wide', 'full' ],
			'color'                => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography'           => [
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			],
			'shadow'               => true,
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
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$is_list_course = $block->context['is_list_course'] ?? false;
			$html_image     = '';

			if ( $is_list_course ) {
				$html_image = sprintf( '<a href="%s">%s</a>', $courseModel->get_permalink(), SingleCourseTemplate::instance()->html_image( $courseModel ) );
			} else {
				$html_image = SingleCourseTemplate::instance()->html_image( $courseModel );
			}
			if ( empty( $html_image ) ) {
				return $html;
			}

			$html = $this->get_output( $html_image );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

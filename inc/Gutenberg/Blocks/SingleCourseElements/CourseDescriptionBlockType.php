<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;
use WP_Block;

/**
 * Class CourseDescriptionBlockType
 *
 * Handle register, render block template
 */
class CourseDescriptionBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-description';

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
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$is_list_course = false;
			if ( $block instanceof WP_Block ) {
				$is_list_course = $block->context['is_list_course'] ?? false;
			}

			if ( $is_list_course ) {
				$description = SingleCourseTemplate::instance()->html_short_description( $courseModel );
			} else {
				$description = SingleCourseTemplate::instance()->html_description( $courseModel );
			}

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

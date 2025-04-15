<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LP_Debug;
use Throwable;
use WP_Block;

/**
 * Class CourseButtonBlockType
 *
 * Handle register, render block template
 */
class CourseButtonBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-button';

	public function get_supports(): array {
		return [
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
			$userModel   = $this->get_user();
			if ( ! $courseModel ) {
				return $html;
			}
			$is_list_course = false;
			if ( $block instanceof WP_Block ) {
				$is_list_course = $block->context['is_list_course'] ?? false;
			}

			$html_button = '';
			if ( $is_list_course ) {
				$html_button = sprintf(
					'<div class="course-readmore"><a href="%s">%s</a></div>',
					$courseModel->get_permalink(),
					__( 'Read more', 'learnpress' )
				);
			} else {
				$html_button = SingleCourseModernLayout::instance()->html_button( $courseModel, $userModel );
			}

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

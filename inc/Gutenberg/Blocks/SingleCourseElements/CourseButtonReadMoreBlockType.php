<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LP_Debug;
use Throwable;
use WP_Block;

/**
 * Class CourseButtonReadMoreBlockType
 *
 * Handle register, render block template
 */
class CourseButtonReadMoreBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-button-read-more';

	public function get_supports(): array {
		return [
			'align'                => [ 'wide', 'full' ],
			'html'                 => false,
			'typography'           => [
				'fontSize'                      => true,
				'__experimentalDefaultControls' => [ 'fontSize' => true ],
			],
			'color'                => [
				'background'                    => true,
				'text'                          => true,
				'__experimentalDefaultControls' => [
					'background' => true,
					'text'       => true,
				],
			],
			'__experimentalBorder' => [
				'color'                         => true,
				'radius'                        => true,
				'width'                         => true,
				'__experimentalDefaultControls' => [
					'width'  => false,
					'color'  => false,
					'radius' => false,
				],
			],
			'spacing'              => [
				'margin'                        => true,
				'padding'                       => true,
				'content'                       => true,
				'__experimentalDefaultControls' => [
					'margin'  => false,
					'padding' => false,
					'content' => true,
				],
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

			$map_align_items = [
				'top'    => 'flex-start',
				'center' => 'center',
				'bottom' => 'flex-end',
			];

			$text_align      = $attributes['textAlign'] ?? 'center';
			$align_items     = isset( $attributes['alignItems'] ) ? $map_align_items[ $attributes['alignItems'] ] : 'flex-start';
			$justify_content = $attributes['justifyContent'] ?? 'center';
			$width           = $attributes['width'] ?? '100';

			$extra_attributes = [
				'style' => 'width: 100%; text-align: ' . $text_align . ';',
			];

			$style_wrapper = 'style="display: flex; ' . 'align-items: ' . $align_items . ';' . 'justify-content: ' . $justify_content . ';' . '"';
			$wrapper       = get_block_wrapper_attributes( $extra_attributes );
			$html          = sprintf(
				'<div class="course-button-read-more" %s><a href="%s" %s aria-label="%s"><button %s aria-label="%s">%s</button></a>',
				$style_wrapper,
				$courseModel->get_permalink(),
				'style=" width: ' . $width . '%;"',
				__( 'Learn more about this course', 'learnpress' ),
				$wrapper,
				__( 'Read more', 'learnpress' ),
				__( 'Read more', 'learnpress' )
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

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

	public function get_ancestor() {
		return [ 'learnpress/course-item-template' ];
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

			$html_button = sprintf(
				'<a href="%s"><button class="">%s</button></a>',
				$courseModel->get_permalink(),
				__( 'Read more', 'learnpress' )
			);

			if ( empty( $html_button ) ) {
				return $html;
			}

			$wrapper = get_block_wrapper_attributes();
			$html    = $html_button;

			preg_match( '#class="(.*)"#i', $wrapper, $class_wrapper_find );
			if ( isset( $class_wrapper_find['1'] ) ) {
				// Find class button lp to replace.
				$pattern_btn_find = '#<button.*>.*</button>#i';
				preg_match( $pattern_btn_find, $html_button, $lp_btn_find );
				if ( isset( $lp_btn_find[0] ) ) {
					preg_match( '#class="(.*)"#i', $lp_btn_find[0], $lp_btn_class_find );
					if ( isset( $lp_btn_class_find[1] ) ) {
						$merge_class = $class_wrapper_find[1] . ' ' . $lp_btn_class_find[1];
						$wrapper     = str_replace( $class_wrapper_find[1], $merge_class, $wrapper );
						$html        = str_replace( "class=\"$lp_btn_class_find[1]\"", $wrapper, $html_button );
					}
				}
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

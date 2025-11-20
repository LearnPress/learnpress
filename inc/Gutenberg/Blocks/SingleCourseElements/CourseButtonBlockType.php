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
	 * @since 4.2.8.3
	 * @version 1.0.1
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		try {
			$courseModel = $this->get_course( $attributes, $block );
			$userModel   = $this->get_user();
			if ( ! $courseModel ) {
				return $html;
			}

			$map_align_items = [
				'top'    => 'flex-start',
				'center' => 'center',
				'bottom' => 'flex-end',
			];

			$text_align     = sanitize_text_field( $attributes['textAlign'] ?? 'center' );
			$allowed_aligns = [ 'left', 'center', 'right', 'justify' ];
			$text_align     = in_array( $text_align, $allowed_aligns, true ) ? $text_align : 'center';

			$align_items   = sanitize_text_field( $attributes['alignItems'] ?? 'top' );
			$allowed_items = [ 'top', 'center', 'bottom' ];
			$align_items   = in_array( $align_items, $allowed_items, true ) ? $map_align_items[ $align_items ] : 'flex-start';

			$justify_content = sanitize_text_field( $attributes['justifyContent'] ?? 'center' );
			$allowed_justify = [ 'flex-start', 'center', 'flex-end', 'space-between', 'space-around' ];
			$justify_content = in_array( $justify_content, $allowed_justify, true ) ? $justify_content : 'center';

			$width = absint( $attributes['width'] ?? 100 );
			$width = min( max( $width, 0 ), 100 );

			$html_button = SingleCourseModernLayout::instance()->html_buttons( $courseModel, $userModel );
			if ( empty( $html_button ) ) {
				return $html;
			}

			$extra_attributes = [
				'style' => 'width: 100%; text-align: ' . esc_attr( $text_align ) . ';',
			];
			$wrapper          = get_block_wrapper_attributes( $extra_attributes );

			$style = sprintf(
				'display: flex; align-items: %s; justify-content: %s;',
				esc_attr( $align_items ),
				esc_attr( $justify_content )
			);

			$html_button = sprintf(
				'<div class="course-buttons__wrapper" style="%s">%s</div>',
				esc_attr( $style ),
				$html_button
			);

			$html_button = str_replace(
				'class="course-buttons"',
				'class="course-buttons" ' . 'style="width: ' . $width . '%; display: flex; flex-direction: column; gap: 10px;"',
				$html_button
			);

			if ( isset( $attributes['align'] ) && $attributes['align'] ) {
				$html_button = str_replace(
					'class="course-buttons',
					'class="course-buttons ' . 'align' . sanitize_html_class( $attributes['align'] ) . ' ',
					$html_button
				);
			}

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
				} else {
					// If not find button lp
					$html = $html_button;
				}
			} else {
				// If not find class wrapper
				$html = $html_button;
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

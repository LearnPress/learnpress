<?php

namespace LearnPress\Gutenberg\Blocks\ArchiveCourseElements;

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseOrderByBlockType
 *
 * Handle register, render block template
 */
class CourseOrderByBlockType extends AbstractArchiveCourseBlockType {
	public $block_name = 'course-order-by';

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
		return [ 'learnpress/list-courses' ];
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
			$order_by         = $block->context['order_by'] ?? 'post_date';
			$order_by_current = isset( $_GET['order_by'] ) ? sanitize_text_field( $_GET['order_by'] ) : '';
			if ( ! empty( $order_by_current ) ) {
				$order_by = $order_by_current;
			}

			$values = apply_filters(
				'learn-press/courses/order-by/values',
				[
					'post_date'       => esc_html__( 'Newly published', 'learnpress' ),
					'post_title'      => esc_html__( 'Title a-z', 'learnpress' ),
					'post_title_desc' => esc_html__( 'Title z-a', 'learnpress' ),
					'price'           => esc_html__( 'Price high to low', 'learnpress' ),
					'price_low'       => esc_html__( 'Price low to high', 'learnpress' ),
					'popular'         => esc_html__( 'Popular', 'learnpress' ),
				]
			);

			$html  = '<div class="courses-order-by-wrapper">';
			$html .= '<select name="order_by" class="block-courses-order-by">';
			foreach ( $values as $k => $v ) {
				$html .= '<option value="' . $k . '" ' . selected( $order_by, $k, false ) . '>' . $v . '</option>';
			}
			$html .= '</select>';
			$html .= '</div>';
			$html .= '<div class="course-filter-btn-mobile"><span class="lp-icon lp-icon-filter"></span></div>';
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

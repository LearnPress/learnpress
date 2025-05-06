<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseOrderByBlockType
 *
 * Handle register, render block template
 */
class CourseOrderByBlockType extends AbstractBlockType {
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
			$settings         = $block->context['settings'] ?? [];
			$order_by         = $block->context['order_by'] ?? 'post_date';
			$order_by_current = isset( $settings['order_by'] ) ? sanitize_text_field( $settings['order_by'] ) : '';
			if ( ! empty( $order_by_current ) ) {
				$order_by = $order_by_current;
			}

			$listCoursesTemplate = ListCoursesTemplate::instance();

			$html  = $listCoursesTemplate->html_order_by( $order_by );
			$html .= FilterCourseTemplate::instance()->html_btn_filter_mobile( $settings );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

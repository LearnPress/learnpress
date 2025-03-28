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
				'fontSize'                 => true,
				'__experimentalFontWeight' => true,
				'textTransform'            => true,
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
			$order_by         = $attributes['orderBy'] ?? '';
			$order_by_current = isset( $_GET['order_by'] ) ? sanitize_text_field( $_GET['order_by'] ) : '';
			if ( ! empty( $order_by_current ) ) {
				$order_by = $order_by_current;
			}
			$html = ListCoursesTemplate::instance()->html_order_by( $order_by );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

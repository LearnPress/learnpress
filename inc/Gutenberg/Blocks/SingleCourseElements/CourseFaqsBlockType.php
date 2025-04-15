<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseFaqsBlockType
 *
 * Handle register, render block template
 */
class CourseFaqsBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-faqs';

	public function get_supports(): array {
		return [
			'spacing' => [
				'padding' => true,
				'margin'  => true,
			],
		];
	}

	public function get_ancestor() {
		return [ 'learnpress/single-course' ];
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
			$courseModel = $this->get_course( $attributes );
			if ( ! $courseModel ) {
				return $html;
			}

			$html_faqs = SingleCourseTemplate::instance()->html_faqs( $courseModel );
			if ( empty( $html_faqs ) ) {
				return $html;
			}

			$html = $this->get_output( $html_faqs );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

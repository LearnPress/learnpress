<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseResultsBlockType
 *
 * Handle register, render block template
 */
class CourseResultsBlockType extends AbstractBlockType {
	public $block_name = 'course-results';

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
			'__experimentalBorder' => [
				'color'  => true,
				'radius' => true,
				'width'  => true,
			],
			'spacing'              => [
				'padding'                       => true,
				'margin'                        => true,
				'__experimentalDefaultControls' => [
					'margin'  => false,
					'padding' => false,
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
			$data = $block->context['results_data'] ?? [];
			if ( empty( $data ) ) {
				return $html;
			}

			$html_results = ListCoursesTemplate::instance()->html_courses_page_result( $data );
			$html         = $this->get_output( $html_results );

		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

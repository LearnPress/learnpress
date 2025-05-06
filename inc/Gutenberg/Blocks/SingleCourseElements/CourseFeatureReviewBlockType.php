<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseFeatureReviewBlockType
 *
 * Handle register, render block template
 */
class CourseFeatureReviewBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-feature-review';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [
				'fontSize'                    => true,
				'__experimentalFontWeight'    => true,
				'__experimentalTextTransform' => true,
			],
			'spacing'    => [
				'padding' => true,
				'margin'  => true,
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
			$courseModel = $this->get_course( $attributes );
			$userModel   = $this->get_user();
			if ( ! $courseModel ) {
				return $html;
			}
			$html_feature_review = SingleCourseTemplate::instance()->html_feature_review( $courseModel, $userModel );

			if ( empty( $html_feature_review ) ) {
				return $html;
			}

			$html = $this->get_output( $html_feature_review );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

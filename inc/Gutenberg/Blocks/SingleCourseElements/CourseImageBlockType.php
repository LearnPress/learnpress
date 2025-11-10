<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseImageInfoBlockType
 *
 * Handle register, render block template
 */
class CourseImageBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-image';

	public function get_supports(): array {
		return [
			'align'                => [ 'wide', 'full' ],
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

			$is_link = $attributes['isLink'] ?? true;
			$new_tab = $attributes['target'] ?? false;

			$width  = ! empty( $attributes['customWidth'] ) ? absint( $attributes['customWidth'] ) : 500;
			$height = ! empty( $attributes['customHeight'] ) ? absint( $attributes['customHeight'] ) : 300;

			$size = $attributes['size'] ?? 'custom';
			if ( $size === 'custom' ) {
				$size = [
					$width,
					$height,
				];
			}

			$data_size = [
				'size' => $size,
			];

			$html_image = SingleCourseTemplate::instance()->html_image( $courseModel, $data_size );

			if ( $is_link ) {
				$attribute_target = ! empty( $new_tab ) ? 'target="_blank"' : '';
				$html_image       = sprintf( '<a href="%s" %s>%s</a>', $courseModel->get_permalink(), $attribute_target, $html_image );
			}

			if ( empty( $html_image ) ) {
				return $html;
			}

			$html = $this->get_output( $html_image );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

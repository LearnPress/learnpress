<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseLevelBlockType
 *
 * Handle register, render block template
 */
class CourseLevelBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-level';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'typography' => [
				'fontSize'                      => true,
				'lineHeight'                    => false,
				'fontWeight'                    => true,
				'__experimentalFontFamily'      => false,
				'__experimentalTextDecoration'  => false,
				'__experimentalFontStyle'       => false,
				'__experimentalFontWeight'      => true,
				'__experimentalLetterSpacing'   => false,
				'__experimentalTextTransform'   => true,
				'__experimentalDefaultControls' => [ 'fontSize' => true ],
			],
			'color'      => [
				'background'                    => false,
				'text'                          => true,
				'link'                          => false,
				'gradients'                     => false,
				'__experimentalDefaultControls' => [
					'text' => true,
				],
			],
			'spacing'    => [
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
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$show_icon  = $attributes['showIcon'] ?? true;
			$show_label = $attributes['showLabel'] ?? true;
			$section    = [
				'wrap'       => '<div class="info-meta-item">',
				'info-left'  => sprintf(
					'%s',
					$show_icon || $show_label ?
						sprintf(
							'<span class="info-meta-left">%s%s</span>',
							$show_icon ? '<i class="lp-icon-signal"></i>' : '',
							$show_label ? __( 'Level', 'learnpress' ) . ':' : ''
						)
						: ''
				),
				'info-right' => sprintf(
					'<span class="info-meta-right">%s</span>',
					SingleCourseTemplate::instance()->html_level( $courseModel )
				),
				'wrap_end'   => '</div>',
			];

			$html = $this->get_output( Template::combine_components( $section ) );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

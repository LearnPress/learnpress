<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseTitleBlockType
 *
 * Handle register, render block template
 */
class CourseTitleBlockType extends AbstractCourseBlockType {
	public $block_name      = 'course-title';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/course-elements/course-title';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [
				'fontSize'                      => true,
				'lineHeight'                    => false,
				'fontWeight'                    => true,
				'textTransform'                 => false,
				'__experimentalFontFamily'      => false,
				'__experimentalTextDecoration'  => false,
				'__experimentalFontStyle'       => true,
				'__experimentalFontWeight'      => true,
				'__experimentalLetterSpacing'   => false,
				'__experimentalTextTransform'   => true,
				'__experimentalDefaultControls' => [
					'fontSize'      => true,
					'textTransform' => false,
				],
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
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			$singleCourseTemplate = SingleCourseTemplate::instance();
			$tag                  = sanitize_text_field( $attributes['tag'] ?? 'h3' );
			$allowed_aligns       = [ 'span', 'div', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
			$tag                  = in_array( $tag, $allowed_aligns, true ) ? $tag : 'h3';
			$is_link              = $attributes['isLink'] ?? false;
			$target               = $attributes['target'] ?? false;

			$html_content = $singleCourseTemplate->html_title( $courseModel );
			if ( $is_link ) {
				$html_content = sprintf(
					'<a class="course-permalink" href="%s" %s>%s</a>',
					esc_url( $courseModel->get_permalink() ),
					$target ? 'target="_blank"' : '',
					$singleCourseTemplate->html_title( $courseModel )
				);
			}

			$html = $this->get_output( $html_content, $tag );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

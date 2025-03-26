<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseCategoriesBlockType
 *
 * Handle register, render block template
 */
class CourseCategoriesBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-categories';

	public function get_supports(): array {
		return [
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [
				'fontSize'                 => true,
				'__experimentalFontWeight' => true,
				'textTransform'            => true,
			],
			'spacing'    => [
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
			$this->get_class_hash();
			$this->enqueue_assets();
			$this->inline_styles( $attributes );
			$show_text = ( isset( $attributes['showText'] ) && $attributes['showText'] === false ) ? 'false' : 'true';
			$is_link   = ( isset( $attributes['isLink'] ) && $attributes['isLink'] === false ) ? 'false' : 'true';
			$new_tab   = ( isset( $attributes['target'] ) && $attributes['target'] === true ) ? 'true' : 'false';
			$setting   = [
				'is_link' => $is_link,
				'new_tab' => $new_tab,
			];
			$label     = sprintf( '<label>%s</label>', __( 'in', 'learnpress' ) );
			if ( $show_text === 'false' ) {
				$label = '';
			}

			$html_categories = SingleCourseTemplate::instance()->html_categories( $courseModel, $setting );

			if ( empty( $html_categories ) ) {
				return $html;
			}

			$content = sprintf(
				'%s %s',
				$label,
				$html_categories
			);
			$html    = $this->get_output( $content );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$link_classes_and_styles       = StyleAttributes::get_link_color_class_and_style( $attributes );
		$link_hover_classes_and_styles = StyleAttributes::get_link_hover_color_class_and_style( $attributes );
		$border_classes_and_styles     = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );

		return '.course-categories__wrapper {' . $border_classes_and_styles['styles'] . '}
				.lp-single-course__header .course-instructor-category .course-categories a {' . $link_classes_and_styles['style'] . '}
				.lp-single-course__header .course-instructor-category .course-categories a:hover, .lp-single-course__header .course-instructor-category .course-categories a:focus {' . $link_hover_classes_and_styles['style'] . '}
		';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseCurriculumBlockType
 *
 * Handle register, render block template
 */
class CourseCurriculumBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-curriculum';

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
			$userModel   = $this->get_user();
			if ( ! $courseModel ) {
				return $html;
			}

			$this->get_class_hash();
			$this->enqueue_assets();
			$this->inline_styles( $attributes );

			$html_curriculum = SingleCourseTemplate::instance()->html_curriculum( $courseModel, $userModel );
			if ( empty( $html_curriculum ) ) {
				return $html;
			}

			$html = $this->get_output_with_class_hash( $attributes, $html_curriculum, [], [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$class_style               = '.' . $this->class_hash . ' div.lp-course-curriculum .lp-course-curriculum__title';
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return $class_style . ' {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

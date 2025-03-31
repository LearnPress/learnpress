<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use LP_Debug;
use Throwable;

/**
 * Class CourseButtonBlockType
 *
 * Handle register, render block template
 */
class CourseButtonBlockType extends AbstractCourseBlockType {
	public $block_name = 'course-button';

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
		return [ 'learnpress/single-course', 'learnpress/course-item-template' ];
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
			$userModel   = $this->get_user();
			if ( ! $courseModel ) {
				return $html;
			}

			$html_button = '';
			if ( ! is_singular( LP_COURSE_CPT ) ) {
				$html_button = sprintf(
					'<div class="course-readmore"><a href="%s">%s</a></div>',
					$courseModel->get_permalink(),
					__( 'Read more', 'learnpress' )
				);
			} else {
				$html_button = SingleCourseModernLayout::instance()->html_button( $courseModel, $userModel );
			}

			if ( empty( $html_button ) ) {
				return $html;
			}

			$this->get_class_hash();
			$this->enqueue_assets();
			$this->inline_styles( $attributes );
			$html = $this->get_output_with_class_hash( $attributes, $html_button, [ 'margin' ] );

		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'padding', 'text_color','background_color', 'border_color', 'border_radius','border_width' ] );
		$class_style               = '.lp-single-course .lp-single-course-main .lp-single-course-main__right__inner .' . $this->class_hash;
		$class_button_read_more    = '.learn-press-courses .course-content .' . $this->class_hash . ' .course-readmore a';
		return $class_button_read_more . ',' . $class_style . ' .course-buttons .lp-button {' . $border_classes_and_styles['styles'] . '}
		' . $class_button_read_more . ',' . $class_style . ' .has-border-color .course-buttons .lp-button { border-style: solid; }';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

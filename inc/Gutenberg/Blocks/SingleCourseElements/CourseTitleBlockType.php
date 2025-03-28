<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;
use WP_Block;

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
			if ( ! $courseModel instanceof CourseModel ) {
				return $html;
			}

			$this->get_class_hash();
			$this->enqueue_assets();
			$this->inline_styles( $attributes );
			$tag = $attributes['tag'] ?? 'h3';

			$singleCourseTemplate = SingleCourseTemplate::instance();
			$html                 = $this->get_output_with_class_hash( $attributes, $singleCourseTemplate->html_title( $courseModel, $tag ) );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	public function get_inline_style( $attributes ) {
		$class_style               = '.' . $this->class_hash . ' .course-title';
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform' ] );
		return $class_style . ' {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes ) {
		$styles = $this->get_inline_style( $attributes );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Debug;
use Throwable;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class CourseTitleBlockType extends AbstractCourseBlockType {
	public $name            = 'course-title';
	public $source_js       = LP_PLUGIN_URL . 'assets/js/dist/blocks/course-title.js';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/course-elements/course-title';

	public function get_supports(): array {
		return [
			'color'      => [
				'gradients'  => true,
				'background' => true,
				'text'       => true,
			],
			'typography' => [ 'fontSize' => true ],
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

			$wrapper = get_block_wrapper_attributes();

			if ( ! $courseModel ) {
				return $html;
			}
			$class_hash = $this->get_class_hash();
			$this->enqueue_assets();
			$this->inline_styles( $attributes, $class_hash );
			$class                = $attributes['className'] ? $attributes['className'] . ' ' . $class_hash : $class_hash;
			$singleCourseTemplate = SingleCourseTemplate::instance();
			ob_start();
			echo sprintf(
				'<div %s>%s</div>',
				$wrapper,
				$singleCourseTemplate->html_title( $courseModel ),
			);
			$html = ob_get_clean();
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	public function get_inline_style( $attributes, $class_hash = '' ) {
		$class_style               = '.' . $class_hash . ' .course-title';
		$border_classes_and_styles = StyleAttributes::get_classes_and_styles_by_attributes( $attributes, [ 'font_size', 'font_weight', 'text_color', 'text_transform', 'padding', 'margin' ] );
		return $class_style . ' {' . $border_classes_and_styles['styles'] . '}';
	}

	public function inline_styles( $attributes, $class_hash = '' ) {
		$styles = $this->get_inline_style( $attributes, $class_hash );
		wp_add_inline_style( 'lp-blocks-style', $styles );
	}
}

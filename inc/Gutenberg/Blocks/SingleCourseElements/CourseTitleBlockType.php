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

			$singleCourseTemplate = SingleCourseTemplate::instance();

			$is_link      = ( isset( $attributes['isLink'] ) && $attributes['isLink'] === false ) ? false : true;
			$new_tab      = ( isset( $attributes['target'] ) && $attributes['target'] === true ) ? true : false;
			$title_target = $new_tab ? 'target="_blank"' : '';
			$tag          = $attributes['tag'] ?? 'h3';
			$html_title   = '';
			if ( $is_link ) {
				$html_title = sprintf(
					'<a class="course-permalink" href="%1$s" %2$s>%3$s</a>',
					$courseModel->get_permalink(),
					$title_target,
					$singleCourseTemplate->html_title( $courseModel, $tag )
				);
			} else {
				$html_title = $singleCourseTemplate->html_title( $courseModel, $tag );
			}
			$html = $this->get_output( $html_title );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

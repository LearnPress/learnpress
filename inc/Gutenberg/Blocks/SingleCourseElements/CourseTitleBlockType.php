<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseElements;

use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\Helpers\Template;
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

			$wrapper = get_block_wrapper_attributes( array( 'class' => 'course-title' ) );

			$is_link    = ( isset( $attributes['isLink'] ) && $attributes['isLink'] === false ) ? false : true;
			$target     = ( isset( $attributes['target'] ) && $attributes['target'] === true ) ? 'target="_blank"' : '';
			$tag        = $attributes['tag'] ?? 'h3';
			$content    = apply_filters(
				'learn-press/block-type/course-title',
				[
					'tag'      => sprintf( '<%s '.$wrapper.'>', $tag ),
					'link'     => $is_link ? sprintf( '<a class="course-permalink" href="%s" %s>', $courseModel->get_permalink(), $target ) : '',
					'title'    => $courseModel->get_title(),
					'link_end' => $is_link ? '</a>' : '',
					'tag_end'  => sprintf( '</%s>', $tag ),
				],
				$courseModel,
				$tag,
				$is_link,
				$target
			);
			$html = Template::combine_components( $content );

		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LP_Debug;
use Throwable;

/**
 * Class CourseSearchBlockType
 *
 * Handle register, render block template
 */
class CourseSearchBlockType extends AbstractBlockType {
	public $block_name = 'course-search';

	public function get_supports(): array {
		return [
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

	public function get_ancestor() {
		return [ 'learnpress/list-courses' ];
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
			$settings          = $block->context['settings'] ?? [];
			$settings['class'] = 'block-search-courses';
			$html_search       = ListCoursesTemplate::instance()->html_search_form( $settings );
			$html              = $this->get_output( $html_search );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

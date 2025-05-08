<?php

namespace LearnPress\Gutenberg\Blocks\SingleInstructorElements;

use LearnPress\Gutenberg\Blocks\SingleInstructorElements\AbstractSingleInstructorBlockType;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Debug;
use Throwable;

/**
 * Class InstructorCourseBlockType
 *
 * Handle register, render block template
 */
class InstructorCourseBlockType extends AbstractSingleInstructorBlockType {
	public $block_name = 'instructor-course';

	public function get_supports(): array {
		return [
			'align'      => [ 'wide', 'full' ],
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
			'__experimentalBorder' => [
				'color'  => true,
				'radius' => true,
				'width'  => true,
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
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return $html;
			}
			$hidden = $attributes['hidden'] ?? '';
			ob_start();
			$html_wrapper = [
				'wrapper'     => '<div class="wrapper-instructor-total-courses">',
				'icon'        => $hidden === 'icon' ? '' : '<i class="lp-ico lp-icon-courses"></i>',
				'content'     => SingleInstructorTemplate::instance()->html_count_courses( $instructor, $hidden ),
				'end_wrapper' => '</div>',
			];
			$html_course  = Template::combine_components( $html_wrapper );
			$html         = $this->get_output( $html_course );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

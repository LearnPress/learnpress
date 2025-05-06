<?php

namespace LearnPress\Gutenberg\Blocks\SingleInstructorElements;

use LearnPress\Gutenberg\Blocks\SingleInstructorElements\AbstractSingleInstructorBlockType;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Debug;
use Throwable;

/**
 * Class InstructorAvatarBlockType
 *
 * Handle register, render block template
 */
class InstructorAvatarBlockType extends AbstractSingleInstructorBlockType {
	public $block_name = 'instructor-avatar';

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
			$html_avatar = SingleInstructorTemplate::instance()->html_avatar_edit( $instructor );
			$html        = $this->get_output( $html_avatar );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

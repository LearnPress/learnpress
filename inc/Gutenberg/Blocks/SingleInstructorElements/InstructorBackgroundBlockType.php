<?php

namespace LearnPress\Gutenberg\Blocks\SingleInstructorElements;

use LearnPress\Gutenberg\Blocks\SingleInstructorElements\AbstractSingleInstructorBlockType;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;
use LP_Debug;
use Throwable;

/**
 * Class InstructorBackgroundBlockType
 *
 * Handle register, render block template
 */
class InstructorBackgroundBlockType extends AbstractSingleInstructorBlockType {
	public $block_name = 'instructor-background';

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
		return [ 'learnpress/single-instructor' ];
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

			$userModel = $this->get_user();
			ob_start();
			echo ProfileTemplate::instance()->html_cover_image( $userModel );
			$html_background = ob_get_clean();

			$html = $this->get_output( $html_background );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

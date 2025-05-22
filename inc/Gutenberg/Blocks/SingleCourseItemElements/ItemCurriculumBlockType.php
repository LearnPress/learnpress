<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseItemElements;

use LearnPress;
use LP_Debug;
use Throwable;

/**
 * Class ItemCurriculumBlockType
 *
 * Handle register, render block template
 */
class ItemCurriculumBlockType extends AbstractCourseItemBlockType {
	public $block_name = 'item-curriculum';

	public function get_supports(): array {
		return [
			'align'                => [ 'wide', 'full' ],
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
			'shadow'               => true,
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
			if ( ! $courseModel ) {
				return $html;
			}

			ob_start();
			LearnPress::instance()->template( 'course' )->course_curriculum();
			$html_curriculum = ob_get_clean();

			$html = $this->get_output( $html_curriculum );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseItemElements;

use LearnPress;
use LP_Debug;
use LP_Template_Course;
use Throwable;

/**
 * Class ItemNavigationBlockType
 *
 * Handle register, render block template
 */
class ItemNavigationBlockType extends AbstractCourseItemBlockType {
	public $block_name = 'item-navigation';

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
			LearnPress::instance()->template( 'course' )->func( 'popup_footer_nav' );
			$html_navigation = ob_get_clean();
			error_log( $html_navigation );
			$html = $this->get_output( $html_navigation );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

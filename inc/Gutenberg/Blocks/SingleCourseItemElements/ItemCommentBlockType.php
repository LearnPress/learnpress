<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseItemElements;

use LearnPress;
use LP_Debug;
use LP_Global;
use Throwable;

/**
 * Class ItemCommentBlockType
 *
 * Handle register, render block template
 */
class ItemCommentBlockType extends AbstractCourseItemBlockType {
	public $block_name = 'item-comment';

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
			global $post;
			setup_postdata( $post );

			ob_start();
			LearnPress::instance()->template( 'course' )->course_item_comments();
			$html_comment = ob_get_clean();

			$html = $this->get_output( $html_comment );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

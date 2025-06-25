<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseItemElements;

use LP_Debug;
use Throwable;

/**
 * Class ItemSearchBlockType
 *
 * Handle register, render block template
 */
class ItemSearchBlockType extends AbstractCourseItemBlockType {
	public $block_name = 'item-search';

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

			$html_search = sprintf(
				'<form method="post" class="search-course">
					<input type="text" name="s" autocomplete="off"placeholder="%s"/>
					<button name="submit" aria-label="%s">
						<i class="lp-icon-search"></i>
					</button>
					<button type="button" class="clear"></button>
				</form>',
				esc_attr_x( 'Search for course content', 'search course input placeholder', 'learnpress' ),
				esc_html_x( 'Search for course content', 'search course input placeholder', 'learnpress' )
			);

			$html = $this->get_output( $html_search );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

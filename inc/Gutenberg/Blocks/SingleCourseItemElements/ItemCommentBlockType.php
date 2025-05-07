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
		global $lp_course_item;

		try {
			$courseModel = $this->get_course( $attributes, $block );
			if ( ! $courseModel ) {
				return $html;
			}

			ob_start();
			$course = learn_press_get_course();
			if ( ! $course ) {
				return 'course';
			}

			$item = $lp_course_item;
			if ( ! $item ) {
				return 'item';
			}

			$user                 = learn_press_get_current_user();
			$user_can_view_course = $user->can_view_content_course( $course->get_id() );
			$user_can_view_item   = $user->can_view_item( $item->get_id(), $user_can_view_course );
			if ( ! $user_can_view_item->flag ) {
				return $html;
			}

			if ( $item->setup_postdata() ) {

				if ( comments_open() || get_comments_number() ) {
					learn_press_get_template( 'single-course/item-comments' );
				}
				$item->reset_postdata();
			}
			$html_comment = ob_get_clean();

			$html = $this->get_output( $html_comment );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

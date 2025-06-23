<?php

namespace LearnPress\Gutenberg\Blocks\SingleCourseItemElements;

use LP_Debug;
use Throwable;

/**
 * Class ItemProgressBlockType
 *
 * Handle register, render block template
 */
class ItemProgressBlockType extends AbstractCourseItemBlockType {
	public $block_name = 'item-progress';

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
			$user   = learn_press_get_current_user();
			$course = learn_press_get_course();

			if ( ! $user || ! $course ) {
				return $html;
			}

			$percentage      = 0;
			$total_items     = 0;
			$completed_items = 0;
			$course_data     = $user->get_course_data( $course->get_id() );

			if ( $course_data && ! empty( $course_data->get_user_id() ) && ! $course->is_no_required_enroll() ) {
				$course_results  = $course_data->get_result();
				$completed_items = $course_results['completed_items'];
				$total_items     = $course_results['count_items'];
				$percentage      = $course_results['count_items'] ? absint( $course_results['completed_items'] / $course_results['count_items'] * 100 ) : 0;
			}

			$html_progress = '';

			if ( $user->has_enrolled_or_finished( $course->get_id() ) ) {
				$html_progress = sprintf(
					'<div class="items-progress" data-total-items="%s">
						<span class="number">
							<span class="items-completed">%s</span> of %d items
						</span>
						<div class="learn-press-progress">
							<div class="learn-press-progress__active" data-value="%s%%"></div>
						</div>
					</div>',
					$total_items,
					esc_html( $completed_items ),
					esc_html( $course->count_items() ),
					esc_attr( $percentage )
				);
			}

			$html = $this->get_output( $html_progress );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

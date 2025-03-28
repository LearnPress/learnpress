<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LP_Course_Filter;
use LP_Debug;
use Throwable;
use WP_Block;
use WP_Query;

/**
 * Class SingleCourseBlock
 *
 * Handle register, render block template
 */
class CourseItemTemplateBlock extends AbstractBlockType {
	public $block_name = 'course-item-template';

	public function get_attributes() {
		return [
			'columns' => [
				'type'    => 'number',
				'default' => 2,
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
			$extra_attributes['class'] = 'learn-press-courses';
			$layout                    = $attributes['layout'] ?? 'list';

			$wrapper_attributes = get_block_wrapper_attributes( $extra_attributes );
			$courseQuery        = $block->context['courseQuery'] ?? [];
			if ( empty( $courseQuery ) ) {
				return $html;
			}

			$filter   = new LP_Course_Filter();
			$settings = lp_archive_skeleton_get_args();
			Courses::handle_params_for_query_courses( $filter, $settings );
			Courses::handle_params_for_query_courses( $filter, $courseQuery );
			if ( ! empty( $settings['page_term_id_current'] ) && empty( $settings['term_id'] ) ) {
				$filter->term_ids[] = $settings['page_term_id_current'];
			} elseif ( ! empty( $settings['page_tag_id_current'] ) && empty( $settings['tag_id'] ) ) {
				$filter->tag_ids[] = $settings['page_tag_id_current'];
			}

			$courses = Courses::get_courses( $filter );
			foreach ( $courses as $course ) {
				$courseModel = CourseModel::find( $course->ID, true );

				$filter_block_context = static function ( $context ) use ( $courseModel ) {
					$context['courseModel'] = $courseModel;
					return $context;
				};

				// Add filter with priority 1 so other filters have access to these values
				add_filter( 'render_block_context', $filter_block_context, 1 );
				$block_render  = new WP_Block( $block->parsed_block );
				$block_content = $block_render->render( [ 'dynamic' => false ] );
				remove_filter( 'render_block_context', $filter_block_context, 1 );

				$html .= '<li class="course">' . $block_content . '</li>';
			}

			return sprintf(
				'<ul %1$s data-layout="%2$s">%3$s</ul>',
				$wrapper_attributes,
				$layout,
				$html
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

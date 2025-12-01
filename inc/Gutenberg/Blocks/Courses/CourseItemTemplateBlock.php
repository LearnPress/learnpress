<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Debug;
use Throwable;
use WP_Block;

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
				'default' => 3,
			],
		];
	}

	public function get_ancestor() {
		return [ 'learnpress/list-courses' ];
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
			$layout         = sanitize_text_field( $attributes['layout'] ?? 'list' );
			$allowed_layout = [ 'list', 'grid' ];
			$layout         = in_array( $layout, $allowed_layout, true ) ? $layout : 'list';
			$columns        = absint( $attributes['columns'] ?? 3 );
			$columns        = min( max( $columns, 0 ), 20 );

			$wrapper_attributes = 'learn-press-courses lp-list-courses-no-css wp-block-learn-press-courses';

			if ( $layout == 'grid' ) {
				$wrapper_attributes .= ' grid-template-columns';
			}

			$courses         = $block->context['courses'] ?? [];
			$html_pagination = $block->context['pagination'] ?? '';
			$settings        = $block->context['settings'] ?? [];

			if ( empty( $courses ) ) {
				$html = Template::print_message( __( 'No courses found', 'learnpress' ), 'info', false );
				return $html;
			}

			foreach ( $courses as $course ) {
				$courseModel = CourseModel::find( $course->ID, true );

				$filter_block_context = static function ( $context ) use ( $courseModel, $settings ) {
					$context['courseModel'] = $courseModel;
					$context['settings']    = $settings;
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
				'<ul class="%1$s" data-layout="%2$s" style="--columns: %3$d;">%4$s</ul>%5$s',
				$wrapper_attributes,
				$layout,
				$columns,
				$html,
				$html_pagination
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

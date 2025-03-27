<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Gutenberg\Utils\StyleAttributes;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course_Filter;
use LP_Debug;
use Throwable;
use WP_Block;

/**
 * Class ListCoursesBlockType
 *
 */
class ListCoursesBlockType extends AbstractBlockType {
	public $name            = 'list-courses';
	public $source_js       = LP_PLUGIN_URL . 'assets/js/dist/blocks/list-courses.js';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/courses/list-courses';

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
			$wrapper = get_block_wrapper_attributes();

			if ( ! isset( $attributes['query'] ) ) {
				$attributes['query'] = [
					'limit'    => 3,
					'order_by' => 'post_date',
				];
			}

			$filter = new LP_Course_Filter();
			Courses::handle_params_for_query_courses( $filter, $attributes['query'] );
			$filter->post_status = [ 'publish' ];
			$courses             = Courses::get_courses( $filter );
			$block_content       = '';
			$block_instance      = $block->parsed_block;
			foreach ( $courses as $course ) {
				$courseModel = CourseModel::find( $course->ID, true );

				$filter_block_context = static function ( $context ) use ( $courseModel ) {
					$context['courseModel'] = $courseModel;
					return $context;
				};

				// Add filter with priority 1 so other filters have access to these values
				add_filter( 'render_block_context', $filter_block_context, 1 );
				$block_render   = new WP_Block( $block_instance );
				$block_content .= $block_render->render( [ 'dynamic' => false ] );
				remove_filter( 'render_block_context', $filter_block_context, 1 );
			}

			$context = $block_content;

			$html = sprintf(
				'<ul %s>%s</ul>',
				$wrapper,
				$context
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

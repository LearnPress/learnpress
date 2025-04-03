<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LP_Course_Filter;
use LP_Debug;
use Throwable;
use WP_Block;

/**
 * Class ListCoursesBlockType
 *
 */
class ListCoursesBlockType extends AbstractBlockType {
	public $block_name      = 'list-courses';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/courses/list-courses';

	public function get_attributes() {
		return [
			'courseQuery' => [
				'type'    => 'object',
				'default' => [
					'limit'      => 3,
					'order_by'   => 'post_date',
					'pagination' => false,
					'related'    => false,
					'tag_id'     => '',
					'term_id'    => '',
				],
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
			$block_content = '';
			$wrapper       = get_block_wrapper_attributes();

			$filter_block_context = static function ( $context ) use ( $attributes ) {
				$context['is_list_course'] = true;
				$context['courseQuery']    = $attributes['courseQuery'] ?? $this->get_attributes();
				return $context;
			};

			// Add filter with priority 1 so other filters have access to these values
			add_filter( 'render_block_context', $filter_block_context, 1 );
			$block_render   = new WP_Block( $block->parsed_block );
			$block_content .= $block_render->render( [ 'dynamic' => false ] );
			remove_filter( 'render_block_context', $filter_block_context, 1 );

			$html = sprintf(
				'<div %s>%s</div>',
				$wrapper,
				$block_content
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

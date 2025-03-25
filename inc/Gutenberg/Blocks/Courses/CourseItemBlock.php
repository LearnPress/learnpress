<?php
namespace LearnPress\Gutenberg\Blocks\Courses;
use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LP_Debug;
use Throwable;
use WP_Block;
use WP_Query;

/**
 * Class SingleCourseBlock
 *
 * Handle register, render block template
 */
class CourseItemBlock extends AbstractBlockType {
	public $name = 'course-item';

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
			$content = '';

			$wrapper_attributes = get_block_wrapper_attributes();

			$filter  = new \LP_Course_Filter();
			$total   = 0;
			$courses = Courses::get_courses( $filter, $total );

			foreach ( $courses as $course ) {
				$courseModel = CourseModel::find( $course->ID );
				if ( ! $courseModel ) {
					continue;
				}
				$block_instance = $block->parsed_block;

				$post_id              = get_the_ID();
				$post_type            = get_post_type();
				$filter_block_context = static function ( $context ) use ( $courseModel, $post_type, $post_id ) {
					$context['courseModel'] = $courseModel;
					$context['postType']    = $post_type;
					$context['postId']      = $post_id;
					return $context;
				};

				// Use an early priority to so that other 'render_block_context' filters have access to the values.
				add_filter( 'render_block_context', $filter_block_context, 1 );
				// Render the inner blocks of the Post Template block with `dynamic` set to `false` to prevent calling
				// `render_callback` and ensure that no wrapper markup is included.
				$block_content = ( new WP_Block( $block_instance ) )->render( array( 'dynamic' => false ) );
				remove_filter( 'render_block_context', $filter_block_context, 1 );

				$post_classes = implode( ' ', get_post_class( 'wp-block-post' ) );

				$content .= '<li class="' . esc_attr( $post_classes ) . '">' . $block_content . '</li>';

			}

			return sprintf(
				'<ul %1$s>%2$s</ul>',
				$wrapper_attributes,
				$content
			);
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

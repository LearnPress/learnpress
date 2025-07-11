<?php
namespace LearnPress\Gutenberg\Blocks\CourseFilter;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\TemplateHooks\Course\FilterCourseTemplate;
use LP_Debug;
use Throwable;
/**
 * Class CourseFilterBlockType
 *
 * Handle register, render block template
 */
class CourseFilterBlockType extends AbstractBlockType {
	public $block_name = 'course-filter';

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
			wp_enqueue_script( 'lp-course-filter' );
			wp_enqueue_script( 'lp-widgets' );
			$allowed_fields = apply_filters( 'learnpress/course-filter/allowed-filter', [ 'search', 'author', 'level', 'price', 'category', 'tag','btn_submit', 'btn_reset' ] );
			$fields         = preg_split( '/\s+/', trim( $content ) );
			$fields         = array_values( array_intersect( $fields, $allowed_fields ) );
			apply_filters( 'learnpress/course-filter/fields', $fields );

			$class                     = 'learnpress-block-widget-wrapper learnpress-widget-wrapper';
			$show_in_rest              = isset( $attributes['showInRest'] ) ? ( $attributes['showInRest'] === false ? 0 : 1 ) : 1;
			$show_title                = $attributes['title'] ?? true;
			$title                     = $show_title ? __( 'Course Filter', 'learnpress' ) : '';
			$widget_content            = '';
			$number_level_category     = $attributes['numberLevelCategory'] ?? 1;
			$search_suggestion         = isset( $attributes['searchSuggestion'] ) ? ( $attributes['searchSuggestion'] === false ? 0 : 1 ) : 1;
			$hide_count_zero           = isset( $attributes['hideCountZero'] ) ? ( $attributes['hideCountZero'] === false ? 0 : 1 ) : 1;
			$class_list_courses_target = '.lp-list-courses-default';

			$instance = [
				'title'                     => $title,
				'number_level_category'     => $number_level_category,
				'class_list_courses_target' => $class_list_courses_target,
				'show_in_rest'              => $show_in_rest,
				'hide_count_zero'           => $hide_count_zero,
				'search_suggestion'         => $search_suggestion,
				'fields'                    => $fields,
				'class_wrapper_form'        => 'lp-form-course-filter',
			];

			$data = [
				'widget'   => 'learnpress_widget_course_filter',
				'instance' => wp_json_encode( $instance ),
			];

			if ( $show_in_rest ) {
				$class .= ' learnpress-widget-wrapper__restapi';
				ob_start();
				lp_skeleton_animation_html( 5 );
				$widget_content = ob_get_clean();
			} else {
				$section_data               = $instance;
				$section_data['params_url'] = lp_archive_skeleton_get_args();
				ob_start();
				do_action( 'learn-press/filter-courses/layout', $section_data );
				$widget_content = ob_get_clean();
			}

			$html_course_filter = sprintf(
				'<div class="lp-archive-courses-sidebar">
					<div class="widget learnpress widget_course_filter">
						<h3 class="widget-title">%s</h3>
						<div class="%s" data-widget="%s">
							%s
							<div class="lp-widget-loading-change"></div>
						</div>
					</div>
				</div>',
				$title,
				$class,
				htmlentities( wp_json_encode( $data ) ),
				$widget_content
			);

			$html = $this->get_output( $html_course_filter );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}
}

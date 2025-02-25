<?php
use LearnPress\Models\Courses;

/**
 * Class Block_Template_Pagination_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_Pagination_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'pagination-archive-course';
	public $name                          = 'learnpress/pagination-archive-course';
	public $title                         = 'Pagination (LearnPress)';
	public $description                   = 'Pagination Block Template';
	public $path_html_block_template_file = 'html/list-course/pagination-archive-course.html';
	public $single_course_func            = 'html_pagination';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/pagination-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$filter     = new LP_Course_Filter();
		$settings   = [];
		$total_rows = 0;
		$settings   = array_merge(
			$settings,
			lp_archive_skeleton_get_args()
		);
		Courses::handle_params_for_query_courses( $filter, $settings );
		Courses::get_courses( $filter, $total_rows );
		$total_pages           = 0;
		$total_pages           = LP_Database::get_total_pages( $filter->limit, $total_rows );
		$data_pagination_type  = LP_Settings::get_option( 'course_pagination_type', 'number' );
		$enableAjaxLoadCourses = LP_Settings::get_option( 'courses_load_ajax', 'yes' );

		if ( ! $enableAjaxLoadCourses ) {
			$data_pagination_type = 'number';
		}

		$data_pagination               = [
			'total_pages' => $total_pages,
			'type'        => $data_pagination_type,
			'base'        => add_query_arg( 'paged', '%#%', $settings['url_current'] ?? '' ),
			'paged'       => $settings['paged'] ?? 1,
		];
		$attributes['data_pagination'] = $data_pagination;

		return parent::render_content_block_template( $attributes );
	}
}

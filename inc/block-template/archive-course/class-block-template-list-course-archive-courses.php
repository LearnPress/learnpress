<?php

use LearnPress\Models\Courses;

/**
 * Class Block_Template_List_Course_Archive_Courses
 *
 * Handle register, render block template
 */
class Block_Template_List_Course_Archive_Courses extends Abstract_Block_Template_Widget_Archive_Courses {
	public $slug                          = 'list-course-archive-course';
	public $name                          = 'learnpress/list-course-archive-course';
	public $title                         = 'List Course (LearnPress)';
	public $description                   = 'List Course Block Template';
	public $path_html_block_template_file = 'html/list-course/list-course-archive-course.html';
	public $single_course_func            = 'html_list_courses';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/list-course-archive-course.js';

	public function render_content_block_template( array $attributes ) {
		$settings = [];
		$settings = array_merge(
			$settings,
			lp_archive_skeleton_get_args()
		);

		$filter     = new LP_Course_Filter();
		$total_rows = 0;
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
		$attributes['settings']        = $settings;
		$attributes['data_pagination'] = $data_pagination;

		return parent::render_content_block_template( $attributes );
	}
}

<?php

use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
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

$data_pagination = [
	'total_pages' => $total_pages,
	'type'        => $data_pagination_type,
	'base'        => add_query_arg( 'paged', '%#%', $settings['url_current'] ?? '' ),
	'paged'       => $settings['paged'] ?? 1,
];

?>
<div class="lp-list-courses-default">
	<?php
	echo ListCoursesTemplate::html_list_courses( $settings, $data_pagination, $inner_content );
	?>
</div>
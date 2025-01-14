<?php

use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

$settings = [];
$settings = array_merge(
	$settings,
	lp_archive_skeleton_get_args()
);

$filter     = new LP_Course_Filter();
$total_rows = 0;


$order_by             = $attributes['orderBy'] ?? 'post_date';
$order_by_current     = isset( $_GET['order_by'] ) ? sanitize_text_field( $_GET['order_by'] ) : $order_by;
$layout               = $attributes['layout'] ?? 'list';
$limit                = $attributes['perPage'] ?? '8';
$settings['order_by'] = $order_by_current;
$settings['skin']     = $layout;
$settings['limit']    = $limit;
$filter->limit        = $limit;
$data_pagination_type = 'number';

if ( LP_Page_Controller::is_page_instructor() ) {
	$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

	if ( ! $instructor || ! $instructor->can_create_course() ) {
		return;
	}

	$author_id             = $instructor->get_id();
	$settings['c_authors'] = $author_id;
}

Courses::handle_params_for_query_courses( $filter, $settings );
Courses::get_courses( $filter, $total_rows );
$total_pages = 0;
$total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );

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
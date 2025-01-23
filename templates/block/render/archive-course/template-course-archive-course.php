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
$category             = $attributes['category'] ?? '';
$tag                  = $attributes['tag'] ?? '';

if ( ! empty( $category ) ) {
	$category_id = get_term_by( 'slug', $category, 'course_category' )->term_id ?? '';
}

if ( ! empty( $tag ) ) {
	$tag_id = get_term_by( 'slug', $tag, 'course_tag' )->term_id ?? '';
}

if ( ! empty( $category_id ) ) {
	$settings['page_term_id_current'] = $category_id;
	$settings['term_id']              = $category_id;
}

if ( ! empty( $tag_id ) ) {
	$settings['page_tag_id_current'] = $tag_id;
	$settings['tag_id']              = $tag_id;
}

if ( LP_Page_Controller::is_page_instructor() ) {
	$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

	if ( ! $instructor || ! $instructor->can_create_course() ) {
		return;
	}

	$author_id             = $instructor->get_id();
	$settings['c_authors'] = $author_id;
	$filter->post_author   = $author_id;
}

if ( learn_press_is_course_category() || learn_press_is_course_tag() ) {
	$cat = get_queried_object();

	if ( learn_press_is_course_category() ) {
		$settings['page_term_id_current'] = $cat->term_id;
		$settings['term_id']              = $cat->term_id;
	} elseif ( learn_press_is_course_tag() ) {
		$settings['page_tag_id_current'] = $cat->term_id;
		$settings['tag_id']              = $cat->term_id;
	}

	$settings['page_term_url'] = get_term_link( $cat );
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
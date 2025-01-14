<?php

use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

$content  = $inner_content;
$callback = [
	'class'  => 'LearnPress\\TemplateHooks\\Course\\ListCoursesTemplate',
	'method' => 'render_courses',
];

$order_by         = $attributes['orderBy'] ?? 'post_date';
$course_per_page  = $attributes['perPage'] ?? '8';
$order_by_current = isset( $_GET['order_by'] ) ? sanitize_text_field( $_GET['order_by'] ) : $order_by;
$layout           = $attributes['layout'] ?? 'list';
$pagination       = $attributes['pagination'] ?? 'number';
$custom           = $attributes['custom'] ?? false;

if ( ! $custom ) {
	$args                          = lp_archive_skeleton_get_args();
	$args['order_by']              = $order_by_current;
	$args['skin']                  = $layout;
	$args['limit']                 = $course_per_page;
	$args['data_pagination_type']  = $pagination;
	$args['courses_load_ajax']     = LP_Settings_Courses::is_ajax_load_courses() ? 1 : 0;
	$args['courses_first_no_ajax'] = LP_Settings_Courses::is_no_load_ajax_first_courses() ? 1 : 0;

	if ( LP_Page_Controller::is_page_instructor() ) {
		$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

		if ( ! $instructor || ! $instructor->can_create_course() ) {
			return;
		}

		$author_id         = $instructor->get_id();
		$args['c_authors'] = $author_id;
	}

	$content = TemplateAJAX::load_content_via_ajax( $args, $callback );
}
?>
<div class="lp-list-courses-default">
	<?php
	if ( ! empty( $content ) ) :
		echo $content;
	endif;
	?>
</div>
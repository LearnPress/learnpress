<?php

use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;

$load_ajax        = $attributes['ajax'] ? $attributes['ajax'] : false;
$load_ajax_first  = $attributes['load'] ? $attributes['load'] : false;
$order_by         = $attributes['orderBy'] ?? 'post_date';
$course_per_page  = $attributes['perPage'] ?? '8';
$order_by_current = isset( $_GET['order_by'] ) ? sanitize_text_field( $_GET['order_by'] ) : $order_by;
$layout           = $attributes['layout'] ?? 'list';
$pagination       = $attributes['pagination'] ?? 'number';
$category         = $attributes['category'] ?? '';
$tag              = $attributes['tag'] ?? '';
$callback         = [
	'class'  => 'LearnPress\\TemplateHooks\\Course\\ListCoursesTemplate',
	'method' => 'render_courses',
];

$args = lp_archive_skeleton_get_args();
if ( ! empty( $category ) ) {
	$category_id = get_term_by( 'slug', $category, 'course_category' )->term_id;
}

if ( ! empty( $tag ) ) {
	$tag_id = get_term_by( 'slug', $tag, 'course_tag' )->term_id;
}

if ( ! empty( $category_id ) ) {
	$args['page_term_id_current'] = $category_id;
}

if ( ! empty( $tag_id ) ) {
	$args['page_tag_id_current'] = $tag_id;
}

$args['order_by']              = $order_by_current;
$args['skin']                  = $layout;
$args['limit']                 = $course_per_page;
$args['data_pagination_type']  = $pagination;
$args['courses_load_ajax']     = $load_ajax ? 1 : 0;
$args['courses_first_no_ajax'] = $load_ajax_first ? 1 : 0;

if ( LP_Page_Controller::is_page_instructor() ) {
	$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

	if ( ! $instructor || ! $instructor->can_create_course() ) {
		return;
	}

	$author_id         = $instructor->get_id();
	$args['c_authors'] = $author_id;
}

$pattern = '/{{template-course}}(.*?){{end-template-course}}/s';
preg_match_all( $pattern, $inner_content, $matches );
$template_course  = array_map( 'trim', $matches[1] );
$args['html']     = $inner_content;
$args['template'] = $template_course[0] ? $template_course[0] : '';

if ( ! $load_ajax || $load_ajax && $load_ajax_first ) {
	$content_obj                     = ListCoursesTemplate::render_courses( $args );
	$args['html_no_load_ajax_first'] = $content_obj->content;
}

$content = TemplateAJAX::load_content_via_ajax( $args, $callback );
?>
<div class="lp-list-courses-default">
	<?php
	if ( ! empty( $content ) ) :
		echo $content;
	endif;
	?>
</div>
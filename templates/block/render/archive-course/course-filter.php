<?php

use LearnPress\TemplateHooks\Course\FilterCourseTemplate;

wp_enqueue_script( 'lp-course-filter' );
wp_enqueue_script( 'lp-widgets' );

$class                     = 'learnpress-widget-wrapper';
$show_in_rest              = $attributes['showInRest'] === false ? 0 : 1;
$title                     = $attributes['title'] ?? 'Course Filter';
$content                   = '';
$number_level_category     = $attributes['numberLevelCategory'] ?? 0;
$search_suggestion         = $attributes['searchSuggestion'] === false ? 0 : 1;
$hide_count_zero           = $attributes['hideCountZero'] === false ? 0 : 1;
$class_list_courses_target = $attributes['classListCoursesTarget'] ?? '.lp-list-courses-default';

$instance = [
	'title'                     => $title,
	'number_level_category'     => $number_level_category,
	'class_list_courses_target' => $class_list_courses_target,
	'show_in_rest'              => $show_in_rest,
	'hide_count_zero'           => $hide_count_zero,
	'search_suggestion'         => $search_suggestion,
	'fields_order'              => 'search,author,level,price,category,tag,course_review,btn_submit,btn_reset',
	'fields'                    => [ 'search', 'author', 'level', 'price', 'category', 'tag', 'course_review', 'btn_submit', 'btn_reset' ],
];

$data = [
	'widget'   => 'learnpress_widget_course_filter',
	'instance' => wp_json_encode( $instance ),
];

if ( $show_in_rest ) {
	$class .= ' learnpress-widget-wrapper__restapi';
	ob_start();
	lp_skeleton_animation_html( 5 );
	$content = ob_get_clean();
} else {
	$params = [];
	if ( ! empty( $_GET ) ) {
		foreach ( $_GET as $key => $value ) {
			$params[ sanitize_text_field( $key ) ] = sanitize_text_field( $value );
		}
	}

	$section_data               = $instance;
	$section_data['params_url'] = $params;
	ob_start();
	echo FilterCourseTemplate::instance()->sections( $section_data );
	$content = ob_get_clean();
}

echo sprintf(
	'<div class="lp-archive-courses-sidebar">
	<h3 class="widget-title">%s</h3>
	<div class="%s" data-widget="%s">
	%s
	<div class="lp-widget-loading-change"></div>
	</div>
	</div>',
	$title,
	$class,
	htmlentities( wp_json_encode( $data ) ),
	$content,
);

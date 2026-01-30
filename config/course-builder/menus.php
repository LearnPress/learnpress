<?php

use LearnPress\TemplateHooks\CourseBuilder\BuilderTabCourseTemplate;

$builderTabCourseTemplate = BuilderTabCourseTemplate::instance();

$menu_arr = [
	'dashboard' => array(
		'title'    => esc_html__( 'Dashboard', 'learnpress' ),
		'slug'     => 'dashboard',
		'icon'     => '<i class="dashicons dashicons-dashboard"></i>',
		'sub_menu' => [],
	),
	'courses'   => array(
		'title'    => esc_html__( 'Courses', 'learnpress' ),
		'slug'     => 'courses',
		'icon'     => '<i class="dashicons dashicons-welcome-learn-more"></i>',
		'sub_menu' => [],
		'sections'     => array(
			'overview'   => array(
				'title' => esc_html__( 'Overview', 'learnpress' ),
				'slug'  => 'overview',
			),
			'curriculum' => array(
				'title' => esc_html__( 'Curriculum', 'learnpress' ),
				'slug'  => 'curriculum',
			),
			'settings'   => array(
				'title' => esc_html__( 'Settings', 'learnpress' ),
				'slug'  => 'settings',
			),
		),
	),
	'lessons'   => array(
		'title' => esc_html__( 'Lessons', 'learnpress' ),
		'slug'  => 'lessons',
		'icon'  => '<i class="dashicons dashicons-media-document"></i>',
		'sections'  => array(
			'overview' => array(
				'title' => esc_html__( 'Overview', 'learnpress' ),
				'slug'  => 'overview',
			),
			'settings' => array(
				'title' => esc_html__( 'Settings', 'learnpress' ),
				'slug'  => 'settings',
			),
		),
	),
	'quizzes'   => array(
		'title' => esc_html__( 'Quizzes', 'learnpress' ),
		'slug'  => 'quizzes',
		'icon'  => '<i class="dashicons dashicons-forms"></i>',
		'sections'  => array(
			'overview' => array(
				'title' => esc_html__( 'Overview', 'learnpress' ),
				'slug'  => 'overview',
			),
			'question' => array(
				'title' => esc_html__( 'Question', 'learnpress' ),
				'slug'  => 'question',
			),
			'settings' => array(
				'title' => esc_html__( 'Settings', 'learnpress' ),
				'slug'  => 'settings',
			),
		),
	),
	'questions' => array(
		'title' => esc_html__( 'Questions', 'learnpress' ),
		'slug'  => 'questions',
		'icon'  => '<i class="dashicons dashicons-editor-help"></i>',
		'sections'  => array(
			'overview' => array(
				'title' => esc_html__( 'Overview', 'learnpress' ),
				'slug'  => 'overview',
			),
			'settings' => array(
				'title' => esc_html__( 'Settings', 'learnpress' ),
				'slug'  => 'settings',
			),
		),
	),
];

return apply_filters(
	'learn-press/course-builder/menus',
	$menu_arr
);

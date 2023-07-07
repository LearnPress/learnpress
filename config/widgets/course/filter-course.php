<?php
/**
 * Fields config widget Course Filter.
 */
return apply_filters(
	'learn-press/widget/course-filter/settings',
	array(
		'enable'            => array(
			'label' => esc_html__( 'Enable Widget', 'learnpress' ),
			'type'  => 'checkbox',
			'class' => 'enable_widget',
			'std'   => 1,
		),
		'title'             => array(
			'label' => esc_html__( 'Title', 'learnpress' ),
			'type'  => 'text',
			'std'   => esc_html__( 'Course Filter', 'learnpress' ),
		),
		'show_in_rest'      => array(
			'label' => __( 'Show in rest', 'learnpress' ),
			'type'  => 'checkbox',
			'std'   => 1,
		),
		'field'             => array(
			'label'   => __( 'Fields', 'learnpress' ),
			'type'    => 'sortable-checkbox',
			'options' => array(
				'keyword'    => array(
					'id'    => 'keyword',
					'label' => esc_html__( 'Keyword', 'learnpress' ),
				),
				'price'      => array(
					'id'    => 'price',
					'label' => esc_html__( 'Price', 'learnpress' ),
				),
				'course-cat' => array(
					'id'    => 'course-cat',
					'label' => esc_html__( 'Course Category', 'learnpress' ),
				),
				'course-tag' => array(
					'id'    => 'course-tag',
					'label' => esc_html__( 'Course Tag', 'learnpress' ),
				),
				'instructor' => array(
					'id'    => 'instructor',
					'label' => esc_html__( 'Instructor', 'learnpress' ),
				),
				'level'      => array(
					'id'    => 'level',
					'label' => esc_html__( 'Level', 'learnpress' ),
				),
			),
			'std'     => [],
		),
		'search_suggestion' => array(
			'label' => __( 'Enable Keyword Search Suggestion', 'learnpress' ),
			'type'  => 'checkbox',
			'std'   => 1,
		),
	)
);

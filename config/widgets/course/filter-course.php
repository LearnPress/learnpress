<?php
/**
 * Fields config widget Course Filter.
 */
return apply_filters(
	'learn-press/widget/course-filter/settings',
	array(
		'title'             => array(
			'label' => esc_html__( 'Title', 'learnpress' ),
			'type'  => 'text',
			'std'   => esc_html__( 'Course Filter', 'learnpress' ),
		),
		'show_in_rest'      => array(
			'label' => __( 'Load widget via REST', 'learnpress' ),
			'type'  => 'checkbox',
			'std'   => 1,
		),
		'hide_count_zero'   => array(
			'label' => __( 'Hide field has count is zero', 'learnpress' ),
			'type'  => 'checkbox',
			'std'   => 1,
		),
		'search_suggestion' => array(
			'label' => __( 'Enable Keyword Search Suggestion', 'learnpress' ),
			'type'  => 'checkbox',
			'std'   => 1,
		),
		'fields_order'      => array(
			'label' => '',
			'type'  => 'hidden',
		),
		'fields'            => array(
			'label'   => __( 'Fields', 'learnpress' ),
			'type'    => 'sortable-checkbox',
			'options' => array(
				'search'     => array(
					'id'    => 'search',
					'label' => esc_html__( 'Keyword', 'learnpress' ),
				),
				'price'      => array(
					'id'    => 'price',
					'label' => esc_html__( 'Price', 'learnpress' ),
				),
				'category'   => array(
					'id'    => 'category',
					'label' => esc_html__( 'Course Category', 'learnpress' ),
				),
				'tag'        => array(
					'id'    => 'tag',
					'label' => esc_html__( 'Course Tag', 'learnpress' ),
				),
				'author'     => array(
					'id'    => 'author',
					'label' => esc_html__( 'Author', 'learnpress' ),
				),
				'level'      => array(
					'id'    => 'level',
					'label' => esc_html__( 'Level', 'learnpress' ),
				),
				'btn_submit' => array(
					'id'    => 'btn_submit',
					'label' => esc_html__( 'Button Submit', 'learnpress' ),
				),
				'btn_reset'  => array(
					'id'    => 'level',
					'label' => esc_html__( 'Button Reset', 'learnpress' ),
				),
			),
			'std'     => [
				'search',
				'price',
				'category',
				'tag',
				'author',
				'level',
				'btn_submit',
				'btn_reset',
			],
		),
	)
);

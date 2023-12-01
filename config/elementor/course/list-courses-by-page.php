<?php
/**
 * Elementor Controls for widget Become a teacher settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$option_data = [];
if ( isset( $options ) ) {
	$option_data = $options;
}

// Fields tab content
$content_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'content',
		esc_html__( 'Content', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			'courses_detect_page'          => LPElementorControls::add_control_type(
				'courses_detect_page',
				'Auto detect page',
				'no',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			),
			'courses_rest'                 => LPElementorControls::add_control_type(
				'courses_rest',
				'Courses REST API enable',
				'no',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'default'      => 'no',
				]
			),
			'courses_rest_no_load_page'    => LPElementorControls::add_control_type(
				'courses_rest_no_load_page',
				'Courses REST no load page',
				'no',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'default'      => 'no',
					'condition'    => [
						'courses_rest' => 'yes',
					],
				]
			),
			'courses_per_page'             => LPElementorControls::add_control_type(
				'courses_per_page',
				esc_html__( 'Courses Per Page', 'learnpress' ),
				8,
				Controls_Manager::NUMBER,
				[
					'min'  => 0,
					'step' => 1,
				]
			),
			'courses_order_by_default'     => LPElementorControls::add_control_type_select(
				'courses_order_by_default',
				esc_html__( 'Order By Default', 'learnpress' ),
				[
					'post_date'       => esc_html__( 'Newest', 'learnpress' ),
					'post_title'      => esc_html__( 'Title a-z', 'learnpress' ),
					'post_title_desc' => esc_html__( 'Title z-a', 'learnpress' ),
					'price'           => esc_html__( 'Price High to Low', 'learnpress' ),
					'price_low'       => esc_html__( 'Price Low to High', 'learnpress' ),
				],
				'post_date'
			),
			'courses_rest_pagination_type' => LPElementorControls::add_control_type_select(
				'courses_rest_pagination_type',
				esc_html__( 'Pagination type', 'learnpress' ),
				[
					'number'    => esc_html__( 'Number', 'learnpress' ),
					'load-more' => esc_html__( 'Button load more', 'learnpress' ),
					'infinite'  => esc_html__( 'Infinite scroll', 'learnpress' ),
				],
				'number',
				[
					'condition' => [
						'courses_rest' => 'yes',
					],
				]
			),
		]
	),
	[]
);

// Fields tab style
// Controls tab Grid
function lp_el_style_list_course_by_page( $style_for = 'layout' ) {
	$style_layout_general      = [
		'gap'              => LPElementorControls::add_control_type(
			'gap',
			__( 'Gap', 'learnpress' ),
			[
				'size' => 30,
				'unit' => 'px',
			],
			Controls_Manager::SLIDER,
			[
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 1,
					],
				],
				'selectors' => [
					'{{WRAPPER}} .list-courses-elm' => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		),
		'border'           => LPElementorControls::add_group_control_type(
			'border',
			Group_Control_Border::get_type(),
			'{{WRAPPER}} .list-courses-elm li'
		),
		'background_color' => LPElementorControls::add_control_type_color(
			'background_color',
			__( 'Background Color', 'learnpress' ),
			[
				'.list-courses-elm li' => 'background-color: {{VALUE}};',
			]
		),
		'border_radius'    => LPElementorControls::add_responsive_control_type(
			'border_radius',
			__( 'Border Radius', 'learnpress' ),
			[],
			Controls_Manager::DIMENSIONS,
			[
				'size_units' => [ 'px', '%', 'custom' ],
				'selectors'  => [
					'{{WRAPPER}} .list-courses-elm li' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden;',
				],
			]
		),
	];
	$style_title_general       = LPElementorControls::add_controls_style_text(
		'title',
		'.list-courses-elm .course-title'
	);
	$style_description_general = LPElementorControls::add_controls_style_text(
		'description',
		'.list-courses-elm .course-short-description'
	);
	$style_price_general       = array_merge(
		LPElementorControls::add_controls_style_text(
			'price',
			'.list-courses-elm .course-item-price'
		),
		[
			'price_free'      => LPElementorControls::add_control_type_color(
				'price_free',
				__( 'Course Free Color', 'learnpress' ),
				[
					'{{WRAPPER}} .list-courses-elm .course-item-price .free' => 'color: {{VALUE}};',
				]
			),
			'price_sale'      => LPElementorControls::add_control_type_color(
				'price_sale',
				__( 'Course Sale Color', 'learnpress' ),
				[
					'{{WRAPPER}} .list-courses-elm .course-item-price .origin-price' => 'color: {{VALUE}};',
				]
			),
			'price_sale_typo' => LPElementorControls::add_group_control_type(
				'price_sale_typo',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .list-courses-elm .course-item-price .origin-price',
				[
					'label' => esc_html__( 'Course Sale Typography', 'learnpress' ),
				]
			),
		]
	);
	$style_meta_data_general   = LPElementorControls::add_controls_style_text(
		'meta_data',
		'.list-courses-elm .course-count-lesson, .list-courses-elm .instructor-display-name, .list-courses-elm .course-count-student'
	);
	$style_image_general       = LPElementorControls::add_controls_style_image(
		'img',
		'.list-courses-elm .course-img'
	);

	switch ( $style_for ) {
		case 'layout':
			return $style_layout_general;
		case 'title':
			return $style_title_general;
		case 'description':
			return $style_description_general;
		case 'price':
			return $style_price_general;
		case 'meta_data':
			return $style_meta_data_general;
		case 'image':
			return $style_image_general;
	}
}

$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'style_layout',
		esc_html__( 'Layout', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		lp_el_style_list_course_by_page()
	),
	LPElementorControls::add_fields_in_section(
		'style_sort',
		esc_html__( 'Sort By', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		array_merge(
			LPElementorControls::add_controls_style_text(
				'style_sort_item',
				'.courses-order-by'
			),
			[
				'style_sort_border' => LPElementorControls::add_group_control_type(
					'style_sort_border',
					Group_Control_Border::get_type(),
					'{{WRAPPER}} .courses-order-by'
				),
			]
		)
	),
	LPElementorControls::add_fields_in_section(
		'style_title',
		esc_html__( 'Course Title', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		lp_el_style_list_course_by_page( 'title' )
	),
	LPElementorControls::add_fields_in_section(
		'style_description',
		esc_html__( 'Course Description', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		lp_el_style_list_course_by_page( 'description' )
	),
	LPElementorControls::add_fields_in_section(
		'style_price',
		esc_html__( 'Course Price', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		lp_el_style_list_course_by_page( 'price' )
	),
	LPElementorControls::add_fields_in_section(
		'style_meta_data',
		esc_html__( 'Meta Data', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		lp_el_style_list_course_by_page( 'meta_data' )
	),
	LPElementorControls::add_fields_in_section(
		'style_image',
		esc_html__( 'Course Image', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		lp_el_style_list_course_by_page( 'image' )
	),
	LPElementorControls::add_fields_in_section(
		'style_pagination',
		esc_html__( 'Pagination', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		array_merge(
			[
				'pagination_align'         => LPElementorControls::add_responsive_control_type(
					'pagination_align',
					__( 'Alignment', 'learnpress' ),
					'center',
					Controls_Manager::CHOOSE,
					[
						'options'   => [
							'left'   => [
								'title' => esc_html__( 'Left', 'thim-elementor-kit' ),
								'icon'  => 'eicon-text-align-left',
							],
							'center' => [
								'title' => esc_html__( 'Center', 'thim-elementor-kit' ),
								'icon'  => 'eicon-text-align-center',
							],
							'right'  => [
								'title' => esc_html__( 'Right', 'thim-elementor-kit' ),
								'icon'  => 'eicon-text-align-right',
							],
						],
						'toggle'    => false,
						'selectors' => [
							'{{WRAPPER}} .learn-press-pagination' => 'text-align: {{VALUE}};',
						],
					]
				),
				'pagination_typography'    => LPElementorControls::add_group_control_type(
					'pagination_typography',
					Group_Control_Typography::get_type(),
					'{{WRAPPER}} .learn-press-pagination li .page-numbers, {{WRAPPER}} .courses-btn-load-more'
				),
				'pagination_margin'        => LPElementorControls::add_responsive_control_type(
					'pagination_margin',
					esc_html__( 'Margin', 'learnpress' ),
					[],
					Controls_Manager::DIMENSIONS,
					[
						'size_units' => [ 'px', '%', 'custom' ],
						'selectors'  => array(
							'{{WRAPPER}} .learn-press-pagination li .page-numbers, {{WRAPPER}} .courses-btn-load-more' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
					]
				),
				'pagination_padding'       => LPElementorControls::add_responsive_control_type(
					'pagination_padding',
					esc_html__( 'Padding', 'learnpress' ),
					[],
					Controls_Manager::DIMENSIONS,
					[
						'size_units' => [ 'px', '%', 'custom' ],
						'selectors'  => array(
							'{{WRAPPER}} .learn-press-pagination li .page-numbers, {{WRAPPER}} .courses-btn-load-more' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						),
					]
				),
				'pagination_border'        => LPElementorControls::add_group_control_type(
					'pagination_border',
					Group_Control_Border::get_type(),
					'{{WRAPPER}} .learn-press-pagination li .page-numbers, {{WRAPPER}} .courses-btn-load-more'
				),
				'pagination_border_radius' => LPElementorControls::add_control_type(
					'pagination_border_radius',
					esc_html__( 'Border Radius', 'learnpress' ),
					[],
					Controls_Manager::DIMENSIONS,
					[
						'size_units' => [ 'px', '%', 'custom' ],
						'selectors'  => [
							'{{WRAPPER}} .learn-press-pagination li .page-numbers, {{WRAPPER}} .courses-btn-load-more' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						],
					]
				),
			],
			LPElementorControls::add_start_control_tabs(
				'tabs_of_pagination',
				array_merge(
					LPElementorControls::add_start_control_tab(
						'tab_pagination_normal',
						__( 'Normal', 'learnpress' ),
						[
							'color_pagination_normal'      => LPElementorControls::add_control_type_color(
								'color_pagination_normal',
								__( 'Color', 'learnpress' ),
								[
									'{{WRAPPER}} .learn-press-pagination li .page-numbers, {{WRAPPER}} .courses-btn-load-more' => 'color: {{VALUE}};',
								]
							),
							'background_pagination_normal' => LPElementorControls::add_control_type_color(
								'background_pagination_normal',
								__( 'Background Color', 'learnpress' ),
								[
									'{{WRAPPER}} .learn-press-pagination li .page-numbers, {{WRAPPER}} .courses-btn-load-more' => 'background-color: {{VALUE}};',
								]
							),
						]
					),
					LPElementorControls::add_start_control_tab(
						'tab_pagination_hover',
						__( 'Hover', 'learnpress' ),
						[
							'color_pagination_hover'      => LPElementorControls::add_control_type_color(
								'color_pagination_hover',
								__( 'Color', 'learnpress' ),
								[
									'{{WRAPPER}} .learn-press-pagination li .page-numbers:hover, {{WRAPPER}} .learn-press-pagination li .page-numbers.current, {{WRAPPER}} .courses-btn-load-more:hover' => 'color: {{VALUE}};',
								]
							),
							'background_pagination_hover' => LPElementorControls::add_control_type_color(
								'background_pagination_hover',
								__( 'Background Color', 'learnpress' ),
								[
									'{{WRAPPER}} .learn-press-pagination li .page-numbers:hover, {{WRAPPER}} .learn-press-pagination li .page-numbers.current, {{WRAPPER}} .courses-btn-load-more:hover' => 'background-color: {{VALUE}};',
								]
							),
							'border_pagination_hover'     => LPElementorControls::add_control_type_color(
								'border_pagination_hover',
								__( 'Border Color', 'learnpress' ),
								[
									'{{WRAPPER}} .learn-press-pagination li .page-numbers:hover, {{WRAPPER}} .learn-press-pagination li .page-numbers.current' => 'border-color: {{VALUE}};',
								]
							),
						]
					)
				)
			)
		)
	),
	LPElementorControls::add_fields_in_section(
		'style_not_found',
		esc_html__( 'Courses Not Found', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'style_courses_not_found',
			'.courses-not-found',
			[],
			[
				'text_display',
				'text_shadow',
				'text_color_hover',
				'text_background_hover',
			]
		)
	)
);
return apply_filters(
	'learn-press/elementor/list-courses-by-page',
	array_merge(
		apply_filters(
			'learn-press/elementor/list-courses-by-page/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/list-courses-by-page/tab-styles',
			$style_fields
		)
	)
);

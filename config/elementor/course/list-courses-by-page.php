<?php
/**
 * Elementor Controls for widget Become a teacher settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
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
			'courses_layout_default'       => LPElementorControls::add_control_type(
				'courses_layout_default',
				'Layout Default',
				'grid',
				Controls_Manager::CHOOSE,
				[
					'options' => [
						'grid' => [
							'title' => esc_html__( 'Grid', 'learnpress' ),
							'icon'  => 'eicon-apps',
						],
						'list' => [
							'title' => esc_html__( 'List', 'learnpress' ),
							'icon'  => 'eicon-menu-bar',
						],
					],
					'toggle'  => false,

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
				Controls_Manager::NUMBER
			),
			'order_by_default'             => LPElementorControls::add_control_type_select(
				'order_by',
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
					'number'     => esc_html__( 'Number', 'learnpress' ),
					'load_more'  => esc_html__( 'Button load more', 'learnpress' ),
					'infinitive' => esc_html__( 'Infinitive scroll', 'learnpress' ),
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
	LPElementorControls::add_fields_in_section(
		'courses_layout',
		esc_html__( 'Layout Courses', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			'courses_layout' => LPElementorControls::add_control_type(
				'courses_layout',
				'',
				'{{courses_order_by}}{{courses_layout_type}}{{courses_items}}{{courses_pagination}}',
				Controls_Manager::WYSIWYG,
				[
					'description' => esc_html__( 'Enter the layout for each item course. You can use the following variables: {{courses_order_by}}{{courses_items}}{{courses_pagination_number}}', 'learnpress' ),
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'courses_item_layout',
		esc_html__( 'Layout Item Course', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			'courses_item_layout' => LPElementorControls::add_control_type(
				'courses_item_layout',
				'',
				'<a href="{{course_url}}">{{course_image}}</a>
					<div>
					<a href="{{course_url}}">{{course_title}}</a>
					{{course_count_lesson}}

					<a href="{{course_author_url}}">{{course_author_display_name}}</a>

					<p>{{course_price}}</p>
					<p><a href="{{course_url}}">Start Learning</a></p>
					</div>',
				Controls_Manager::WYSIWYG,
				[
					'description' => esc_html__(
						'Enter the layout for each item course. You can use the following variables: {{course_title}},
						{{course_image}}, {{course_url}}, {{course_author_display_name}}, {{course_author_url}}, {{course_author_avatar}},
						{{course_price}}, {{course_categories}}, {{course_count_student}}, {{course_count_lesson}}, {{course_short_description}}',
						'learnpress'
					),
				]
			),
		]
	),
	[]
);

// Fields tab style
// Controls tab Grid
function lp_el_style_list_course_by_page( $type_tab = 'grid', $style_for = 'layout' ) {
	$style_layout_general = [
		"gap_{$type_tab}"    => LPElementorControls::add_control_type(
			"gap_{$type_tab}",
			__( 'Gap', 'learnpress' ),
			[
				'size' => 5,
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
					"{{WRAPPER}} .list-courses-elm.{$type_tab}" => 'gap: {{SIZE}}{{UNIT}};',
				],
			]
		),
		"border_{$type_tab}" => LPElementorControls::add_control_type(
			"border_{$type_tab}",
			__( 'Border', 'learnpress' ),
			'none',
			Controls_Manager::SELECT,
			[
				'default'   => 'none',
				'options'   => [
					'none'   => esc_html__( 'None', 'learnpress' ),
					'solid'  => esc_html__( 'Solid', 'learnpress' ),
					'dashed' => esc_html__( 'Dashed', 'learnpress' ),
					'dotted' => esc_html__( 'Dotted', 'learnpress' ),
					'double' => esc_html__( 'Double', 'learnpress' ),
				],
				'selectors' => [
					"{{WRAPPER}} .list-courses-elm.{$type_tab} li" => 'border-style: {{VALUE}};',
				],
			]
		),
	];
	$style_title_general  = LPElementorControls::add_controls_style_text(
		"title_{$type_tab}",
		".list-courses-elm.{$type_tab} .course-title"
	);
	$style_image_general  = LPElementorControls::add_controls_style_image(
		"img_{$type_tab}",
		".list-courses-elm.{$type_tab} .course-img"
	);

	switch ( $style_for ) {
		case 'layout':
			return $style_layout_general;
		case 'title':
			return $style_title_general;
		case 'image':
			return $style_image_general;
	}
}

$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'style_layout',
		esc_html__( 'Layout', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_start_control_tabs(
			'tabs_of_layout',
			array_merge(
				LPElementorControls::add_start_control_tab(
					'tab_layout_grid',
					__( 'Grid', 'learnpress' ),
					lp_el_style_list_course_by_page( 'grid' )
				),
				LPElementorControls::add_start_control_tab(
					'tab_layout_list',
					__( 'List', 'learnpress' ),
					lp_el_style_list_course_by_page( 'list' )
				)
			)
		)
	),
	LPElementorControls::add_fields_in_section(
		'style_title',
		esc_html__( 'Course Title', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_start_control_tabs(
			'tabs_of_title',
			array_merge(
				LPElementorControls::add_start_control_tab(
					'tab_title_grid',
					__( 'Grid', 'learnpress' ),
					lp_el_style_list_course_by_page( 'grid', 'title' )
				),
				LPElementorControls::add_start_control_tab(
					'tab_title_list',
					__( 'List', 'learnpress' ),
					lp_el_style_list_course_by_page( 'list', 'title' )
				)
			)
		)
	),
	LPElementorControls::add_fields_in_section(
		'style_image',
		esc_html__( 'Course Image', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_start_control_tabs(
			'tabs_of_image',
			array_merge(
				LPElementorControls::add_start_control_tab(
					'tab_image_grid',
					__( 'Grid', 'learnpress' ),
					lp_el_style_list_course_by_page( 'grid', 'image' )
				),
				LPElementorControls::add_start_control_tab(
					'tab_image_list',
					__( 'List', 'learnpress' ),
					lp_el_style_list_course_by_page( 'list', 'image' )
				)
			)
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

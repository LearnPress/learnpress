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

// Fields tab content
$content_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'layouts',
		esc_html__( 'Layout Item Instructor', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'item_layouts',
				esc_html__( 'Add layout and drag to top to set Active', 'learnpress' ),
				[
					[
						'layout_name' => 'Layout Default',
						'layout_html' => '{{instructor_avatar}}<a href="{{instructor_url}}" style="display: block">{{instructor_display_name}}</a><p>{{instructor_total_courses}}</p>{{instructor_total_students}}',
					],
				],
				Controls_Manager::REPEATER,
				[
					'fields'        => [
						[
							'name'        => 'layout_name',
							'label'       => esc_html__( 'Layout Name', 'learnpress' ),
							'type'        => Controls_Manager::TEXT,
							'label_block' => true,
						],
						[
							'name'        => 'layout_html',
							'label'       => esc_html__( 'Layout HTML', 'learnpress' ),
							'type'        => Controls_Manager::WYSIWYG,
							'description' => 'Sections: {{instructor_avatar}}, {{instructor_url}}, {{instructor_display_name}}, {{instructor_total_courses}}, {{instructor_total_students}}',
							'label_block' => true,
						],
					],
					'prevent_empty' => false,
					'title_field'   => '{{{ layout_name }}}',
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'content',
		esc_html__( 'Content', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			/*'instructor_ids' => LPElementorControls::add_control_type(
				'instructor_ids',
				esc_html__( 'Select Instructor ID', 'learnpress' ),
				[],
				Controls_Manager::TEXT,
				[
					'description' => 'ID of Instructor separated by comma. Ex: 1,2,3',
				]
			),
			'item_per_page' => LPElementorControls::add_control_type(
				'item_per_page',
				esc_html__( 'Item per page', 'learnpress' ),
				5,
				Controls_Manager::NUMBER,
				[
					'min' => -1,
					'max' => 100,
				]
			),*/
			'order_by' => LPElementorControls::add_control_type_select(
				'order_by',
				esc_html__( 'Order By', 'learnpress' ),
				[
					'name'      => esc_html__( 'Name a-z', 'learnpress' ),
					'desc_name' => esc_html__( 'Name z-a', 'learnpress' ),
				],
				'name'
			),
			'limit'    => LPElementorControls::add_control_type(
				'limit',
				esc_html__( 'Limit', 'learnpress' ),
				5,
				Controls_Manager::NUMBER,
				[
					'min'         => -1,
					'max'         => 100,
					'description' => 'Number of items to show. Enter -1 to show all instructors.',
				]
			),
		]
	),
	[]
);

// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'wrapper',
		esc_html__( 'Ul style', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
			'grid_column'        => LPElementorControls::add_responsive_control_type(
				'grid_column',
				esc_html__( 'Number Column', 'learnpress' ),
				2,
				Controls_Manager::NUMBER,
				[
					[
						'min'         => 1,
						'max'         => 6,
						'description' => 'Number of columns to show.',
					],
					'selectors' => [
						'{{WRAPPER}} .list-instructors' => 'display: grid;list-style: none;grid-template-columns: repeat({{VALUE}}, 1fr);padding: 0;margin: 0;',
					],
				]
			),
			'gap_column'         => LPElementorControls::add_control_type_slider(
				'gap_column',
				esc_html__( 'Gap', 'learnpress' ),
				10,
				'px',
				[
					[
						'min' => 0,
						'max' => 100,
					],
					'description' => 'Space between items. (px)',
					'selectors'   => [
						'{{WRAPPER}} .list-instructors' => 'grid-gap: {{SIZE}}{{UNIT}};',
					],
				]
			),
			'item_padding'       => LPElementorControls::add_control_type(
				'item_padding',
				esc_html__( 'Item Padding', 'learnpress' ),
				[
					'top'    => '10',
					'right'  => '10',
					'bottom' => '10',
					'left'   => '10',
				],
				Controls_Manager::DIMENSIONS,
				[
					'selectors' => [
						'{{WRAPPER}} .list-instructors .item-instructor' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			),
			'item_border'        => LPElementorControls::add_group_control_type(
				'item_border',
				Group_Control_Border::get_type(),
				'{{WRAPPER}} .list-instructors .item-instructor',
				[
					'fields_options' => [
						'border' => [
							'default' => 'solid',
						],
						'width'  => [
							'default' => [
								'top'      => '1',
								'right'    => '1',
								'bottom'   => '1',
								'left'     => '1',
								'isLinked' => true,
							],
						],
						'color'  => [
							'default' => '#D4D4D4',
						],
					],
				]
			),
			'item_border_radius' => LPElementorControls::add_control_type(
				'item_border_radius',
				esc_html__( 'Item Border Radius', 'learnpress' ),
				[
					'top'    => '5',
					'right'  => '5',
					'bottom' => '5',
					'left'   => '5',
				],
				Controls_Manager::DIMENSIONS,
				[
					'selectors' => [
						'{{WRAPPER}} .list-instructors .item-instructor' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'avatar',
		esc_html__( 'Avatar Instructor', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_image(
			'avatar',
			'.instructor-avatar img',
			[
				'avatar_align' => LPElementorControls::add_control_type(
					'avatar_align',
					esc_html__( 'Align', 'learnpress' ),
					'left',
					Controls_Manager::CHOOSE,
					[
						'options'   => [
							'left'   => [
								'title' => esc_html__( 'Left', 'elementor' ),
								'icon'  => 'eicon-text-align-left',
							],
							'center' => [
								'title' => esc_html__( 'Center', 'elementor' ),
								'icon'  => 'eicon-text-align-center',
							],
							'right'  => [
								'title' => esc_html__( 'Right', 'elementor' ),
								'icon'  => 'eicon-text-align-right',
							],
						],
						'selectors' => [
							'{{WRAPPER}} .instructor-avatar' => 'text-align: {{VALUE}};',
						],
					]
				),
				'avatar_with'  => LPElementorControls::add_control_type(
					'avatar_with_height',
					esc_html__( 'Width(%)', 'learnpress' ),
					'',
					Controls_Manager::NUMBER,
					[
						'selectors' => [
							'{{WRAPPER}} .instructor-avatar img' => 'width:{{VALUE}}%;',
						],
					]
				),
			]
		)
	),
	LPElementorControls::add_fields_in_section(
		'title',
		esc_html__( 'Title Instructor', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'title',
			'.instructor-display-name'
		)
	),
	LPElementorControls::add_fields_in_section(
		'total_courses',
		esc_html__( 'Total Courses', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'total_courses',
			'.instructor-total-courses'
		)
	),
	LPElementorControls::add_fields_in_section(
		'total_students',
		esc_html__( 'Total Students', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'total_students',
			'.instructor-total-students'
		)
	),
	[]
);

return apply_filters(
	'learn-press/elementor/list-instructors',
	array_merge(
		apply_filters(
			'learn-press/elementor/list-instructors/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/list-instructors/tab-styles',
			$style_fields
		)
	)
);

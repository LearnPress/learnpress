<?php
/**
 * Elementor Controls for widget filter course settings.
 *
 * @since 4.2.5
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$content_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'filter_area',
		esc_html__( 'Filter Area', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'item_filter',
				esc_html__( 'Fields', 'learnpress' ),
				[
					[
						'item_fields' => 'category',
					],
					[
						'item_fields' => 'btn_submit',
					],
				],
				Controls_Manager::REPEATER,
				[
					'fields'        => [
						[
							'name'    => 'item_fields',
							'label'   => esc_html__( 'Filter By', 'learnpress' ),
							'type'    => Controls_Manager::SELECT,
							'options' => array(
								'search'     => esc_html__( 'Keyword', 'learnpress' ),
								'price'      => esc_html__( 'Price', 'learnpress' ),
								'category'   => esc_html__( 'Course Category', 'learnpress' ),
								'tag'        => esc_html__( 'Course Tag', 'learnpress' ),
								'author'     => esc_html__( 'Author', 'learnpress' ),
								'level'      => esc_html__( 'Level', 'learnpress' ),
								'btn_submit' => esc_html__( 'Button Submit', 'learnpress' ),
								'btn_reset'  => esc_html__( 'Button Reset', 'learnpress' ),
							),
						],
						// [
						// 	'name'      => 'type_source',
						// 	'label'     => esc_html__( 'Type Source', 'learnpress' ),
						// 	'type'      => Controls_Manager::SELECT,
						// 	'default'   => 'checkbox',
						// 	'options'   => array(
						// 		'checkbox' => esc_html__( 'Check Box', 'learnpress' ),
						// 		'dropdown' => esc_html__( 'Drop Down (Coming Soon)', 'learnpress' ),
						// 	),
						// 	'condition' => [
						// 		'item_fields' => [ 'price', 'category', 'tag', 'author', 'level' ],
						// 	],
						// ],
						[
							'name'         => 'enable_count',
							'label'        => esc_html__( 'Show Count', 'learnpress' ),
							'type'         => Controls_Manager::SWITCHER,
							'default'      => 'yes',
							'label_on'     => esc_html__( 'Show', 'learnpress' ),
							'label_off'    => esc_html__( 'Hide', 'learnpress' ),
							'return_value' => 'yes',
							'condition'    => [
								'item_fields' => [ 'price', 'category', 'tag', 'author', 'level' ],
							],
						],
						[
							'name'         => 'heading_setting',
							'label'        => esc_html__( 'Heading Setting', 'learnpress' ),
							'type'         => Controls_Manager::POPOVER_TOGGLE,
							'label_off'    => esc_html__( 'Default', 'learnpress' ),
							'label_on'     => esc_html__( 'Custom', 'learnpress' ),
							'return_value' => 'yes',
							'condition'    => [
								'item_fields!' => [ 'btn_submit', 'btn_reset' ],
							],
						],
						[ 'method' => 'start_popover' ],
						[
							'name'         => 'enable_heading',
							'label'        => esc_html__( 'Enable Heading', 'learnpress' ),
							'type'         => Controls_Manager::SWITCHER,
							'default'      => 'yes',
							'label_on'     => esc_html__( 'Show', 'learnpress' ),
							'label_off'    => esc_html__( 'Hide', 'learnpress' ),
							'return_value' => 'yes',
						],
						[
							'name'         => 'toggle_content',
							'label'        => esc_html__( 'Toggle Content', 'learnpress' ),
							'type'         => Controls_Manager::SWITCHER,
							'default'      => 'no',
							'label_on'     => esc_html__( 'Show', 'learnpress' ),
							'label_off'    => esc_html__( 'Hide', 'learnpress' ),
							'return_value' => 'yes',
							'condition'    => [
								'enable_heading' => 'yes',
							],
						],
						[
							'name'         => 'default_toggle_on',
							'label'        => esc_html__( 'Default Toggle On', 'learnpress' ),
							'type'         => Controls_Manager::SWITCHER,
							'default'      => 'yes',
							'label_on'     => esc_html__( 'Show', 'learnpress' ),
							'label_off'    => esc_html__( 'Hide', 'learnpress' ),
							'return_value' => 'yes',
							'condition'    => [
								'enable_heading' => 'yes',
								'toggle_content' => 'yes',
							],
						],
						[ 'method' => 'end_popover' ],
					],
					'prevent_empty' => false,
					'title_field'   => '{{{ item_fields }}}',
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'extra_option',
		esc_html__( 'Extra Option', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'show_in_rest',
				esc_html__( 'Load widget via REST', 'learnpress' ),
				'no',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
				]
			),
			LPElementorControls::add_control_type(
				'search_suggestion',
				esc_html__( 'Enable Keyword Search Suggestion', 'learnpress' ),
				'0',
				Controls_Manager::SWITCHER,
				[
					'1'            => esc_html__( 'Yes', 'learnpress' ),
					'0'            => esc_html__( 'No', 'learnpress' ),
					'return_value' => '1',
				]
			),
			LPElementorControls::add_control_type(
				'filter_toggle_button',
				esc_html__( 'Filter Toggle Button', 'learnpress' ),
				'no',
				Controls_Manager::POPOVER_TOGGLE,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
				]
			),
			'popover_start' => [
				'method' => 'start_popover',
			],
			LPElementorControls::add_control_type(
				'enable_filter_button',
				esc_html__( 'Filter Toggle Button', 'learnpress' ),
				'no',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Show', 'learnpress' ),
					'label_off'    => esc_html__( 'Hide', 'learnpress' ),
					'return_value' => 'yes',
				]
			),
			LPElementorControls::add_control_type(
				'text_filter_button',
				esc_html__( 'Text Button', 'learnpress' ),
				esc_html__( 'Filter', 'learnpress' ),
				Controls_Manager::TEXT,
				[
					'label_block' => false,
					'condition'   => [
						'enable_filter_button' => 'yes',
					]
				]
			),
			LPElementorControls::add_control_type(
				'enable_icon_filter_button',
				esc_html__( 'Button Icon', 'learnpress' ),
				'no',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Show', 'learnpress' ),
					'label_off'    => esc_html__( 'Hide', 'learnpress' ),
					'return_value' => 'yes',
					'condition'    => [
						'enable_filter_button' => 'yes',
					]
				]
			),
			LPElementorControls::add_control_type(
				'icon_filter_button',
				esc_html__( 'Icon', 'learnpress' ),
				[],
				Controls_Manager::ICONS,
				[
					'skin'        => 'inline',
					'label_block' => false,
					'condition'   => [
						'enable_filter_button'      => 'yes',
						'enable_icon_filter_button' => 'yes',
					]
				]
			),
			LPElementorControls::add_control_type_select(
				'icon_position',
				esc_html__( 'Icon Position', 'learnpress' ),
				[
					'left'  => esc_html__( 'Before', 'learnpress' ),
					'right' => esc_html__( 'After', 'learnpress' ),
				],
				'left',
				[
					'condition' => [
						'enable_filter_button'      => 'yes',
						'enable_icon_filter_button' => 'yes',
					]
				]
			),
			LPElementorControls::add_control_type(
				'filter_selected_number',
				esc_html__( 'Filter Selected Number', 'learnpress' ),
				'yes',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'condition'    => [
						'enable_filter_button' => 'yes',
					]
				]
            ),
            LPElementorControls::add_responsive_control_type(
                "filter_section_width",
                esc_html__( 'Width', 'learnpress' ),
                [
                    'size' => 300,
                ],
                Controls_Manager::SLIDER,
                [
                    'size_units' => array( 'px', '%', 'custom' ),
                    'range'      => array(
                        'px' => array(
                            'min' => 1,
                            'max' => 1000,
                            'step'=> 5
                        ),
                    ),
                    'selectors'  => array(
                        '{{WRAPPER}} .lp-form-course-filter' => 'width: {{SIZE}}{{UNIT}};',
                    ),
                    'condition' => [
                        'enable_filter_button'    => 'yes',
                    ] 
                ]
            ),
            'popover_end' => [
                'method' => 'end_popover',
            ],
			LPElementorControls::add_control_type(
				'filter_selected_list',
				esc_html__( 'Filter Selected List', 'learnpress' ),
				'no',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
				]
			),
		]
	),
	[]
);

$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'filter_section',
		esc_html__( 'Section', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
			'filter_section_margin'     => LPElementorControls::add_responsive_control_type(
				'filter_section_margin',
				esc_html__( 'Margin', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .lp-form-course-filter' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			'filter_section_padding'    => LPElementorControls::add_responsive_control_type(
				'filter_section_padding',
				esc_html__( 'Padding', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .lp-form-course-filter' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			'filter_section_border'     => LPElementorControls::add_group_control_type(
				'filter_section_border',
				Group_Control_Border::get_type(),
				'{{WRAPPER}} .lp-form-course-filter'
			),
			'filter_section_background' => LPElementorControls::add_control_type_color(
				'filter_section_background',
				esc_html__( 'Background', 'learnpress' ),
				[ '{{WRAPPER}} .lp-form-course-filter' => 'background: {{VALUE}}' ]
			),
			'filter_section_radius'     => LPElementorControls::add_responsive_control_type(
				'filter_section_radius',
				esc_html__( 'Radius', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .lp-form-course-filter' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			'filter_section_Shadow'     => LPElementorControls::add_group_control_type(
				'filter_section_Shadow',
				Group_Control_Box_Shadow::get_type(),
				'{{WRAPPER}} .lp-form-course-filter'
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'filter_item',
		esc_html__( 'Item', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
			'item_margin'     => LPElementorControls::add_responsive_control_type(
				'item_margin',
				esc_html__( 'Margin', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .lp-form-course-filter__item' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			'item_padding'    => LPElementorControls::add_responsive_control_type(
				'item_padding',
				esc_html__( 'Padding', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .lp-form-course-filter__item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			'item_border'     => LPElementorControls::add_group_control_type(
				'item_border',
				Group_Control_Border::get_type(),
				'{{WRAPPER}} .lp-form-course-filter__item'
			),
			'item_background' => LPElementorControls::add_control_type_color(
				'layout_background',
				esc_html__( 'Background', 'learnpress' ),
				[ '{{WRAPPER}} .lp-form-course-filter__item' => 'background: {{VALUE}}' ]
			),
			'item_radius'     => LPElementorControls::add_responsive_control_type(
				'item_radius',
				esc_html__( 'Radius', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .lp-form-course-filter__item' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
            'toggle_offset_x'    => LPElementorControls::add_responsive_control_type(  
                'toggle_offset_x',
                esc_html__( 'Toggle Offset X (px)', 'learnpress' ),
                '',
                Controls_Manager::NUMBER,
                [
                    'label_block' => false,
                    'selectors'   => array(
                        '{{WRAPPER}} .toggle-content >.icon-toggle-filter' => 'right:{{VALUE}}px',
                    ),
                ]
            ),
            'toggle_offset_y'    => LPElementorControls::add_responsive_control_type(  
                'toggle_offset_y',
                esc_html__( 'Toggle Offset Y (px)', 'learnpress' ),
                '',
                Controls_Manager::NUMBER,
                [
                    'label_block' => false,
                    'selectors'   => array(
                        '{{WRAPPER}} .toggle-content >.icon-toggle-filter' => 'top:{{VALUE}}px',
                    ),
                ]
            ),
		]
	),
	LPElementorControls::add_fields_in_section(
		'filter_title',
		esc_html__( 'Title', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'filter_title',
			'.lp-form-course-filter .lp-form-course-filter__title',
			[],
			[ 'text_display','text_background', 'text_background_hover' ]
		)
	),
	LPElementorControls::add_fields_in_section(
		'filter_content',
		esc_html__( 'Label', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		array_merge(
			LPElementorControls::add_controls_style_text(
				'filter_content',
				'.lp-form-course-filter .lp-form-course-filter__item .lp-form-course-filter__content label',
				[],
				[ 'text_display', 'text_shadow', 'text_background', 'text_background_hover' ]
			),
			[
				'horizontal_align' => LPElementorControls::add_responsive_control_type(
					'horizontal_align',
					esc_html__( 'Horizontal Align', 'learnpress' ),
					'',
					Controls_Manager::CHOOSE,
					[
						'options'   => [
							'row-reverse' => [
								'title' => esc_html__( 'Left', 'learnpress' ),
								'icon'  => 'eicon-h-align-left',
							],
							'row'         => [
								'title' => esc_html__( 'Right', 'learnpress' ),
								'icon'  => 'eicon-h-align-right',
							],
						],
						'selectors' => [
							'{{WRAPPER}} .lp-form-course-filter__item .lp-form-course-filter__content .lp-course-filter__field' => 'flex-direction: {{VALUE}};',
						],
					]
				),
			]
		)
	),
	LPElementorControls::add_fields_in_section(
		'filter_count',
		esc_html__( 'Count', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'filter_count',
			'.lp-form-course-filter .lp-form-course-filter__item .lp-form-course-filter__content .count',
			[],
			[ 'text_display','text_shadow', 'text_background', 'text_background_hover' ]
		)
	),
	LPElementorControls::add_fields_in_section(
		'input_search',
		esc_html__( 'Search', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'input_search',
			'.lp-form-course-filter__content .lp-course-filter-search-field input',
			[],
			[ 'text_display','text_shadow', 'text_color_hover', 'text_background', 'text_background_hover' ]
		)
	),
	LPElementorControls::add_fields_in_section(
		'btn_submit',
		esc_html__( 'Button Submit', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'btn_submit',
			'.course-filter-submit',
			[
				'btn_submit_align' => LPElementorControls::add_responsive_control_type(
					'btn_submit_align',
					esc_html__( 'Alignment', 'learnpress' ),
					'',
					Controls_Manager::CHOOSE,
					[
						'options'   => [
							'left'   => [
								'title' => esc_html__( 'Left', 'learnpress' ),
								'icon'  => 'eicon-text-align-left',
							],
							'center' => [
								'title' => esc_html__( 'Center', 'learnpress' ),
								'icon'  => 'eicon-text-align-center',
							],
							'right'  => [
								'title' => esc_html__( 'Right', 'learnpress' ),
								'icon'  => 'eicon-text-align-right',
							],
						],
						'selectors' => [
							'{{WRAPPER}} .course-filter-submit' => 'text-align: {{VALUE}};',
						],
					]
				),
				'btn_submit_width' => LPElementorControls::add_responsive_control_type(
					'btn_submit_width',
					esc_html__( 'Width', 'learnpress' ),
					[],
					Controls_Manager::SLIDER,
					[
						'size_units' => array( 'px', '%', 'custom' ),
						'range'      => array(
							'px' => array(
								'min'  => 1,
								'max'  => 500,
								'step' => 5,
							),
						),
						'selectors'  => array(
							'{{WRAPPER}} .course-filter-submit' => 'width: {{SIZE}}{{UNIT}};',
						),
					]
				),
			],
			[ 'text_display' ]
		)
	),
	LPElementorControls::add_fields_in_section(
		'btn_reset',
		esc_html__( 'Button Reset', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'btn_reset',
			'.course-filter-reset',
			[
				'btn_reset_align'    => LPElementorControls::add_responsive_control_type(
					'btn_reset_align',
					esc_html__( 'Alignment', 'learnpress' ),
					'',
					Controls_Manager::CHOOSE,
					[
						'options'   => [
							'left'   => [
								'title' => esc_html__( 'Left', 'learnpress' ),
								'icon'  => 'eicon-text-align-left',
							],
							'center' => [
								'title' => esc_html__( 'Center', 'learnpress' ),
								'icon'  => 'eicon-text-align-center',
							],
							'right'  => [
								'title' => esc_html__( 'Right', 'learnpress' ),
								'icon'  => 'eicon-text-align-right',
							],
						],
						'selectors' => [
							'{{WRAPPER}} .course-filter-reset' => 'text-align: {{VALUE}};',
						],
					]
				),
				'btn_reset_width'    => LPElementorControls::add_responsive_control_type(
					'btn_reset_width',
					esc_html__( 'Width', 'learnpress' ),
					[],
					Controls_Manager::SLIDER,
					[
						'size_units' => array( 'px', '%', 'custom' ),
						'range'      => array(
							'px' => array(
								'min'  => 1,
								'max'  => 500,
								'step' => 5,
							),
						),
						'selectors'  => array(
							'{{WRAPPER}} .course-filter-reset' => 'width: {{SIZE}}{{UNIT}};',
						),
					]
				),
				'btn_reset_position' => LPElementorControls::add_control_type_select(
					'btn_reset_position',
					esc_html__( 'Position', 'learnpress' ),
					[
						'static'   => esc_html__( 'Static', 'learnpress' ),
						'absolute' => esc_html__( 'Absolute', 'learnpress' ),
					],
					'static',
					[
						'selectors' => [
							'{{WRAPPER}} .lp-form-course-filter .course-filter-reset' => 'position: {{VALUE}};',
						],
					]
				),
				'reset_offset_x'     => LPElementorControls::add_responsive_control_type(
					'reset_offset_x',
					esc_html__( 'Offset X (px)', 'learnpress' ),
					'',
					Controls_Manager::NUMBER,
					[
						'label_block' => false,
						'selectors'   => array(
							'{{WRAPPER}} .lp-form-course-filter .course-filter-reset' => 'right:{{VALUE}}px',
						),
						'condition'   => [
							'btn_reset_position' => 'absolute',
						],
					]
				),
				'reset_offset_y'     => LPElementorControls::add_responsive_control_type(
					'reset_offset_y',
					esc_html__( 'Offset Y (px)', 'learnpress' ),
					'',
					Controls_Manager::NUMBER,
					[
						'label_block' => false,
						'selectors'   => array(
							'{{WRAPPER}} .lp-form-course-filter .course-filter-reset' => 'top:{{VALUE}}px',
						),
						'condition'   => [
							'btn_reset_position' => 'absolute',
						],
					]
				),
			],
			[ 'text_display' ]
		)
	),
	LPElementorControls::add_fields_in_section(
		'btn_popup',
		esc_html__( 'Button Popup', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		array_merge(
			LPElementorControls::add_controls_style_button(
				'btn_popup',
				'.lp-button-popup',
				[
					'btn_popup_align' => LPElementorControls::add_responsive_control_type(
						'btn_popup_align',
						esc_html__( 'Alignment', 'learnpress' ),
						'',
						Controls_Manager::CHOOSE,
						[
							'options'   => [
								'left'   => [
									'title' => esc_html__( 'Left', 'learnpress' ),
									'icon'  => 'eicon-text-align-left',
								],
								'center' => [
									'title' => esc_html__( 'Center', 'learnpress' ),
									'icon'  => 'eicon-text-align-center',
								],
								'right'  => [
									'title' => esc_html__( 'Right', 'learnpress' ),
									'icon'  => 'eicon-text-align-right',
								],
							],
							'selectors' => [
								'{{WRAPPER}} .lp-button-popup' => 'text-align: {{VALUE}};',
							],
						]
					),
					'btn_popup_width' => LPElementorControls::add_responsive_control_type(
						'btn_popup_width',
						esc_html__( 'Width', 'learnpress' ),
						[],
						Controls_Manager::SLIDER,
						[
							'size_units' => array( 'px', '%', 'custom' ),
							'range'      => array(
								'px' => array(
									'min'  => 1,
									'max'  => 500,
									'step' => 5,
								),
							),
							'selectors'  => array(
								'{{WRAPPER}} .lp-button-popup' => 'width: {{SIZE}}{{UNIT}};',
							),
						]
					),
				],
				[ 'text_display' ]
			),
			[
				'heading_selected_list' => LPElementorControls::add_control_type(
					'heading_selected_list',
					esc_html__( 'Selected List', 'learnpress' ),
					'',
					Controls_Manager::HEADING,
					[
						'separator' => 'before',
					]
				),
			],
			LPElementorControls::add_controls_style_button(
				'selected_list',
				'.selected-list span',
				[],
				[ 'text_display', 'text_shadow' ]
			),
			[
				'heading_selected_number' => LPElementorControls::add_control_type(
					'heading_selected_number',
					esc_html__( 'Selected Number', 'learnpress' ),
					'',
					Controls_Manager::HEADING,
					[
						'separator' => 'before',
					]
				),
			],
			LPElementorControls::add_controls_style_button(
				'selected_number',
				'.selected-filter',
				[
					'selected_number_width' => LPElementorControls::add_responsive_control_type(
						'selected_number_width',
						esc_html__( 'Width', 'learnpress' ),
						[],
						Controls_Manager::SLIDER,
						[
							'size_units' => array( 'px', '%', 'custom' ),
							'range'      => array(
								'px' => array(
									'min'  => 1,
									'max'  => 50,
									'step' => 1,
								),
							),
							'selectors'  => array(
								'{{WRAPPER}} .selected-filter' => 'width: {{SIZE}}{{UNIT}};',
							),
						]
					),
				],
				[ 'text_display', 'text_shadow' ]
			)
		)
	)
);


return apply_filters(
	'learn-press/elementor/course/filter-course-el',
	array_merge(
		apply_filters(
			'learn-press/elementor/course/filter-course-el/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/course/filter-course-el/tab-styles',
			$style_fields
		)
	)
);

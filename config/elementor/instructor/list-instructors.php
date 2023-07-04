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
						// Toggle Style Ul
						[
							'name'         => 'toggle-ul-style',
							'label'        => esc_html__( 'Ul Style', 'learnpress' ),
							'type'         => Controls_Manager::POPOVER_TOGGLE,
							'label_off'    => esc_html__( 'Default', 'learnpress' ),
							'label_on'     => esc_html__( 'Custom', 'learnpress' ),
							'return_value' => 'yes',
						],
						[
							'method' => 'start_popover',
						],
						[
							'name'        => 'grid_columns',
							'label'       => esc_html__( 'Column number', 'learnpress' ),
							'method'      => 'add_responsive_control',
							'type'        => Controls_Manager::NUMBER,
							'min'         => 1,
							'max'         => 6,
							'default'     => 2,
							'description' => 'Number of columns to show.',
							'selectors'   => [
								'{{WRAPPER}} {{CURRENT_ITEM}} .list-instructors' => 'display: grid;list-style: none;grid-template-columns: repeat({{VALUE}}, 1fr);padding: 0;margin: 0;',
							],
						],
						[
							'name'        => 'gap_column',
							'label'       => esc_html__( 'Gap', 'learnpress' ),
							'method'      => 'add_responsive_control',
							'type'        => Controls_Manager::SLIDER,
							'default'     => [
								'unit' => 'px',
								'size' => 10,
							],
							'unit'        => 'px',
							'min'         => 0,
							'max'         => 100,
							'description' => 'Space between items. (px)',
							'selectors'   => [
								'{{WRAPPER}} {{CURRENT_ITEM}} .list-instructors' => 'grid-gap: {{SIZE}}{{UNIT}};',
							],
						],
						[
							'name'      => 'item_padding',
							'label'     => esc_html__( 'Item Padding', 'learnpress' ),
							'method'    => 'add_responsive_control',
							'type'      => Controls_Manager::DIMENSIONS,
							'default'   => [
								'top'    => '10',
								'right'  => '10',
								'bottom' => '10',
								'left'   => '10',
							],
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}} .list-instructors .item-instructor' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
							],
						],
						[ 'method' => 'end_popover' ],
						// Toggle Item Border
						[
							'name'  => 'toggle-item-border',
							'label' => esc_html__( 'Item border', 'learnpress' ),
							'type'  => Controls_Manager::POPOVER_TOGGLE,
						],
						[
							'method' => 'start_popover',
						],
						[
							'name'           => 'item_border',
							'label'          => esc_html__( 'Item Border', 'learnpress' ),
							'method'         => 'add_group_control',
							'type'           => Group_Control_Border::get_type(),
							'description'    => 'Number of columns to show.',
							'selector'       => '{{WRAPPER}} {{CURRENT_ITEM}} .list-instructors .item-instructor',
							'fields_options' => [
								'border' => [
									'default' => 'none',
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
						],
						[
							'name'      => 'item_border_radius',
							'label'     => esc_html__( 'Border Radius', 'learnpress' ),
							'type'      => Controls_Manager::DIMENSIONS,
							'selectors' => [
								'{{WRAPPER}} {{CURRENT_ITEM}} .list-instructors .item-instructor' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
							],
						],
						[ 'method' => 'end_popover' ],
						// Toggle Custom Css
						[
							'name'  => 'toggle-custom-css',
							'label' => esc_html__( 'Advanced Css', 'learnpress' ),
							'type'  => Controls_Manager::POPOVER_TOGGLE,
						],
						[
							'method' => 'start_popover',
						],
						[
							'name'        => 'layout_custom_css',
							'label'       => esc_html__( 'Custom CSS', 'learnpress' ),
							'type'        => Controls_Manager::CODE,
							'label_block' => true,
							'language'    => 'css',
							'description' => 'Should start with selector before style. Ex: selector .[className] {color: red;}',
						],
						[ 'method' => 'end_popover' ],
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
					'min'         => - 1,
					'max'         => 100,
					'description' => 'Number of items to show. Enter -1 to show all instructors.',
				]
			),
		]
	),
	[]
);

// Fields tab style
$style_fields = [];

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

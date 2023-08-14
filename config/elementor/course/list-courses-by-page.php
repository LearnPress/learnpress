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
			'layout_default'   => LPElementorControls::add_control_type(
				'courses_item_layout_default',
				'Layout Default',
				'grid',
				Controls_Manager::CHOOSE,
				[
					'options'   => [
						'grid' => [
							'title' => esc_html__( 'Grid', 'learnpress' ),
							'icon'  => 'eicon-apps',
						],
						'list' => [
							'title' => esc_html__( 'List', 'learnpress' ),
							'icon'  => 'eicon-menu-bar',
						],
					],
					'toggle'    => false,

				]
			),
			'order_by_default' => LPElementorControls::add_control_type_select(
				'order_by',
				esc_html__( 'Order By Default', 'learnpress' ),
				[
					'name'      => esc_html__( 'Name a-z', 'learnpress' ),
					'desc_name' => esc_html__( 'Name z-a', 'learnpress' ),
				],
				'post_date'
			),
			'courses_per_page' => LPElementorControls::add_control_type(
				'courses_per_page',
				esc_html__( 'Courses Per Page', 'learnpress' ),
				8,
				Controls_Manager::NUMBER
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'courses_layout',
		esc_html__( 'Layout Courses', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'courses_layout',
				'',
				'{{courses_order_by}}{{course_items}}{{courses_pagination_number}}',
				Controls_Manager::WYSIWYG,
				[
					'description' => esc_html__( 'Enter the layout for each item course. You can use the following variables: {{courses_order_by}}{{course_items}}{{courses_pagination_number}}', 'learnpress' ),
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'courses_item_layout',
		esc_html__( 'Layout Item Course', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'courses_item_layout',
				'',
				'{{course_title}}{{course_image}}{{course_url}}',
				Controls_Manager::WYSIWYG,
				[
					'description' => esc_html__( 'Enter the layout for each item course. You can use the following variables: {{course_title}}, {{course_image}}, {{course_url}}', 'learnpress' ),
				]
			),
		]
	),
	[]
);

// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'style_layout',
		esc_html__( 'Layout', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
			LPElementorControls::add_control_type(
				'style_courses_item_layout',
				'Layout Type',
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
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'style_title',
		esc_html__( 'Course Title', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'title',
			'.list-courses-elm .course-title'
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

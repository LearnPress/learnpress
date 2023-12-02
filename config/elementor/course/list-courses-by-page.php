<?php
/**
 * Elementor Controls for widget Courses.
 *
 * @since 4.2.3
 * @version 1.0.1
 */

use Elementor\Controls_Manager;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$option_data = [];
if ( isset( $options ) ) {
	$option_data = $options;
}

// Fields tab content
$content_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'skin',
		esc_html__( 'Skin', 'learnpress' )
	),
	LPElementorControls::add_fields_in_section(
		'option_load_rest_api',
		esc_html__( 'Option load REST API', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
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
				'yes',
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
		]
	),
	LPElementorControls::add_fields_in_section(
		'layout',
		esc_html__( 'Layout', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			'el_result_count'    => LPElementorControls::add_control_type(
				'el_result_count',
				'Show result count',
				'yes',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			),
			'el_sorting'    => LPElementorControls::add_control_type(
				'el_sorting',
				'Show sorting',
				'yes',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'query',
		esc_html__( 'Query', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			'courses_limit'             => LPElementorControls::add_control_type(
				'courses_limit',
				esc_html__( 'Courses limit', 'learnpress' ),
				0,
				Controls_Manager::NUMBER,
				[
					'min'  => 0,
					'step' => 1,
					'description' => esc_html__( 'Number courses you when to query, default 0 is no limit', 'learnpress' ),
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
					'description' => esc_html__( 'Default 0 is show all of Courses Limit', 'learnpress' ),
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
		]
	),
	[]
);

// Fields tab style
$style_fields = array_merge(
	[],
	[]
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

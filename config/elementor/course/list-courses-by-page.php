<?php
/**
 * Elementor Controls for widget Courses.
 *
 * @since 4.2.3
 * @version 1.0.1
 */

use Elementor\Controls_Manager;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;
use LearnPress\Models\Courses;

$option_data = [];
if ( isset( $options ) ) {
	$option_data = $options;
}

$filter              = new LP_Course_Filter();
$filter->limit       = - 1;
$filter->only_fields = array( 'ID', 'post_title' );
$courses_obj         = (array) Courses::get_courses( $filter );
$courses             = [];
$categories          = [];

// Only show courses and categories in Admin
if ( is_admin() ) {
	foreach ( $courses_obj as $course ) {
		$courses[ $course->ID ] = $course->post_title;
	}

	$categories_obj = LP_Course::get_all_categories();
	foreach ( $categories_obj as $category ) {
		$categories[ $category->term_id ] = $category->name;
	}
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
			'courses_rest'              => LPElementorControls::add_control_type(
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
			'courses_rest_no_load_page' => LPElementorControls::add_control_type(
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
			'el_result_count' => LPElementorControls::add_control_type(
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
			'el_sorting'      => LPElementorControls::add_control_type(
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
			'courses_limit'            => LPElementorControls::add_control_type(
				'courses_limit',
				esc_html__( 'Courses limit', 'learnpress' ),
				0,
				Controls_Manager::NUMBER,
				[
					'min'         => 0,
					'step'        => 1,
					'description' => esc_html__( 'Total courses you want to query, default 0 is no limit', 'learnpress' ),
				]
			),
			'courses_per_page'         => LPElementorControls::add_control_type(
				'courses_per_page',
				esc_html__( 'Courses Per Page', 'learnpress' ),
				8,
				Controls_Manager::NUMBER,
				[
					'min'         => 0,
					'step'        => 1,
					'description' => esc_html__( 'Number courses show on 1 page. Default 0 is show all of Courses Limit', 'learnpress' ),
				]
			),
			'courses_order_by_default' => LPElementorControls::add_control_type_select(
				'courses_order_by_default',
				esc_html__( 'Order By Default', 'learnpress' ),
				[
					'post_date'       => esc_html__( 'Newest', 'learnpress' ),
					'post_title'      => esc_html__( 'Title a-z', 'learnpress' ),
					'post_title_desc' => esc_html__( 'Title z-a', 'learnpress' ),
					'price'           => esc_html__( 'Price High to Low', 'learnpress' ),
					'price_low'       => esc_html__( 'Price Low to High', 'learnpress' ),
					'popular'         => esc_html__( 'Popular', 'learnpress' ),
				],
				'post_date'
			),
			'courses_category_ids'             => LPElementorControls::add_control_type(
				'courses_category_ids',
				esc_html__( 'Select Categories', 'learnpress' ),
				[],
				Controls_Manager::SELECT2,
				[
					'multiple' => true,
					'options'  => $categories,
				]
			),
		]
	),
	LPElementorControls::add_fields_in_section(
		'pagination',
		esc_html__( 'Pagination', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			'pagination_type' => LPElementorControls::add_control_type_select(
				'pagination_type',
				esc_html__( 'Pagination type', 'learnpress' ),
				[
					''          => esc_html__( 'Nonce', 'learnpress' ),
					'number'    => esc_html__( 'Number', 'learnpress' ),
					'load-more' => esc_html__( 'Load more', 'learnpress' ),
					'infinite'  => esc_html__( 'Infinite scroll', 'learnpress' ),
				],
				'number'
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

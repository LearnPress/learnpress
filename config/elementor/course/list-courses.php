<?php
/**
 * Elementor Controls for widget Become a teacher settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$filter              = new LP_Course_Filter();
$filter->limit       = -1;
$filter->only_fields = array( 'ID', 'post_title' );
$courses_obj         = (array) LP_Course::get_courses( $filter );
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
$content_fields = LPElementorControls::add_fields_in_section(
	'content',
	esc_html__( 'Content', 'learnpress' ),
	Controls_Manager::TAB_CONTENT,
	[
		'layout'       => LPElementorControls::add_control_type_select(
			'layout',
			esc_html__( 'Layout', 'learnpress' ),
			[
				'list' => esc_html__( 'List', 'learnpress' ),
				'grid' => esc_html__( 'Grid', 'learnpress' ),
			],
			'grid'
		),
		'courses_ids'  => LPElementorControls::add_control_type(
			'courses_ids',
			esc_html__( 'Select Courses', 'learnpress' ),
			[],
			Controls_Manager::SELECT2,
			[
				'multiple' => true,
				'options'  => $courses,
			]
		),
		'category_ids' => LPElementorControls::add_control_type(
			'category_ids',
			esc_html__( 'Select Categories', 'learnpress' ),
			[],
			Controls_Manager::SELECT2,
			[
				'multiple' => true,
				'options'  => $categories,
			]
		),
		'sort_in'      => LPElementorControls::add_control_type_select(
			'sort_in',
			esc_html__( 'Sort In', 'learnpress' ),
			[
				''         => esc_html__( 'Default', 'learnpress' ),
				'recent'   => esc_html__( 'Recent', 'learnpress' ),
				'popular'  => esc_html__( 'Popular', 'learnpress' ),
				'featured' => esc_html__( 'Featured', 'learnpress' ),
			],
			''
		),
		'order_by'     => LPElementorControls::add_control_type_select(
			'order_by',
			esc_html__( 'Order By', 'learnpress' ),
			[
				'DESC' => esc_html__( 'DESC', 'learnpress' ),
				'ASC'  => esc_html__( 'ASC', 'learnpress' ),
			],
			'ASC'
		),
		'limit'        => LPElementorControls::add_control_type(
			'limit',
			esc_html__( 'Limit', 'learnpress' ),
			5,
			Controls_Manager::NUMBER,
			[
				'min' => -1,
				'max' => 100,
			]
		),
	]
);

// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'title',
		esc_html__( 'Title Course', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'title',
			'.learn-press-courses .course-item .course-title'
		)
	),
	LPElementorControls::add_fields_in_section(
		'instructor',
		esc_html__( 'Title Instructor', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'instructor',
			'.learn-press-courses .course-item .course-instructor a'
		)
	),
	LPElementorControls::add_fields_in_section(
		'price',
		esc_html__( 'Price', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'price',
			'.learn-press-courses .course-item .course-price'
		)
	),
	LPElementorControls::add_fields_in_section(
		'course_image',
		esc_html__( 'Course Image', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_image(
			'course_image',
			'.learn-press-courses .course-item img'
		)
	)
);

return apply_filters(
	'learn-press/elementor/course/list-courses',
	array_merge(
		apply_filters(
			'learn-press/elementor/course/list-courses/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/course/list-courses/tab-styles',
			$style_fields
		)
	)
);

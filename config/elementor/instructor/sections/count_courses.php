<?php
/**
 * Elementor Controls for widget Instructor Count Courses settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

// Fields tab content
$content_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'wrapper',
		esc_html__( 'Wrapper', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'instructor_id',
				esc_html__( 'Set Instructor ID', 'learnpress' ),
				'',
				Controls_Manager::TEXT,
				[
					'description' => 'If widget include on page is Single Instructor, will be get instructor id automatic, from query var.',
				]
			)
		]
	),
	[]
);

// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'instructor_count_courses',
		esc_html__( 'Instructor Count Courses', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'count_courses',
			'.instructor-total-courses'
		)
	),
	[]
);

return apply_filters(
	'learn-press/elementor/instructor/count-courses',
	array_merge(
		apply_filters(
			'learn-press/elementor/instructor/count-courses/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/instructor/count-courses/tab-styles',
			$style_fields
		)
	)
);

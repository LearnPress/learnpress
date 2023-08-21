<?php
/**
 * Elementor Controls for widget Instructor Avatar settings.
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
		'instructor_avatar',
		esc_html__( 'Instructor avatar', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_image(
			'avatar',
			'.instructor-avatar img'
		)
	),
	[]
);

return apply_filters(
	'learn-press/elementor/instructor/avatar',
	array_merge(
		apply_filters(
			'learn-press/elementor/instructor/avatar/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/instructor/avatar/tab-styles',
			$style_fields
		)
	)
);

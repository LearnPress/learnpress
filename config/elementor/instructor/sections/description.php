<?php
/**
 * Elementor Controls for widget Become a teacher settings.
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
			),
			LPElementorControls::add_control_type(
				'wrapper_tags',
				esc_html__( 'Add html tag wrapper Instructor Description', 'learnpress' ),
				[
					[
						'open_tag'  => '<div class="div">',
						'close_tag' => '</div>',
					],
				],
				Controls_Manager::REPEATER,
				[
					'fields'        => [
						[
							'name'        => 'open_tag',
							'label'       => esc_html__( 'Html Open tag', 'learnpress' ),
							'type'        => Controls_Manager::TEXT,
							'label_block' => true,
						],
						[
							'name'        => 'close_tag',
							'label'       => esc_html__( 'Html Close tag', 'learnpress' ),
							'type'        => Controls_Manager::TEXT,
							'label_block' => true,
						],

					],
					'prevent_empty' => false,
				]
			),
		]
	),
	[]
);

// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'description',
		esc_html__( 'Instructor Description', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'description',
			'.instructor-description'
		)
	),
	[]
);

return apply_filters(
	'learn-press/elementor/instructor/description',
	array_merge(
		apply_filters(
			'learn-press/elementor/instructor/description/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/instructor/description/tab-styles',
			$style_fields
		)
	)
);

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
				'wrapper_tags',
				esc_html__( 'Add html tag wrapper Instructor Name', 'learnpress' ),
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
		'title',
		esc_html__( 'Title Course', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'title',
			'.instructor-display-name'
		)
	),
	[]
);

return apply_filters(
	'learn-press/elementor/instructor/title',
	array_merge(
		apply_filters(
			'learn-press/elementor/instructor/title/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/instructor/title/tab-styles',
			$style_fields
		)
	)
);

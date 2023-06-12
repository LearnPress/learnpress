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
		'layouts',
		esc_html__( 'Layout Item Instructor', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'layouts',
				esc_html__( 'Add layout and drag to top to set Active', 'learnpress' ),
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
							'name'        => 'layout_name',
							'label'       => esc_html__( 'Layout Name', 'learnpress' ),
							'type'        => Controls_Manager::TEXT,
							'label_block' => true,
						],
						[
							'name'        => 'layout_html',
							'label'       => esc_html__( 'Layout HTML', 'learnpress' ),
							'type'        => Controls_Manager::WYSIWYG,
							'label_block' => true,
						],
					],
					'prevent_empty' => false,
					'title_field'   => '{{{ layout_name }}}',
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

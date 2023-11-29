<?php
/**
 * Elementor Controls for widget course filter selected settings.
 *
 * @since 4.2.5
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$content_fields = array_merge(
    LPElementorControls::add_fields_in_section(
		'area_content',
		esc_html__( 'Content', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
        [
            'show_preview'      => LPElementorControls::add_control_type(
				'show_preview',
				esc_html__( 'Preview', 'learnpress' ),
				'yes',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Show', 'learnpress' ),
					'label_off'    => esc_html__( 'Hide', 'learnpress' ),
					'return_value' => 'yes',
				]
            ),
            'text_reset'        => LPElementorControls::add_control_type(
                'text_reset',
                esc_html__( 'Clear Text', 'learnpress' ),
                esc_html__( 'Clear', 'learnpress' ),
                Controls_Manager::TEXT,
                [
                    'label_block' => false,
                ]
            ),
        ]
    ),
    []
);

$style_fields = array_merge(
    LPElementorControls::add_fields_in_section(
		'selected_item',
		esc_html__( 'Selected Item', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
        LPElementorControls::add_controls_style_button(
            'selected_list',
            '.selected-list .selected-item',
            [],
            [ 'text_display', 'text_shadow' ]
        ),
    ),
    LPElementorControls::add_fields_in_section(
		'btn_reset',
		esc_html__( 'Button Reset', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
        LPElementorControls::add_controls_style_button(
            'btn_reset',
            '.course-filter-reset',
            [
                'border_color_hover'    => LPElementorControls::add_control_type_color(
                    'border_color_hover',
                    esc_html__( 'Border Color Hover', 'learnpress' ),
                    [
                        '{{WRAPPER}} .course-filter-reset:hover' => 'border-color: {{VALUE}};'
                    ],
                )
            ],
            [ 'text_display' ]
        ),
    ),
    []
);

return apply_filters(
	'learn-press/elementor/course/course-filter-selected',
    array_merge(
		apply_filters(
			'learn-press/elementor/course/course-filter-selected/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/course/course-filter-selected/tab-styles',
			$style_fields
		)
	)
);
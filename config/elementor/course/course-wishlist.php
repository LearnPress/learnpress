<?php
/**
 * Elementor Controls for widget Course wishlist settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Text_Shadow;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$style_fields = array_merge(
    LPElementorControls::add_fields_in_section(
		'style_button',
        esc_html__( 'Style', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
            'add_button_color'       => LPElementorControls::add_control_type_color(
				'add_button_color',
				esc_html__( 'Color', 'learnpress' ),
				[
					'{{WRAPPER}} .course-wishlist' => 'color: {{VALUE}};',
				]
			),
            'remove_button_color'       => LPElementorControls::add_control_type_color(
				'remove_button_color',
				esc_html__( 'Color Remove', 'learnpress' ),
				[
					'{{WRAPPER}} .course-wishlist.on' => 'color: {{VALUE}};',
				]
			),
			'button_background_color'       => LPElementorControls::add_control_type_color(
				'button_background_color',
				esc_html__( 'Background Color', 'learnpress' ),
				[
					'{{WRAPPER}} .course-wishlist' => 'background-color: {{VALUE}};',
				]
			),
			'button_padding'       => LPElementorControls::add_responsive_control_type(
                'button_padding',
                esc_html__( 'Padding', 'learnpress' ),
                [],
                Controls_Manager::DIMENSIONS,
                [
                    'size_units' => [ 'px', '%', 'custom' ],
                    'selectors'  => array(
                        '{{WRAPPER}} .course-wishlist' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    ),
                ]
            ),
            'button_typography'    => LPElementorControls::add_group_control_type(
				'button_typography',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .course-wishlist'
			),
            'icon_typography'    => LPElementorControls::add_group_control_type(
				'icon_typography',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .course-wishlist:before',
                [
					'label' => esc_html__( 'Icon Typography', 'learnpress' ),
				]
			),
            'button_shadow'           => LPElementorControls::add_group_control_type(
				'button_shadow',
				Group_Control_Text_Shadow::get_type(),
				'{{WRAPPER}} .course-wishlist'
			)
		]
    )
);

return apply_filters(
	'learn-press/elementor/course-wishlist',
	apply_filters(
		'learn-press/elementor/course-wishlist/tab-styles',
		$style_fields
	)
);
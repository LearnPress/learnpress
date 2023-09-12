<?php
/**
 * Elementor Controls for widget Course price settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'style_regular_price',
        esc_html__( 'Regular Price', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
			'price_align'         => LPElementorControls::add_responsive_control_type(
				'price_align',
				__( 'Alignment', 'learnpress' ),
				'left',
				Controls_Manager::CHOOSE,
				[
					'options'   => [
						'left'   => [
							'title' => esc_html__( 'Left', 'thim-elementor-kit' ),
							'icon'  => 'eicon-text-align-left',
						],
						'center' => [
							'title' => esc_html__( 'Center', 'thim-elementor-kit' ),
							'icon'  => 'eicon-text-align-center',
						],
						'right'  => [
							'title' => esc_html__( 'Right', 'thim-elementor-kit' ),
							'icon'  => 'eicon-text-align-right',
						],
					],
					'toggle'    => false,
					'selectors' => [
						'{{WRAPPER}}' => 'text-align: {{VALUE}};',
					],
				]
			),
			'regular_price_color'       => LPElementorControls::add_control_type_color(
				'regular_price_color',
				esc_html__( 'Color', 'learnpress' ),
				[
					'{{WRAPPER}} .course-item-price' => 'color: {{VALUE}};',
				]
			),
			'regular_price_typography'    => LPElementorControls::add_group_control_type(
				'regular_price_typography',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .course-item-price'
			),
		]
    ),
    LPElementorControls::add_fields_in_section(
		'style_origin_price',
        esc_html__( 'Origin Price', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
			'origin_price_color'       => LPElementorControls::add_control_type_color(
				'origin_price_color',
				esc_html__( 'Color', 'learnpress' ),
				[
					'{{WRAPPER}} .course-item-price .origin-price' => 'color: {{VALUE}};',
				]
			),
			'origin_price_typography'    => LPElementorControls::add_group_control_type(
				'origin_price_typography',
				Group_Control_Typography::get_type(),
				'{{WRAPPER}} .course-item-price .origin-price'
			),
			'origin_price_padding'       => LPElementorControls::add_responsive_control_type(
				'origin_price_padding',
				esc_html__( 'Padding', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						'{{WRAPPER}} .course-item-price .origin-price' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			)
		]
	),
	LPElementorControls::add_fields_in_section(
		'style_free_price',
        esc_html__( 'Free Price', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		[
			'free_price_color'       => LPElementorControls::add_control_type_color(
				'free_price_color',
				esc_html__( 'Color', 'learnpress' ),
				[
					'{{WRAPPER}} .course-item-price .free' => 'color: {{VALUE}};',
				]
			)
		]
	)
);

return apply_filters(
	'learn-press/elementor/course-price',
	apply_filters(
		'learn-press/elementor/course-price/tab-styles',
		$style_fields
	)
);
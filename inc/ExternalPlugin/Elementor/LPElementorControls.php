<?php
/**
 * Class LP_Elementor_Widgets
 *
 * @since 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\ExternalPlugin\Elementor;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Text_Shadow;
use Elementor\Group_Control_Typography;
use phpDocumentor\Reflection\DocBlock\Tags\Param;

class LPElementorControls {
	/**
	 * Start section.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $tab
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_start_section( string $id, string $label,
		string $tab = Controls_Manager::TAB_CONTENT, array $args = [] ): array {
		return [
			'method' => 'start_controls_section',
			'id'     => $id,
			array_merge(
				[
					'label' => $label,
					'tab'   => $tab,
				],
				$args
			),
		];
	}

	/**
	 * Start section.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $tab
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_fields_in_section( string $id_section, string $label_section,
		string $tab = Controls_Manager::TAB_CONTENT, array $fields_inner = [] ): array {
		return array_merge(
			[
				"section_$id_section" => LPElementorControls::add_start_section(
					"section_$id_section",
					$label_section,
					$tab
				),
			],
			$fields_inner,
			[ "end_section_$id_section" => [ 'method' => 'end_controls_section' ] ]
		);
	}

	/**
	 * Declare control type.
	 *
	 * @param string $id
	 * @param string $control_type
	 * @param string $label
	 * @param string|array $default
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_control_type( string $id, string $label, $default = '',
		string $control_type = Controls_Manager::TEXT, array $args = [] ): array {
		return [
			'method' => 'add_control',
			'id'     => $id,
			array_merge(
				[
					'label'   => $label,
					'type'    => $control_type,
					'default' => $default,
				],
				$args
			),
		];
	}

	/**
	 * Declare control type.
	 *
	 * @param string $id
	 * @param string $label
	 * @param array $selector
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_control_type_color( string $id, string $label, array $selector, array $args = [] ): array {
		return self::add_control_type(
			$id,
			$label,
			'',
			Controls_Manager::COLOR,
			array_merge( [ 'selectors' => $selector ], $args )
		);
	}

	/**
	 * Declare control type slider.
	 *
	 * @param string $id
	 * @param string $label
	 * @param float $default
	 * @param string $unit
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_control_type_slider( string $id, string $label, float $default = 0,
		string $unit = 'px', array $args = [] ): array {
		return self::add_control_type(
			$id,
			$label,
			$default,
			Controls_Manager::SLIDER,
			array_merge(
				[
					'range'   => array(
						'px' => array(
							'min'  => 0,
							'max'  => 60,
							'step' => 1,
						),
					),
					'default' => array(
						'size' => $default,
						'unit' => $unit,
					),
				],
				$args
			)
		);
	}

	/**
	 * Declare control type.
	 *
	 * @param string $id
	 * @param string $group_type
	 * @param string $el_selector
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_group_control_type( string $id, string $group_type, string $selector, array $args = [] ): array {
		return [
			'method' => 'add_group_control',
			$group_type,
			array_merge(
				[
					'name'     => $id,
					'selector' => $selector,
				],
				$args
			),
		];
	}

	/**
	 * Group control style for text.
	 *
	 * @param string $name_field
	 * @param string $title
	 * @param string $el_selector
	 *
	 * @return array
	 */
	public static function add_control_style_for_el( string $name_field, string $title, string $el_selector ): array {
		return [
			"section_style_{$name_field}"     => self::add_start_section(
				"section_style_{$name_field}",
				$title,
				Controls_Manager::TAB_STYLE
			),
			"{$name_field}_align"             => [
				'method' => 'add_responsive_control',
				'id'     => "{$name_field}_align",
				[
					'label'     => esc_html__( 'Alignment', 'learnpress' ),
					'type'      => Controls_Manager::CHOOSE,
					'options'   => array(
						'left'   => array(
							'title' => esc_html__( 'Left', 'learnpress' ),
							'icon'  => 'eicon-text-align-left',
						),
						'center' => array(
							'title' => esc_html__( 'Center', 'learnpress' ),
							'icon'  => 'eicon-text-align-center',
						),
						'right'  => array(
							'title' => esc_html__( 'Right', 'learnpress' ),
							'icon'  => 'eicon-text-align-right',
						),
					),
					'selectors' => array(
						'{{WRAPPER}} ' . $el_selector => 'text-align: {{VALUE}}',
					),
				],
			],
			"{$name_field}_color"             => self::add_control_type_color(
				"{$name_field}_color",
				esc_html__( 'Color', 'learnpress' ),
				[ '{{WRAPPER}} ' . $el_selector => 'color: {{VALUE}}' ]
			),
			"{$name_field}_typography"        => self::add_group_control_type(
				"{$name_field}_typography",
				Group_Control_Typography::get_type(),
				"{{WRAPPER}} $el_selector"
			),
			"{$name_field}_shadow"            => self::add_group_control_type(
				"{$name_field}_shadow",
				Group_Control_Text_Shadow::get_type(),
				"{{WRAPPER}} $el_selector"
			),
			"{$name_field}_spacing"           => [
				'method' => 'add_responsive_control',
				"{$name_field}_spacing",
				array(
					'label'     => esc_html__( 'Spacing', 'learnpress' ),
					'type'      => Controls_Manager::SLIDER,
					'range'     => array(
						'px' => array(
							'max' => 100,
						),
					),
					'selectors' => array(
						'{{WRAPPER}} ' . $el_selector => 'margin-bottom: {{SIZE}}{{UNIT}};',
					),
				),
			],
			"end_section_style_{$name_field}" => [
				'method' => 'end_controls_section',
			],
		];
	}

	/**
	 * Group control style for text.
	 *
	 * @param string $prefix_name
	 * @param string $selector
	 *
	 * @return array
	 */
	public static function add_controls_style_button( string $prefix_name, string $selector ): array {
		return [
			"{$prefix_name}_btn_align"          => [
				'method' => 'add_responsive_control',
				'id'     => "{$prefix_name}_align",
				[
					'label'     => esc_html__( 'Alignment', 'learnpress' ),
					'type'      => Controls_Manager::CHOOSE,
					'options'   => array(
						'auto auto auto 0' => array(
							'title' => esc_html__( 'Left', 'learnpress' ),
							'icon'  => 'eicon-text-align-left',
						),
						'0 auto'           => array(
							'title' => esc_html__( 'Center', 'learnpress' ),
							'icon'  => 'eicon-text-align-center',
						),
						'auto 0 auto auto' => array(
							'title' => esc_html__( 'Right', 'learnpress' ),
							'icon'  => 'eicon-text-align-right',
						),
					),
					'selectors' => array(
						"{{WRAPPER}} $selector" => 'display: block; margin: {{VALUE}}',
					),
				],
			],
			"{$prefix_name}_btn_spacing_top"    => self::add_control_type_slider(
				"{$prefix_name}_btn_spacing_top",
				esc_html__( 'Spacing Top', 'learnpress' ),
				0,
				'px',
				[
					'selectors' => array(
						"{{WRAPPER}} $selector" => 'margin-top: {{SIZE}}{{UNIT}};',
					),
				]
			),
			"{$prefix_name}_btn_spacing_bottom" => self::add_control_type_slider(
				"{$prefix_name}_btn_spacing_bottom",
				esc_html__( 'Spacing bottom', 'learnpress' ),
				0,
				'px',
				[
					'selectors' => array(
						"{{WRAPPER}} $selector" => 'margin-bottom: {{SIZE}}{{UNIT}};',
					),
				]
			),
		];
	}

	/**
	 * Group control style for form.
	 *
	 * @param string $name_field
	 * @param string $title
	 * @param string $selector
	 * @param string $field
	 * @param array $groups
	 *
	 * @return array
	 */
	public static function add_control_style_for_form( string $name_field, string $title, string $selector, string $field, array $groups = [] ): array {
		$fields = array_merge(
			[
				"{$name_field}_row_gap"             => self::add_control_type_slider(
					"{$name_field}_row_gap",
					esc_html__( 'Rows Gap', 'learnpress' ),
					0,
					'px',
					[
						'selectors' => array(
							"{{WRAPPER}} $selector $field" => 'margin-bottom: {{SIZE}}{{UNIT}};',
							"{{WRAPPER}} $selector"        => 'margin-bottom: -{{SIZE}}{{UNIT}};',
						),
					]
				),
				"{$name_field}_label_spacing"       => self::add_control_type_slider(
					"{$name_field}_label_spacing",
					esc_html__( 'Label Spacing', 'learnpress' ),
					0,
					'px',
					[
						'range'     => array(
							'px' => array(
								'min'  => 0,
								'max'  => 60,
								'step' => 1,
							),
						),
						'selectors' => array(
							'{{WRAPPER}} ' . $selector . ' label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
						),
					]
				),
				"{$name_field}_label_color"         => self::add_control_type_color(
					"{$name_field}_label_color",
					esc_html__( 'Label Color', 'learnpress' ),
					[ '{{WRAPPER}} ' . $selector . ' label' => 'color: {{VALUE}};' ]
				),
				"{$name_field}_label_typography"    => self::add_group_control_type(
					"{$name_field}_label_typography",
					Group_Control_Typography::get_type(),
					"{{WRAPPER}} $selector label",
					[
						'label' => esc_html__( 'Label Typography', 'learnpress' ),
					]
				),
				"{$name_field}_field_color"         => self::add_control_type_color(
					"{$name_field}_field_color",
					esc_html__( 'Field Color', 'learnpress' ),
					[ '{{WRAPPER}} ' . $selector . ' ' . $field . ' input, {{WRAPPER}} ' . $selector . ' ' . $field . ' textarea' => 'color: {{VALUE}};' ]
				),
				"{$name_field}_field_background"    => self::add_control_type_color(
					"{$name_field}_field_background",
					esc_html__( 'Field Background', 'learnpress' ),
					[ '{{WRAPPER}} ' . $selector . ' ' . $field . ' input, {{WRAPPER}} ' . $selector . ' ' . $field . ' textarea' => 'background-color: {{VALUE}};' ]
				),
				"{$name_field}_field_border"        => self::add_group_control_type(
					"{$name_field}_field_border",
					Group_Control_Border::get_type(),
					"{{WRAPPER}} $selector $field input, {{WRAPPER}} $selector $field textarea",
					[
						'label'       => esc_html__( 'Field Border', 'learnpress' ),
						'placeholder' => '1px',
						'default'     => '1px',
					]
				),
				"{$name_field}_field_border_radius" => self::add_control_type(
					"{$name_field}_field_border_radius",
					esc_html__( 'Field Border Radius', 'learnpress' ),
					'',
					Controls_Manager::DIMENSIONS,
					[
						'default'    => [],
						'size_units' => array( 'px', '%' ),
						'selectors'  => [
							"{{WRAPPER}} $selector $field input, {{WRAPPER}} $selector $field textarea" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						],
					]
				),
			],
			$groups
		);

		return self::add_fields_in_section(
			"form_section_$name_field",
			$title,
			Controls_Manager::TAB_STYLE,
			$fields
		);
	}
}

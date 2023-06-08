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
	 * Responsive control type.
	 *
	 *
	 * @param string $id
	 * @param string $label
	 * @param string|array $default
	 * @param string $control_type
	 * @param array $args
	 *
	 * @return array
	 */
	public static function add_responsive_control_type( string $id, string $label, $default = '',
		string $control_type = Controls_Manager::CHOOSE, array $args = [] ): array {
		return [
			'method' => 'add_responsive_control',
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
	 * control type color.
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
	 * Include or exclude more fields.
	 *
	 * @param array $fields
	 * @param string $prefix_name - prefix name of field for exclude. Format: 'prefix_name'_'field_name_want_exclude'
	 * @param array $include - no need prefix_name, must add by standard format: 'prefix_name'_'btn'_'attribute'
	 * @param array $exclude
	 *
	 * @return array
	 */
	private static function add_group_style_controls( array $fields, string $prefix_name,
		array $include = [], array $exclude = [] ): array {

		if ( ! empty( $include ) ) {
			$fields = array_merge( $fields, $include );
		}

		if ( ! empty( $exclude ) ) {
			foreach ( $exclude as $field ) {
				$field = "{$prefix_name}_{$field}";
				unset( $fields[ $field ] );
			}
		}

		return $fields;
	}

	/**
	 * Group control style for button.
	 *
	 * @param string $prefix_name
	 * @param string $selector
	 * @param array $include
	 * @param array $exclude
	 *
	 * @return array
	 */
	public static function add_controls_style_button( string $prefix_name, string $selector,
		array $include = [], array $exclude = [] ): array {

		$fields = [
			"{$prefix_name}_btn_margin"           => self::add_responsive_control_type(
				"{$prefix_name}_btn_margin",
				esc_html__( 'Margin', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						"{{WRAPPER}} $selector" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			"{$prefix_name}_btn_padding"          => self::add_responsive_control_type(
				"{$prefix_name}_btn_padding",
				esc_html__( 'Padding', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						"{{WRAPPER}} $selector" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			"{$prefix_name}_btn_typography"       => self::add_group_control_type(
				"{$prefix_name}_btn_typography",
				Group_Control_Typography::get_type(),
				"{{WRAPPER}} $selector"
			),
			"{$prefix_name}_btn_color"            => self::add_control_type_color(
				"{$prefix_name}_btn_color",
				esc_html__( 'Text Color', 'learnpress' ),
				[ "{{WRAPPER}} $selector" => 'color: {{VALUE}}' ]
			),
			"{$prefix_name}_btn_color_hover"      => self::add_control_type_color(
				"{$prefix_name}_btn_color_hover",
				esc_html__( 'Text Color Hover', 'learnpress' ),
				[ "{{WRAPPER}} $selector:hover" => 'color: {{VALUE}}' ]
			),
			"{$prefix_name}_btn_background"       => self::add_control_type_color(
				"{$prefix_name}_btn_background",
				esc_html__( 'Background Color', 'learnpress' ),
				[ "{{WRAPPER}} $selector" => 'background: {{VALUE}}' ]
			),
			"{$prefix_name}_btn_background_hover" => self::add_control_type_color(
				"{$prefix_name}_btn_background_hover",
				esc_html__( 'Background Color Hover', 'learnpress' ),
				[ "{{WRAPPER}} $selector:hover" => 'background: {{VALUE}}' ]
			),
			"{$prefix_name}_btn_border"           => self::add_group_control_type(
				"{$prefix_name}_btn_border",
				Group_Control_Border::get_type(),
				"{{WRAPPER}} $selector"
			),
			"{$prefix_name}_btn_border_radius"    => self::add_control_type(
				"{$prefix_name}_btn_border_radius",
				esc_html__( 'Border Radius', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'selectors' => [
						"{{WRAPPER}} $selector" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			),
		];

		return self::add_group_style_controls( $fields, $prefix_name, $include, $exclude );
	}

	/**
	 * Group control style for text.
	 *
	 * @param string $prefix_name
	 * @param string $selector
	 * @param array $include
	 * @param array $exclude
	 *
	 * @return array
	 */
	public static function add_controls_style_text( string $prefix_name, string $selector,
		array $include = [], array $exclude = [] ): array {
		$fields = [
			"{$prefix_name}_text_margin"               => self::add_responsive_control_type(
				"{$prefix_name}_text_margin",
				esc_html__( 'Margin', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						"{{WRAPPER}} $selector" => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			"{$prefix_name}_text_padding"              => self::add_responsive_control_type(
				"{$prefix_name}_text_padding",
				esc_html__( 'Padding', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors'  => array(
						"{{WRAPPER}} $selector" => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					),
				]
			),
			"{$prefix_name}_text_typography"           => self::add_group_control_type(
				"{$prefix_name}_typography",
				Group_Control_Typography::get_type(),
				"{{WRAPPER}} $selector"
			),
			"{$prefix_name}_text_shadow"               => self::add_group_control_type(
				"{$prefix_name}_shadow",
				Group_Control_Text_Shadow::get_type(),
				"{{WRAPPER}} $selector"
			),
			"{$prefix_name}_text_btn_color"            => self::add_control_type_color(
				"{$prefix_name}_btn_color",
				esc_html__( 'Text Color', 'learnpress' ),
				[ "{{WRAPPER}} $selector" => 'color: {{VALUE}}' ]
			),
			"{$prefix_name}_text_btn_color_hover"      => self::add_control_type_color(
				"{$prefix_name}_btn_color_hover",
				esc_html__( 'Text Color Hover', 'learnpress' ),
				[ "{{WRAPPER}} $selector:hover" => 'color: {{VALUE}}' ]
			),
			"{$prefix_name}_text_btn_background"       => self::add_control_type_color(
				"{$prefix_name}_btn_background",
				esc_html__( 'Background Color', 'learnpress' ),
				[ "{{WRAPPER}} $selector" => 'background: {{VALUE}}' ]
			),
			"{$prefix_name}_text_btn_background_hover" => self::add_control_type_color(
				"{$prefix_name}_btn_background_hover",
				esc_html__( 'Background Color Hover', 'learnpress' ),
				[ "{{WRAPPER}} $selector:hover" => 'background: {{VALUE}}' ]
			),
		];

		return self::add_group_style_controls( $fields, $prefix_name, $include, $exclude );
	}
}

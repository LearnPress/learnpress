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
	private static function add_start_section( string $id, string $label,
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
	 * @param string $id_section
	 * @param string $label_section
	 * @param string $tab
	 * @param array $fields_inner
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
	 * Controls Tabs.
	 *
	 * @param string $id
	 * @param array $control_tab_inner
	 *
	 * @return string[]
	 * @since 4.2.3.4
	 * @version 1.0.0
	 */
	public static function add_start_control_tabs( string $id, array $control_tab_inner = [] ): array {
		return array_merge(
			[
				'start_controls_tabs_' . $id => [
					'method' => 'start_controls_tabs',
					'id'     => $id,
				],
			],
			$control_tab_inner,
			[ 'end_control_tabs_' . $id => [ 'method' => 'end_controls_tabs' ] ]
		);
	}

	/**
	 * Controls Tab.
	 *
	 * @param string $id
	 * @param string $label
	 * @param array $controls_inner
	 *
	 * @return string[]
	 * @since 4.2.3.4
	 * @version 1.0.0
	 */
	public static function add_start_control_tab( string $id, string $label = '', array $controls_inner = [] ): array {
		return array_merge(
			[
				'start_controls_tab_' . $id => [
					'method' => 'start_controls_tab',
					'id'     => $id,
					[
						'label' => $label,
					],
				],
			],
			$controls_inner,
			[ 'end_controls_tab_' . $id => [ 'method' => 'end_controls_tab' ] ]
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
	 * control type select.
	 *
	 * @param string $id
	 * @param string $label
	 * @param array $options
	 * @param $default
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_control_type_select( string $id, string $label, array $options,
		$default, array $args = [] ): array {
		return self::add_control_type(
			$id,
			$label,
			$default,
			Controls_Manager::SELECT,
			array_merge(
				[
					'options' => $options,
				],
				$args
			)
		);
	}

	/**
	 * control type select.
	 *
	 * @param string $id
	 * @param string $label
	 * @param array $selectors
	 * @param string $default
	 * @param array $args
	 *
	 * @return string[]
	 */
	public static function add_control_type_switcher( string $id, string $label, array $selectors = [],
		string $default = 'no', array $args = [] ): array {
		return self::add_control_type(
			$id,
			$label,
			$default,
			Controls_Manager::SWITCHER,
			array_merge(
				[
					'selectors' => $selectors,
				],
				$args
			)
		);
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
	 * @param string $selector
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
			"{$prefix_name}_text_display"          => self::add_control_type(
				"{$prefix_name}_text_display",
				esc_html__( 'Display', 'learnpress' ),
				'block',
				Controls_Manager::CHOOSE,
				[
					'options'   => [
						'block'        => array(
							'title' => esc_html__( 'Block', 'thim-elementor-kit' ),
							'icon'  => 'eicon-editor-list-ul',
						),
						'inline-block' => array(
							'title' => esc_html__( 'Inline', 'thim-elementor-kit' ),
							'icon'  => 'eicon-ellipsis-h',
						),
					],
					'selectors' => [
						"{{WRAPPER}} $selector" => 'display: {{VALUE}}',
					],
				]
			),
			"{$prefix_name}_text_margin"           => self::add_responsive_control_type(
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
			"{$prefix_name}_text_padding"          => self::add_responsive_control_type(
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
			"{$prefix_name}_text_typography"       => self::add_group_control_type(
				"{$prefix_name}_typography",
				Group_Control_Typography::get_type(),
				"{{WRAPPER}} $selector"
			),
			"{$prefix_name}_text_shadow"           => self::add_group_control_type(
				"{$prefix_name}_shadow",
				Group_Control_Text_Shadow::get_type(),
				"{{WRAPPER}} $selector"
			),

			"{$prefix_name}_text_color"            => self::add_control_type_color(
				"{$prefix_name}_text_color",
				esc_html__( 'Text Color', 'learnpress' ),
				[ "{{WRAPPER}} $selector" => 'color: {{VALUE}}' ]
			),
			"{$prefix_name}_text_color_hover"      => self::add_control_type_color(
				"{$prefix_name}_text_color_hover",
				esc_html__( 'Text Color Hover', 'learnpress' ),
				[ "{{WRAPPER}} $selector:hover" => 'color: {{VALUE}}' ]
			),
			"{$prefix_name}_text_background"       => self::add_control_type_color(
				"{$prefix_name}_text_background",
				esc_html__( 'Background Color', 'learnpress' ),
				[ "{{WRAPPER}} $selector" => 'background: {{VALUE}}' ]
			),
			"{$prefix_name}_text_background_hover" => self::add_control_type_color(
				"{$prefix_name}_text_background_hover",
				esc_html__( 'Background Color Hover', 'learnpress' ),
				[ "{{WRAPPER}} $selector:hover" => 'background: {{VALUE}}' ]
			),
		];

		return self::add_group_style_controls( $fields, $prefix_name, $include, $exclude );
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

		$fields = self::add_controls_style_text( $prefix_name, $selector, $include, $exclude );
		$fields = array_merge(
			$fields,
			[
				"{$prefix_name}_btn_border"        => self::add_group_control_type(
					"{$prefix_name}_btn_border",
					Group_Control_Border::get_type(),
					"{{WRAPPER}} $selector"
				),
				"{$prefix_name}_btn_border_radius" => self::add_control_type(
					"{$prefix_name}_btn_border_radius",
					esc_html__( 'Border Radius', 'learnpress' ),
					[],
					Controls_Manager::DIMENSIONS,
					[
						'size_units' => [ 'px', '%', 'custom' ],
						'selectors' => [
							"{{WRAPPER}} $selector" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
						],
					]
				),
			]
		);

		return self::add_group_style_controls( $fields, $prefix_name, $include, $exclude );
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
	public static function add_controls_style_image( string $prefix_name, string $selector,
		array $include = [], array $exclude = [] ): array {

		$fields = [
			"{$prefix_name}_img_show"          => self::add_control_type(
				"{$prefix_name}_img_show",
				esc_html__( 'Image', 'learnpress' ),
				'',
				Controls_Manager::SWITCHER,
				[
					'selectors'    => [ "{{WRAPPER}} $selector" => 'display: {{VALUE}}' ],
					'return_value' => 'none',
					'label_on'     => esc_html__( 'Hide', 'learnpress' ),
					'label_off'    => esc_html__( 'Show', 'learnpress' ),
				]
			),
			"{$prefix_name}_img_margin"        => self::add_responsive_control_type(
				"{$prefix_name}_img_margin",
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
			"{$prefix_name}_img_padding"       => self::add_responsive_control_type(
				"{$prefix_name}_img_padding",
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
			"{$prefix_name}_img_border"        => self::add_group_control_type(
				"{$prefix_name}_img_border",
				Group_Control_Border::get_type(),
				"{{WRAPPER}} $selector"
			),
			"{$prefix_name}_img_border_radius" => self::add_control_type(
				"{$prefix_name}_img_border_radius",
				esc_html__( 'Border Radius', 'learnpress' ),
				[],
				Controls_Manager::DIMENSIONS,
				[
					'size_units' => [ 'px', '%', 'custom' ],
					'selectors' => [
						"{{WRAPPER}} $selector" => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
					],
				]
			),
		];

		return self::add_group_style_controls( $fields, $prefix_name, $include, $exclude );
	}
}

<?php
/**
 * Elementor Controls for widget Become a teacher settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$option_data = [];
if ( isset( $options ) ) {
	$option_data = $options;
}

// Fields tab content
$content_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'layouts',
		esc_html__( 'Info course', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			LPElementorControls::add_control_type(
				'item_layouts',
				esc_html__( 'Add layout list item info of course', 'learnpress' ),
				[
					[
						'layout_name' => 'Time',
						'layout_html' => '<ul><li></li><li></li><li></li></ul>',
					],
				],
				Controls_Manager::REPEATER,
				[
					'fields'        => [
						[
							'name'        => 'icon_item_info',
							'label'       => esc_html__( 'Icon item info', 'learnpress' ),
							'type' => \Elementor\Controls_Manager::ICON,
							'label_block' => true,
						],
						[
							'name'        => 'layout_html',
							'label'       => esc_html__( 'Layout HTML', 'learnpress' ),
							'type'        => Controls_Manager::WYSIWYG,
							'description' => 'Sections: {{instructor_total_courses}}',
							'label_block' => true,
						],
						
						// Toggle Custom Css
						[
							'name'  => 'toggle-custom-css',
							'label' => esc_html__( 'Advanced Css', 'learnpress' ),
							'type'  => Controls_Manager::POPOVER_TOGGLE,
						],
						[
							'method' => 'start_popover',
						],
						[
							'name'        => 'layout_custom_css',
							'label'       => esc_html__( 'Custom CSS', 'learnpress' ),
							'type'        => Controls_Manager::CODE,
							'label_block' => true,
							'language'    => 'css',
							'description' => 'Should start with selector before style. Ex: selector .[className] {color: red;}',
						],
						[ 'method' => 'end_popover' ],
					],
					'prevent_empty' => false,
					'title_field'   => '{{{ layout_name }}}',
				]
			),
		]
	),
);

// Fields tab style
$style_fields = [];

return apply_filters(
	'learn-press/elementor/info-course',
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
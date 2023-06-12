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
				'item_layouts',
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
	LPElementorControls::add_fields_in_section(
		'content',
		esc_html__( 'Content', 'learnpress' ),
		Controls_Manager::TAB_CONTENT,
		[
			/*'instructor_ids' => LPElementorControls::add_control_type(
				'instructor_ids',
				esc_html__( 'Select Instructor ID', 'learnpress' ),
				[],
				Controls_Manager::TEXT,
				[
					'description' => 'ID of Instructor separated by comma. Ex: 1,2,3',
				]
			),
			'item_per_page' => LPElementorControls::add_control_type(
				'item_per_page',
				esc_html__( 'Item per page', 'learnpress' ),
				5,
				Controls_Manager::NUMBER,
				[
					'min' => -1,
					'max' => 100,
				]
			),*/
			'layout'   => LPElementorControls::add_control_type_select(
				'layout',
				esc_html__( 'Layout', 'learnpress' ),
				[
					'grid'  => esc_html__( 'Grid', 'learnpress' ),
					'block' => esc_html__( 'list', 'learnpress' ),
				],
				'grid',
				[
					'selectors'    => [
						'{{WRAPPER}} .list-instructors' => 'display: {{VALUE}}; list-style: none; padding: 0; margin: 0;',
						'{{WRAPPER}} .lp-list-instructors-grid .list-instructors' => 'display: none',
					],
					'prefix_class' => 'lp-list-instructors-',
				]
			),
			'order_by' => LPElementorControls::add_control_type_select(
				'order_by',
				esc_html__( 'Order By', 'learnpress' ),
				[
					'name'      => esc_html__( 'Name a-z', 'learnpress' ),
					'desc_name' => esc_html__( 'Name z-a', 'learnpress' ),
				],
				'name'
			),
			'limit'    => LPElementorControls::add_control_type(
				'limit',
				esc_html__( 'Limit', 'learnpress' ),
				5,
				Controls_Manager::NUMBER,
				[
					'min'         => -1,
					'max'         => 100,
					'description' => 'Number of items to show. Enter -1 to show all instructors.',
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
		esc_html__( 'Title Instructor', 'learnpress' ),
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

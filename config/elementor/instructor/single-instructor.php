<?php
/**
 * Elementor Controls for instructor settings.
 */

use Elementor\Controls_Manager;

return apply_filters(
	'learn-press/elementor/single-instructor',
	array_merge(
		apply_filters(
			'learn-press/elementor/single-instructor/tab-content/fields',
			[
				'section_content' => [
					'type_detect' => 'section',
					'label'       => esc_html__( 'Content', 'learnpress' ),
				],
				'grid_list_type'  => [
					'type_detect' => 'control',
					'label'       => esc_html__( 'Layout Type', 'learnpress' ),
					'type'        => Controls_Manager::SELECT,
					'options'     => array(
						'list' => esc_html__( 'List', 'learnpress' ),
						'grid' => esc_html__( 'Grid', 'learnpress' ),
					),
					'default'     => 'grid',
				],
				'end_section'     => [],
			]
		),
		[]
	)
);

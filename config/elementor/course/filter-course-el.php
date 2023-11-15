<?php
/**
 * Elementor Controls for widget filter course settings.
 *
 * @since 4.2.5
 * @version 1.0.0
 */

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$content_fields = array_merge( 
    LPElementorControls::add_fields_in_section(
        'content_filter',
        esc_html__( 'Filter Course', 'learnpress' ),
        Controls_Manager::TAB_CONTENT,
        [
            LPElementorControls::add_control_type(
				'show_in_rest',
                esc_html__( 'Load widget via REST', 'learnpress' ),
				'yes',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			),
            LPElementorControls::add_control_type(
				'search_suggestion',
                esc_html__( 'Enable Keyword Search Suggestion', 'learnpress' ),
				'yes',
				Controls_Manager::SWITCHER,
				[
					'label_on'     => esc_html__( 'Yes', 'learnpress' ),
					'label_off'    => esc_html__( 'No', 'learnpress' ),
					'return_value' => 'yes',
					'default'      => 'yes',
				]
			),
            LPElementorControls::add_control_type(
                'item_filter',
                esc_html__( 'Fields', 'learnpress' ),
                [
					[
						'item_fields'  => 'category',
                    ],
                    [
                        'item_fields'  => 'btn_submit',
					]
				],
                Controls_Manager::REPEATER,
                [
                    'fields'        => [
                        [
                            'name'        => 'item_fields',
                            'label'       => esc_html__( 'Filter By', 'learnpress' ),
                            'type'        => Controls_Manager::SELECT,
                            'options'     => array(
                                'search'     =>  esc_html__( 'Keyword', 'learnpress' ),
                                'price'      =>  esc_html__( 'Price', 'learnpress' ),
                                'category'   =>  esc_html__( 'Course Category', 'learnpress' ),
                                'tag'        =>  esc_html__( 'Course Tag', 'learnpress' ),
                                'author'     =>  esc_html__( 'Author', 'learnpress' ),
                                'level'      =>  esc_html__( 'Level', 'learnpress' ),
                                'btn_submit' =>  esc_html__( 'Button Submit', 'learnpress' ),
                                'btn_reset'  =>  esc_html__( 'Button Reset', 'learnpress' ),
                            ),
                        ]
                    ],
                    'prevent_empty' => false,
                    'title_field'   => '{{{ item_fields }}}',
                ]
            ),
        ],
    ),
    []
);

// $style_fields   = '';


return apply_filters(
	'learn-press/elementor/course/filter-course-el',
    array_merge(
        apply_filters(
            'learn-press/elementor/course/filter-course-el/tab-content',
            $content_fields
        ),
        // apply_filters(
        //     'learn-press/elementor/course/filter-course-el/tab-styles',
        //     $style_fields
        // )
    )
);
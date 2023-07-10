<?php
/**
 * Elementor Controls for widget Become a teacher settings.
 *
 * @since 4.2.3
 * @version 1.0.0
 */
//Stop this config
use Elementor\Controls_Manager;
use LearnPress\ExternalPlugin\Elementor\LPElementorControls;

$content_fields = array_merge(
    LPElementorControls::add_fields_in_section(
        'layouts',
        esc_html__( 'Course/Lesson Materials', 'learnpress' ),
        Controls_Manager::TAB_CONTENT,
        [
            LPElementorControls::add_control_type(
                'item_layouts',
                esc_html__( 'Add layout and drag to top to set Active', 'learnpress' ),
                [
                    [
                        'layout_name' => 'Layout Default',
                        'layout_html' => '',
                    ],
                ],
                Controls_Manager::REPEATER,
                [
                    'fields'        => [
                        [
                            'name'        => 'column_name',
                            'label'       => esc_html__( 'Column Name', 'learnpress' ),
                            'type'        => Controls_Manager::TEXT,
                            'label_block' => true,
                        ],
                        [
                            'name'         => 'show_column',
                            'label'        => esc_html__( 'Show Column', 'learnpress' ),
                            'type'         => Controls_Manager::SWITCHER,
                            'label_on'     => esc_html__( 'Show', 'learnpress' ),
                            'label_off'    => esc_html__( 'Hide', 'learnpress' ),
                            'return_value' => 'yes',
                            'default'      => 'yes',
                        ],
                        [
                            'name'         => 'has_icon',
                            'type'         => Controls_Manager::HIDDEN,
                            'default'      => false,
                            'return_value' => true,
                        ],
                        [
                            'name'         => 'download_icon',
                            'label'        => esc_html__( 'Download Icon', 'learnpress' ),
                            'type'         => Controls_Manager::ICONS,
                            'default'      => [
                                    'value' => 'fas fa-file-download',
                                    'library' => 'fa-solid',
                                ],
                            'condition'    => [
                                'has_icon'    => 'yes',
                            ]
                        ],
                    ],
                    'default'       =>[
                        'file-name' => [
                            'column_name' => esc_html__( 'Name', 'learnpress' ),
                            'show_column' => esc_html__( 'yes', 'learnpress' ),
                            'has_icon'    => false,
                        ],
                        'file-type' =>[
                            'column_name' => esc_html__( 'Type', 'learnpress' ),
                            'show_column' => esc_html__( 'yes', 'learnpress' ),
                            'has_icon'    => false,
                        ],
                        'file-size' => [
                            'column_name' => esc_html__( 'Size', 'learnpress' ),
                            'show_column' => esc_html__( 'yes', 'learnpress' ),
                            'has_icon'    => false,
                        ],
                        'file-link' => [
                            'column_name' => esc_html__( 'Download', 'learnpress' ),
                            'show_column' => esc_html__( 'yes', 'learnpress' ),
                            'has_icon'    => 'yes',
                            'download_icon' => [
                                'value' => 'fas fa-file-download',
                            ],
                        ],
                    ],
                    'prevent_empty' => false,
                    'title_field'   => '{{{ column_name }}}',
                    'item_actions' => [
                        'add' => false,
                        'duplicate' => false,
                        'remove' => false,
                        'sort' => true,
                    ],
                ]
            ),
        ]
    ),
    []
);
return apply_filters(
    'learn-press/elementor/course/course-material',
    array_merge(
        apply_filters(
            'learn-press/elementor/course/course-material/tab-content',
            $content_fields
        ),
        // apply_filters(
        //     'learn-press/elementor/list-instructors/tab-styles',
        //     $style_fields
        // )
    )
);
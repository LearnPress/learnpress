<?php
/**
 * Setting tab Advanced
 */

return apply_filters(
	'learn_press_advanced_settings',
	array(
		array(
			'type'  => 'title',
			'title' => esc_html__( 'Style', 'learnpress' ),
			'id'    => 'lp_metabox_setting_advanced',
		),
		array(
			'title'    => esc_html__( 'Width container', 'learnpress' ),
			'desc'     => sprintf( '%s', __( 'With of container, Ex: 1140px, 80rem', 'learnpress' ) ),
			'id'       => 'width_container',
			'type'     => 'text',
			'default'  => '1290px',
			'desc_tip' => true,
		),
		array(
			'title'    => esc_html__( 'Primary color', 'learnpress' ),
			'desc'     => sprintf( __( 'Default: %s', 'learnpress' ), '<code>#ffb606</code>' ),
			'id'       => 'primary_color',
			'type'     => 'color',
			'css'      => 'width:6em;',
			'default'  => '#ffb606',
			'autoload' => false,
			'desc_tip' => true,
		),
		array(
			'title'    => esc_html__( 'Secondary color', 'learnpress' ),
			'desc'     => sprintf( __( 'Default: %s', 'learnpress' ), '<code>#442e66</code>' ),
			'id'       => 'secondary_color',
			'type'     => 'color',
			'css'      => 'width:6em;',
			'default'  => '#442e66',
			'autoload' => false,
			'desc_tip' => true,
		),
		array(
			'type' => 'sectionend',
			'id'   => 'lp_metabox_setting_advanced',
		),
		array(
			'type'  => 'title',
			'title' => esc_html__( 'Other', 'learnpress' ),
			'id'    => 'lp_metabox_advanced_other',
		),
		array(
			'title'         => esc_html__( 'Enable gutenberg', 'learnpress' ),
			'id'            => 'enable_gutenberg_course',
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => 'start',
			'desc'          => esc_html__( 'Course', 'learnpress' ),
		),
		array(
			'id'            => 'enable_gutenberg_lesson',
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => '',
			'desc'          => esc_html__( 'Lesson', 'learnpress' ),
		),
		array(
			'id'            => 'enable_gutenberg_quiz',
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => '',
			'desc'          => esc_html__( 'Quiz', 'learnpress' ),
		),
		array(
			'id'            => 'enable_gutenberg_question',
			'default'       => 'no',
			'type'          => 'checkbox',
			'checkboxgroup' => 'end',
			'desc'          => esc_html__( 'Question', 'learnpress' ),
		),
		array(
			'title'   => esc_html__( 'Debug Mode', 'learnpress' ),
			'id'      => 'debug',
			'default' => 'no',
			'type'    => 'checkbox',
			'desc'    => esc_html__( 'Enable debug mode for the developer.', 'learnpress' ),
		),
		array(
			'type' => 'sectionend',
			'id'   => 'lp_metabox_advanced_other',
		),
	)
);

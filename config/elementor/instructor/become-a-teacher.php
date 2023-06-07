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
$content_fields = LPElementorControls::add_fields_in_section(
	'content',
	esc_html__( 'Content', 'learnpress' ),
	Controls_Manager::TAB_CONTENT,
	[
		'title'                      => LPElementorControls::add_control_type(
			'title',
			esc_html__( 'Title', 'learnpress' ),
			esc_html__( 'Become a teacher', 'learnpress' )
		),
		'description'                => LPElementorControls::add_control_type(
			'description',
			esc_html__( 'Description', 'learnpress' ),
			esc_html__( 'Fill in your information and send it to us to become a teacher.', 'learnpress' ),
			Controls_Manager::TEXTAREA
		),
		'submit_button_text'         => LPElementorControls::add_control_type(
			'submit_button_text',
			esc_html__( 'Button text', 'learnpress' ),
			esc_html__( 'Submit', 'learnpress' )
		),
		'submit_button_process_text' => LPElementorControls::add_control_type(
			'submit_button_process_text',
			esc_html__( 'Button Processing text', 'learnpress' ),
			esc_html__( 'Processing', 'learnpress' )
		),
	]
);
// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_control_style_for_el(
		'title',
		__( 'Title', 'learnpress' ),
		'#learn-press-become-teacher-form h3'
	),
	LPElementorControls::add_control_style_for_el(
		'description',
		__( 'Description', 'learnpress' ),
		'.become-teacher-form__description'
	),
	LPElementorControls::add_control_style_for_form(
		'form',
		__( 'Form', 'learnpress' ),
		'.become-teacher-form',
		'.form-field'
	),
	LPElementorControls::add_fields_in_section(
		'btn_submit',
		esc_html__( 'Button Submit', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'form',
			'.become-teacher-form button'
		)
	)
);

return apply_filters(
	'learn-press/elementor/instructor/become-a-teacher/tab-content',
	array_merge(
		apply_filters(
			'learn-press/elementor/instructor/become-a-teacher/tab-content/tab-content/fields',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/instructor/become-a-teacher/tab-styles/fields',
			$style_fields
		)
	)
);

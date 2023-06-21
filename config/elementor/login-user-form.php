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
$content_fields = [];

// Fields tab style
$style_fields = array_merge(
	LPElementorControls::add_fields_in_section(
		'title',
		esc_html__( 'Title', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'title',
			'.learn-press-form-login h3'
		)
	),
	LPElementorControls::add_fields_in_section(
		'form_label',
		esc_html__( 'Form Label', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_text(
			'form_label',
			'.learn-press-form-login label'
		)
	),
	LPElementorControls::add_fields_in_section(
		'form_input_text',
		esc_html__( 'Form Input', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'form_input_text',
			'.learn-press-form-login input, .learn-press-form-login textarea'
		)
	),
	LPElementorControls::add_fields_in_section(
		'btn_submit',
		esc_html__( 'Button Submit', 'learnpress' ),
		Controls_Manager::TAB_STYLE,
		LPElementorControls::add_controls_style_button(
			'form',
			'.learn-press-form-login button'
		)
	)
);

return apply_filters(
	'learn-press/elementor/login-user-form',
	array_merge(
		apply_filters(
			'learn-press/elementor/login-user-form/tab-content',
			$content_fields
		),
		apply_filters(
			'learn-press/elementor/login-user-form/tab-styles',
			$style_fields
		)
	)
);

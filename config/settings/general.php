<?php
$currencies = learn_press_currencies();

foreach ( $currencies as $code => $name ) {
	$currency_symbol     = learn_press_get_currency_symbol( $code );
	$currencies[ $code ] = sprintf( '%s (%s)', $name, $currency_symbol );
}

$data_struct_currency = [
	'setting' => [
		'plugins' => [],
	],
];

return apply_filters(
	'learn-press/general-settings-fields',
	array(
		array(
			'title' => esc_html__( 'Pages setup', 'learnpress' ),
			'type'  => 'title',
		),
		array(
			'title'   => esc_html__( 'All courses page', 'learnpress' ),
			'id'      => 'courses_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'value'   => learn_press_get_page_id( 'courses' ),
		),
		array(
			'title'   => esc_html__( 'All instructors page', 'learnpress' ),
			'id'      => 'instructors_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'value'   => learn_press_get_page_id( 'instructors' ),
		),
		array(
			'title'   => esc_html__( 'Single instructor page', 'learnpress' ),
			'id'      => 'single_instructor_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'value'   => learn_press_get_page_id( 'single_instructor' ),
		),
		array(
			'title'   => esc_html__( 'Profile page', 'learnpress' ),
			'id'      => 'profile_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'value'   => learn_press_get_page_id( 'profile' ),
		),
		array(
			'title'   => esc_html__( 'Checkout page', 'learnpress' ),
			'id'      => 'checkout_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'value'   => learn_press_get_page_id( 'checkout' ),
		),
		array(
			'title'   => esc_html__( 'Become instructors page', 'learnpress' ),
			'id'      => 'become_a_teacher_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'value'   => learn_press_get_page_id( 'become_a_teacher' ),
		),
		array(
			'title'   => esc_html__( 'Terms and conditions', 'learnpress' ),
			'id'      => 'term_conditions_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'value'   => learn_press_get_page_id( 'term_conditions' ),
		),
		array(
			'title'   => esc_html__( 'Logout Redirect', 'learnpress' ),
			'id'      => 'logout_redirect_page_id',
			'default' => '',
			'type'    => 'pages-dropdown',
			'desc'    => __( 'The page where the user will be redirected to after logging out.', 'learnpress' ),
			'value'   => learn_press_get_page_id( 'logout_redirect' ),
		),
		array(
			'type' => 'sectionend',
		),
		array(
			'title' => esc_html__( 'Currency', 'learnpress' ),
			'type'  => 'title',
			'desc'  => esc_html__( 'Setting up your currency unit and its formatting.', 'learnpress' ),
		),
		array(
			'title'             => esc_html__( 'Currency', 'learnpress' ),
			'id'                => 'currency',
			'default'           => 'USD',
			'type'              => 'select',
			'class'             => 'lp-tom-select',
			'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct_currency ) ) ],
			'options'           => $currencies,
		),
		array(
			'title'    => esc_html__( 'Currency position', 'learnpress' ),
			'desc_tip' => esc_html__( 'This controls the position of the currency symbol.', 'learnpress' ),
			'id'       => 'currency_pos',
			'default'  => 'left',
			'type'     => 'select',
			'options'  => learn_press_currency_positions(),
		),
		array(
			'title'    => esc_html__( 'Thousands separator', 'learnpress' ),
			'desc_tip' => esc_html__( 'This sets the thousands separator of displayed prices.', 'learnpress' ),
			'id'       => 'thousands_separator',
			'default'  => ',',
			'type'     => 'text',
			'css'      => 'min-width: 50px; width: 50px;',
		),
		array(
			'title'    => esc_html__( 'Decimal separator', 'learnpress' ),
			'desc_tip' => esc_html__( 'This sets the decimal separator of displayed prices.', 'learnpress' ),
			'id'       => 'decimals_separator',
			'default'  => '.',
			'type'     => 'text',
			'css'      => 'min-width: 50px; width: 50px;',
		),
		array(
			'title'    => esc_html__( 'The number of decimals', 'learnpress' ),
			'desc_tip' => esc_html__( 'This sets the number of decimal points shown in the displayed prices.', 'learnpress' ),
			'id'       => 'number_of_decimals',
			'default'  => '2',
			'type'     => 'number',
			'css'      => 'width: 50px;',
		),
		array(
			'type' => 'sectionend',
		),
		array(
			'title' => esc_html__( 'Other', 'learnpress' ),
			'type'  => 'title',
		),
		array(
			'title'    => esc_html__( 'Publish profile', 'learnpress' ),
			'id'       => 'publish_profile',
			'default'  => 'no',
			'type'     => 'checkbox',
			'desc_tip' => esc_html__( 'This option will add a sub-item \"Privacy\" under the Setting tab on the Profile page. If users want to publish or hide their course, or quiz tab when other users visit their profile page, they need to enable/disable that option in the Privacy section.', 'learnpress' ),
			'desc'     => __( 'Public all user profile pages (only the overview tab).', 'learnpress' ),
		),
		array(
			'title'   => esc_html__( 'Instructor registration', 'learnpress' ),
			'desc'    => esc_html__( 'Enable the option in all registration forms.', 'learnpress' ),
			'id'      => 'instructor_registration',
			'default' => 'no',
			'type'    => 'checkbox',
		),
		array(
			'type' => 'sectionend',
		),
	)
);

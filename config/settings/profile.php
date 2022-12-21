<?php
$currencies = learn_press_currencies();
foreach ( $currencies as $code => $name ) {
	$currency_symbol     = learn_press_get_currency_symbol( $code );
	$currencies[ $code ] = sprintf( '%s (%s)', $name, $currency_symbol );
}

$settings      = LP_Settings::instance();
$user          = wp_get_current_user();
$username      = $user->user_login;
$settings_slug = $settings->get( 'profile_endpoints.settings', 'settings' );
$profile_slug  = 'profile';

if ( learn_press_get_page_id( 'profile' ) ) {
	$profile_post = get_post( learn_press_get_page_id( 'profile' ) );

	if ( $profile_post ) {
		$profile_slug = $profile_post->post_name;
	}
}

$profile_url = site_url() . '/' . $profile_slug . '/' . $username;

return apply_filters(
	'lp/settings/profile',
	apply_filters(
		'learn-press/profile-settings-fields/general',
		array(
			array(
				'title' => esc_html__( 'General', 'learnpress' ),
				'type'  => 'title',
				'id'    => 'lp_profile_general',
			),
			array(
				'title'   => esc_html__( 'Avatar Dimensions', 'learnpress' ),
				'id'      => 'avatar_dimensions',
				'default' => array( 250, 250, 'yes' ),
				'type'    => 'image-dimensions',
			),
			array(
				'title'   => esc_html__( 'Enable login form', 'learnpress' ),
				'id'      => 'enable_login_profile',
				'default' => 'no',
				'type'    => 'checkbox',
				'desc'    => esc_html__( 'If the user is not logged in, enable login from profile.', 'learnpress' ),
			),
			array(
				'title'   => esc_html__( 'Enable register form', 'learnpress' ),
				'id'      => 'enable_register_profile',
				'default' => 'no',
				'type'    => 'checkbox',
				'desc'    => esc_html__( 'If the user is not logged in, enable register from profile.', 'learnpress' ),
			),
			array(
				'title'         => esc_html__( 'Enable default fields', 'learnpress' ),
				'id'            => 'enable_register_first_name',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'start',
				'desc'          => esc_html__( 'First name', 'learnpress' ),
			),
			array(
				'id'            => 'enable_register_last_name',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => '',
				'desc'          => esc_html__( 'Last name', 'learnpress' ),
			),
			array(
				'id'            => 'enable_register_display_name',
				'default'       => 'no',
				'type'          => 'checkbox',
				'checkboxgroup' => 'end',
				'desc'          => esc_html__( 'Display name', 'learnpress' ),
			),
			array(
				'title'   => esc_html__( 'Custom register fields', 'learnpress' ),
				'id'      => 'register_profile_fields',
				'default' => array(),
				'type'    => 'custom-fields',
				'options' => array(
					'name'     => array(
						'title'       => esc_html__( 'Name', 'learnpress' ),
						'type'        => 'text',
						'desc_tip'    => esc_html__( 'Display field name.', 'learnpress' ),
						'placeholder' => esc_html__( 'Display name', 'learnpress' ),
					),
					'type'     => array(
						'title'   => esc_html__( 'Type', 'learnpress' ),
						'type'    => 'select',
						'options' => array(
							'text'     => esc_html__( 'Text', 'learnpress' ),
							'textarea' => esc_html__( 'Textarea', 'learnpress' ),
							'checkbox' => esc_html__( 'Checkbox', 'learnpress' ),
							'url'      => esc_html__( 'URL', 'learnpress' ),
							'number'   => esc_html__( 'Number', 'learnpress' ),
						),
					),
					'required' => array(
						'title' => esc_html__( 'Required?', 'learnpress' ),
						'type'  => 'checkbox',
					),
				),
				'desc'    => esc_html__( 'Custom fields to the registration form.', 'learnpress' ),
			),
			array(
				'type' => 'sectionend',
				'id'   => 'lp_profile_general',
			),
		)
	)
);

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
	'lp/settings/open-ai',
	array_merge(
		apply_filters(
			'learn-press/course-settings-fields/single',
			[
				[
					'title' => esc_html__( 'Open AI', 'learnpress' ),
					'type'  => 'title',
				],
				[
					'title'   => esc_html__( 'Enable Open AI', 'learnpress' ),
					'id'      => 'enable_open_ai',
					'default' => 'no',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Enable/Disable', 'learnpress' ),
				],
				[
					'title'   => __( 'Secret key', 'learnpress' ),
					'id'      => 'open_ai_secret_key',
					'default' => '',
					'type'    => 'textarea',
				],
				[
					'type' => 'sectionend',
				],
			]
		),
	)
);

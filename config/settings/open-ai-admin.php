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
					'desc'    => esc_html__( 'Enable this option and enter your OpenAI Secret key below to activate the feature.', 'learnpress' ),
				],
				[
					'title'   => __( 'Secret key', 'learnpress' ),
					'id'      => 'open_ai_secret_key',
					'default' => '',
					'type'    => 'textarea',
					'desc'    => sprintf(
					/* translators: 1. profile url */
						__(
							'Get your Open AI secret key from <a href="%1$s" target="_blank">here</a>.
                            Read more guide from <a href="%2$s" target="_blank">here</a>.',
							'learnpress'
						),
						'https://platform.openai.com/account/api-keys',
						'https://platform.openai.com/docs/quickstart'
					),
				],
				[
					'title'   => __( 'Text Model Type', 'learnpress' ),
					'id'      => 'open_ai_text_model_type',
					'default' => 'chatgpt-4o-latest',
					'type'    => 'select',
					'options' => array(
						'gpt-5.2'                => esc_html__( 'ChatGPT 5.2', 'learnpress' ),
						'gpt-5'                  => esc_html__( 'ChatGPT 5', 'learnpress' ),
						'gpt-5-mini'             => esc_html__( 'ChatGPT 5 mini', 'learnpress' ),
						'gpt-5-nano'             => esc_html__( 'ChatGPT 5 nano', 'learnpress' ),
						'gpt-4.1'                => esc_html__( 'ChatGPT 4.1', 'learnpress' ),
						'chatgpt-4o-latest'      => esc_html__( 'ChatGPT 4o-Latest', 'learnpress' ),
						'gpt-4o'                 => esc_html__( 'GPT 4o', 'learnpress' ),
						'gpt-4o-mini'            => esc_html__( 'GPT 4o Mini', 'learnpress' ),
						'gpt-4'                  => esc_html__( 'GPT 4', 'learnpress' ),
						'gpt-3.5-turbo'          => esc_html__( 'GPT 3.5 Turbo', 'learnpress' ),
						'gpt-3.5-turbo-instruct' => esc_html__( 'GPT 3.5 Turbo Instruct', 'learnpress' ),
					),
				],
				[
					'title'   => __( 'Image Model Type', 'learnpress' ),
					'id'      => 'open_ai_image_model_type',
					'default' => 'gpt-image-1',
					'type'    => 'select',
					'options' => array(
						'gpt-image-1' => esc_html__( 'GPT image 1', 'learnpress' ),
						'dall-e-3'    => esc_html__( 'DALL E 3', 'learnpress' ),
						'dall-e-2'    => esc_html__( 'DALL E 2 ', 'learnpress' ),
					),
				],
				[
					'title'   => __( 'Frequency Penalty Level', 'learnpress' ),
					'id'      => 'open_ai_frequency_penalty_level',
					'default' => '0.0',
					'type'    => 'select',
					'options' => array(
						'-2.0' => esc_html__( 'Very High Repetition (-2.0)', 'learnpress' ),
						'-1.5' => esc_html__( 'High Repetition (-1.5)', 'learnpress' ),
						'-1.0' => esc_html__( 'Moderate Repetition (-1.0)', 'learnpress' ),
						'-0.5' => esc_html__( 'Low Repetition (-0.5)', 'learnpress' ),
						'0.0'  => esc_html__( 'No Penalty (0.0)', 'learnpress' ),
						'0.5'  => esc_html__( 'Low Penalty (0.5)', 'learnpress' ),
						'1.0'  => esc_html__( 'Moderate Penalty (1.0)', 'learnpress' ),
						'1.5'  => esc_html__( 'High Penalty (1.5)', 'learnpress' ),
						'2.0'  => esc_html__( 'Very High Penalty (2.0)', 'learnpress' ),
					),
				],
				[
					'title'   => __( 'Presence Penalty Level', 'learnpress' ),
					'id'      => 'open_ai_presence_penalty_level',
					'default' => '0.0',
					'type'    => 'select',
					'options' => array(
						'-2.0' => esc_html__( 'Very High Repetition Allowed (-2.0)', 'learnpress' ),
						'-1.5' => esc_html__( 'High Repetition Allowed (-1.5)', 'learnpress' ),
						'-1.0' => esc_html__( 'Moderate Repetition Allowed (-1.0)', 'learnpress' ),
						'-0.5' => esc_html__( 'Low Repetition Allowed (-0.5)', 'learnpress' ),
						'0.0'  => esc_html__( 'No Penalty (0.0)', 'learnpress' ),
						'0.5'  => esc_html__( 'Low Penalty (0.5)', 'learnpress' ),
						'1.0'  => esc_html__( 'Moderate Penalty (1.0)', 'learnpress' ),
						'1.5'  => esc_html__( 'High Penalty (1.5)', 'learnpress' ),
						'2.0'  => esc_html__( 'Very High Penalty (2.0)', 'learnpress' ),
					),
				],
				[
					'title'   => __( 'Creativity Level', 'learnpress' ),
					'id'      => 'open_ai_creativity_level',
					'default' => '1.0',
					'type'    => 'select',
					'options' => array(
						'0.0' => esc_html__( 'Very Low Creativity (0.0)', 'learnpress' ),
						'0.2' => esc_html__( 'Low Creativity (0.2)', 'learnpress' ),
						'0.3' => esc_html__( 'Low Creativity (0.3)', 'learnpress' ),
						'0.5' => esc_html__( 'Moderate Creativity (0.5)', 'learnpress' ),
						'0.7' => esc_html__( 'High Creativity (0.7)', 'learnpress' ),
						'0.8' => esc_html__( 'High Creativity (0.8)', 'learnpress' ),
						'1.0' => esc_html__( 'Very High Creativity (1.0)', 'learnpress' ),
						'1.1' => esc_html__( 'Extreme Creativity (1.1)', 'learnpress' ),
						'1.5' => esc_html__( 'Extreme Creativity (1.5)', 'learnpress' ),
						'2.0' => esc_html__( 'Maximum Creativity (2.0)', 'learnpress' ),
					),
				],
				[
					'title'             => __( 'Max Token', 'learnpress' ),
					'id'                => 'open_ai_max_token',
					'default'           => 4000,
					'type'              => 'number',
					'desc'              => esc_html__( 'Set 0 for no limit.', 'learnpress' ),
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
				],
				[
					'type' => 'sectionend',
				],
			]
		),
	)
);

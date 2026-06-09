<?php
return apply_filters(
	'lp/settings/open-ai',
	array_merge(
		apply_filters(
			'learn-press/course-settings-fields/single',
			[
				[
					'title' => esc_html__( 'OpenAI', 'learnpress' ),
					'type'  => 'title',
				],
				[
					'title'   => esc_html__( 'Enable OpenAI', 'learnpress' ),
					'id'      => 'enable_open_ai',
					'default' => 'no',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Enable this option and enter your OpenAI secret key below to activate this feature.', 'learnpress' ),
				],
				[
					'title'   => __( 'Secret Key', 'learnpress' ),
					'id'      => 'open_ai_secret_key',
					'default' => '',
					'type'    => 'textarea',
					'desc'    => sprintf(
					/* translators: 1. profile url */
						__(
							'Get your OpenAI secret key <a href="%1$s" target="_blank" rel="noopener noreferrer">here</a>. Read the quickstart guide <a href="%2$s" target="_blank" rel="noopener noreferrer">here</a>.',
							'learnpress'
						),
						'https://platform.openai.com/account/api-keys',
						'https://platform.openai.com/docs/quickstart'
					),
				],
				[
					'title'   => __( 'Text Model', 'learnpress' ),
					'id'      => 'open_ai_text_model_type',
					'default' => 'gpt-4.1',
					'type'    => 'select',
					'options' => array(
						'gpt-5.2'                => esc_html__( 'GPT-5.2', 'learnpress' ),
						'gpt-5'                  => esc_html__( 'GPT-5', 'learnpress' ),
						'gpt-5-mini'             => esc_html__( 'GPT-5 Mini', 'learnpress' ),
						'gpt-5-nano'             => esc_html__( 'GPT-5 Nano', 'learnpress' ),
						'gpt-4.1'                => esc_html__( 'GPT-4.1', 'learnpress' ),
						//'chatgpt-4o-latest'      => esc_html__( 'ChatGPT 4o-Latest', 'learnpress' ),
						'gpt-4o'                 => esc_html__( 'GPT-4o', 'learnpress' ),
						'gpt-4o-mini'            => esc_html__( 'GPT-4o Mini', 'learnpress' ),
						'gpt-4'                  => esc_html__( 'GPT-4', 'learnpress' ),
						'gpt-3.5-turbo'          => esc_html__( 'GPT-3.5 Turbo', 'learnpress' ),
						'gpt-3.5-turbo-instruct' => esc_html__( 'GPT-3.5 Turbo Instruct', 'learnpress' ),
					),
				],
				[
					'title'   => __( 'Image Model', 'learnpress' ),
					'id'      => 'open_ai_image_model_type',
					'default' => 'gpt-image-1',
					'type'    => 'select',
					'options' => array(
						'gpt-image-1' => esc_html__( 'GPT Image 1', 'learnpress' ),
						'dall-e-3'    => esc_html__( 'DALL-E 3', 'learnpress' ),
						'dall-e-2'    => esc_html__( 'DALL-E 2', 'learnpress' ),
					),
				],
				[
					'title'   => __( 'Frequency Penalty', 'learnpress' ),
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
					'title'   => __( 'Presence Penalty', 'learnpress' ),
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
					'title'             => __( 'Max Tokens', 'learnpress' ),
					'id'                => 'open_ai_max_token',
					'default'           => 4000,
					'type'              => 'number',
					'desc'              => esc_html__( 'Set to 0 for no limit.', 'learnpress' ),
					'custom_attributes' => array(
						'min'  => 0,
						'step' => 1,
					),
				],
				[
					'type' => 'sectionend',
				],
				[
					'title' => esc_html__( 'AI Assistant', 'learnpress' ),
					'type'  => 'title',
				],
				[
					'title'   => esc_html__( 'Enable AI Assistant', 'learnpress' ),
					'id'      => 'ai_assistant_enabled',
					'default' => 'no',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Enable the AI Assistant for learners on lesson pages. Requires OpenAI to be enabled and a valid secret key.', 'learnpress' ),
				],
				[
					'title'   => esc_html__( 'Enable Free Chat', 'learnpress' ),
					'id'      => 'ai_assistant_free_chat',
					'default' => 'no',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Allow learners to type free-form questions. When disabled, only quick action buttons are shown.', 'learnpress' ),
				],
				[
					'title'   => esc_html__( 'Enable Summarize Lesson', 'learnpress' ),
					'id'      => 'ai_assistant_summarize_enabled',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Allow learners to use the Summarize Lesson action.', 'learnpress' ),
				],
				[
					'title'   => esc_html__( 'Enable Explain Concept', 'learnpress' ),
					'id'      => 'ai_assistant_explain_enabled',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Allow learners to use the Explain Concept action.', 'learnpress' ),
				],
				[
					'title'   => esc_html__( 'Enable Quick Quiz', 'learnpress' ),
					'id'      => 'ai_assistant_quick_quiz_enabled',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Allow learners to start and continue quick quizzes.', 'learnpress' ),
				],
				[
					'title'   => esc_html__( 'Enable Smart Review', 'learnpress' ),
					'id'      => 'ai_assistant_smart_review_enabled',
					'default' => 'yes',
					'type'    => 'checkbox',
					'desc'    => esc_html__( 'Allow learners to use the Smart Review action.', 'learnpress' ),
				],
				[
					'title'             => esc_html__( 'Max Usage Tokens Per Day', 'learnpress' ),
					'id'                => 'ai_assistant_max_usage_tokens_per_day',
					'default'           => 0,
					'type'              => 'number',
					'desc'              => esc_html__( 'Maximum total AI tokens each learner can use per day. Set to 0 for no limit.', 'learnpress' ),
					'custom_attributes' => [
						'min'  => 0,
						'step' => 1,
					],
				],
				[
					'type' => 'sectionend',
				],
			]
		),
	)
);

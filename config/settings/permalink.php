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
	'lp/settings/permalinks',
	array_merge(
		apply_filters(
			'learn-press/course-settings-fields/single',
			[
				[
					'title' => esc_html__( 'Permalinks Course', 'learnpress' ),
					'type'  => 'title',
				],
				[
					'title'   => esc_html__( 'Course', 'learnpress' ),
					'type'    => 'course-permalink',
					'default' => '',
					'id'      => 'course_base',
				],
				[
					'title'       => esc_html__( 'Lesson', 'learnpress' ),
					'type'        => 'text',
					'id'          => 'lesson_slug',
					'desc'        => sprintf( 'e.g. %s/course/sample-course/<code>lessons</code>/sample-lesson/', home_url() ),
					'default'     => 'lessons',
					'placeholder' => 'lesson',
				],
				[
					'title'       => esc_html__( 'Quiz', 'learnpress' ),
					'type'        => 'text',
					'id'          => 'quiz_slug',
					'desc'        => sprintf( 'e.g. %s/course/sample-course/<code>quizzes</code>/sample-quiz/', home_url() ),
					'default'     => 'quizzes',
					'placeholder' => 'quizzes',
				],
				[
					'title'       => esc_html__( 'Category base', 'learnpress' ),
					'id'          => 'course_category_base',
					'default'     => 'course-category',
					'type'        => 'text',
					'placeholder' => 'course-category',
					'desc'        => sprintf( 'e.g. %s/course/%s/sample-category/', home_url(), '<code>course-category</code>' ),
				],
				[
					'title'       => esc_html__( 'Tag base', 'learnpress' ),
					'id'          => 'course_tag_base',
					'default'     => 'course-tag',
					'type'        => 'text',
					'placeholder' => 'course-tag',
					'desc'        => sprintf( 'e.g. %s/course/%s/sample-tag/', home_url(), '<code>course-tag</code>' ),
				],
				[
					'type' => 'sectionend',
				],
			]
		),
		apply_filters(
			'learn-press/profile-settings-fields/sub-tabs',
			array(
				array(
					'title' => esc_html__( 'Permalinks Profile', 'learnpress' ),
					'type'  => 'title',
					'id'    => 'lp_profile_permalinks',
				),
				array(
					'title'       => esc_html__( 'Courses', 'learnpress' ),
					'id'          => 'profile_endpoints[courses]',
					'type'        => 'text',
					'default'     => 'courses',
					'placeholder' => 'courses',
					'desc'        => sprintf(
						'%s. E.g: %s',
						__( 'Courses created by user', 'learnpress' ),
						"{$profile_url}/<code>" . $settings->get( 'profile_endpoints.courses', 'courses' ) . '</code>'
					),
				),
				array(
					'title'       => esc_html__( 'My Courses', 'learnpress' ),
					'id'          => 'profile_endpoints[my-courses]',
					'type'        => 'text',
					'default'     => 'my-courses',
					'placeholder' => 'my-courses',
					'desc'        => sprintf(
						'%s. E.g: %s',
						__( 'Courses enrolled by user', 'learnpress' ),
						"{$profile_url}/<code>" . $settings->get( 'profile_endpoints.my-courses', 'my-courses' ) . '</code>'
					),
				),
				array(
					'title'       => esc_html__( 'Quizzes', 'learnpress' ),
					'id'          => 'profile_endpoints[quizzes]',
					'type'        => 'text',
					'default'     => 'quizzes',
					'placeholder' => 'quizzes',
					'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.quizzes', 'quizzes' ) . '</code>' ),
				),
				array(
					'title'       => esc_html__( 'Orders', 'learnpress' ),
					'id'          => 'profile_endpoints[orders]',
					'type'        => 'text',
					'default'     => 'orders',
					'placeholder' => 'orders',
					'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.orders', 'orders' ) . '</code>' ),
				),
				array(
					'title'       => esc_html__( 'Order details', 'learnpress' ),
					'id'          => 'profile_endpoints[order-details]',
					'type'        => 'text',
					'default'     => 'order-details',
					'placeholder' => 'order-details',
					'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.order-details', 'order-details' ) . '</code>/123' ),
				),
			),
			$this
		),
		apply_filters(
			'learn-press/profile-settings-fields/settings-tab',
			array(
				array(
					'title'       => esc_html__( 'Settings', 'learnpress' ),
					'id'          => 'profile_endpoints[settings]',
					'type'        => 'text',
					'default'     => 'settings',
					'placeholder' => 'settings',
					'desc'        => sprintf( 'e.g.  %s', "{$profile_url}/<code>{$settings_slug}</code>" ),
				),
				array(
					'title'       => __( 'Basic Information <small>Settings</small>', 'learnpress' ),
					'id'          => 'profile_endpoints[settings-basic-information]',
					'type'        => 'text',
					'default'     => 'basic-information',
					'placeholder' => 'basic-information',
					'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-basic-information', 'basic-information' ) . '</code>' ),
				),
				array(
					'title'       => __( 'Avatar <small>Settings</small>', 'learnpress' ),
					'id'          => 'profile_endpoints[settings-avatar]',
					'type'        => 'text',
					'default'     => 'avatar',
					'placeholder' => 'avatar',
					'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-avatar', 'avatar' ) . '</code>' ),
				),
				array(
					'title'       => __( 'Change Password <small>Settings</small>', 'learnpress' ),
					'id'          => 'profile_endpoints[settings-change-password]',
					'type'        => 'text',
					'default'     => 'change-password',
					'placeholder' => 'change-password',
					'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-change-password', 'change-password' ) . '</code>' ),
				),
				array(
					'title'       => __( 'Privacy <small>Settings</small>', 'learnpress' ),
					'id'          => 'profile_endpoints[settings-privacy]',
					'type'        => 'text',
					'default'     => 'privacy',
					'placeholder' => 'privacy',
					'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-privacy', 'privacy' ) . '</code>' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'lp_profile_permalinks',
				),
			)
		)
	)
);

<?php
/**
 * Profile tabs
 *
 * @since 4.2.6.4
 * @version 1.0.0
 */

use LearnPress\TemplateHooks\Profile\ProfileOrdersTemplate;
use LearnPress\TemplateHooks\Profile\ProfileOrderTemplate;

$settings         = LP_Settings::instance();
$default_settings = array(
	'courses'       => array(
		'title'    => esc_html__( 'Courses', 'learnpress' ),
		'slug'     => $settings->get( 'profile_endpoints.courses', 'courses' ),
		'callback' => array( LP_Template_Profile::class, 'tab_courses' ),
		'priority' => 1,
		'icon'     => '<i class="lp-icon-book-open"></i>',
	),
	'my-courses'    => array(
		'title'    => esc_html__( 'My Courses', 'learnpress' ),
		'slug'     => $settings->get( 'profile_endpoints.my-courses', 'my-courses' ),
		'callback' => array( LP_Template_Profile::class, 'tab_my_courses' ),
		'priority' => 1,
		'icon'     => '<i class="lp-icon-my-courses"></i>',
	),
	'quizzes'       => array(
		'title'    => esc_html__( 'Quizzes', 'learnpress' ),
		'slug'     => $settings->get( 'profile_endpoints.quizzes', 'quizzes' ),
		'callback' => false,
		'priority' => 20,
		'icon'     => '<i class="lp-icon-puzzle-piece"></i>',
	),
	'orders'        => array(
		'title'    => esc_html__( 'Orders', 'learnpress' ),
		'slug'     => $settings->get( 'profile_endpoints.orders', 'orders' ),
		'callback' => [ ProfileOrdersTemplate::class, 'tab_content' ],
		'priority' => 25,
		'icon'     => '<i class="lp-icon-shopping-cart"></i>',
	),
	'order-details' => array(
		'title'    => esc_html__( 'Order details', 'learnpress' ),
		'slug'     => $settings->get( 'profile_endpoints.order-details', 'order-details' ),
		'hidden'   => true,
		'callback' => [ ProfileOrderTemplate::class, 'content' ],
		'priority' => 30,
	),
	'settings'      => array(
		'title'    => esc_html__( 'Settings', 'learnpress' ),
		'slug'     => $settings->get( 'profile_endpoints.settings', 'settings' ),
		'callback' => false,
		'sections' => array(
			'basic-information' => array(
				'title'    => esc_html__( 'General', 'learnpress' ),
				'slug'     => $settings->get( 'profile_endpoints.settings-basic-information', 'basic-information' ),
				'callback' => false,
				'priority' => 10,
				'icon'     => '<i class="lp-icon-home"></i>',
			),
			'avatar'            => array(
				'title'    => esc_html__( 'Avatar', 'learnpress' ),
				'callback' => false,
				'slug'     => $settings->get( 'profile_endpoints.settings-avatar', 'avatar' ),
				'priority' => 20,
				'icon'     => '<i class="lp-icon-user-circle"></i>',
			),
			'change-password'   => array(
				'title'    => esc_html__( 'Password', 'learnpress' ),
				'slug'     => $settings->get( 'profile_endpoints.settings-change-password', 'change-password' ),
				'callback' => false,
				'priority' => 30,
				'icon'     => '<i class="lp-icon-key"></i>',
			),
		),
		'priority' => 90,
		'icon'     => '<i class="lp-icon-cog"></i>',
	),
	'logout'        => array(
		'title'    => esc_html__( 'Logout', 'learnpress' ),
		'slug'     => learn_press_profile_logout_slug(),
		'icon'     => '<i class="lp-icon-sign-out"></i>',
		'priority' => 100,
	),
);

if ( 'yes' === LP_Profile::get_option_publish_profile() ) {
	$default_settings['settings']['sections']['privacy'] = array(
		'title'    => esc_html__( 'Privacy', 'learnpress' ),
		'slug'     => $settings->get( 'profile_endpoints.settings-privacy', 'privacy' ),
		'priority' => 40,
		'callback' => false,
		'icon'     => '<i class="lp-icon-user-secret"></i>',
	);
}

return apply_filters( 'learn-press/profile-tabs', $default_settings );

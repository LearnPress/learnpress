<?php

/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Profile extends LP_Abstract_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'profile';
		$this->text = __( 'Profile', 'learnpress' );

		parent::__construct();
	}

	/**
	 * @param string $section
	 * @param string $tab
	 *
	 * @return array
	 */
	public function get_settings( $section = null, $tab = null ) {
		$settings      = LP()->settings();
		$user          = wp_get_current_user();
		$username      = $user->user_login;
		$settings_slug = $settings->get( 'profile_endpoints.settings', 'settings' );
		$profile_slug  = 'profile';

		if ( $profile_id = learn_press_get_page_id( 'profile' ) ) {
			if ( $profile_post = get_post( $profile_id ) ) {
				$profile_slug = $profile_post->post_name;
			}
		}
		$profile_url = site_url() . '/' . $profile_slug . '/' . $username;

		$settings = apply_filters(
			'learn-press/profile-settings-fields',
			array_merge(
				apply_filters(
					'learn-press/profile-settings-fields/general',
					array(
						array(
							'title' => __( 'General', 'learnpress' ),
							'type'  => 'heading',
						),
						array(
							'title'   => __( 'Avatar Dimensions', 'learnpress' ),
							'id'      => 'avatar_dimensions',
							'default' => array( 250, 250, 'yes' ),
							'type'    => 'image-dimensions'
						)
//						array(
//							'title'   => __( 'Profile page', 'learnpress' ),
//							'id'      => 'profile_page_id',
//							'default' => '',
//							'type'    => 'pages-dropdown'
//						),
//						array(
//							'title'   => __( 'Add link to admin bar', 'learnpress' ),
//							'id'      => 'admin_bar_link',
//							'default' => 'yes',
//							'type'    => 'yes-no'
//						),
//						array(
//							'title'       => __( 'Text link', 'learnpress' ),
//							'id'          => 'admin_bar_link_text',
//							'default'     => '',
//							'type'        => 'text',
//							'placeholder' => get_post_field( 'post_title', learn_press_get_page_id( 'profile' ) ),
//							'desc'        => __( 'If empty, please enter the name of the page used for profile.', 'learnpress' ),
//							'visibility'  => array(
//								'state'       => 'show',
//								'conditional' => array(
//									array(
//										'field'   => 'admin_bar_link',
//										'compare' => '=',
//										'value'   => 'yes'
//									)
//								)
//							)
//						),
//						array(
//							'title'      => __( 'Target link', 'learnpress' ),
//							'id'         => 'admin_bar_link_target',
//							'default'    => 'yes',
//							'type'       => 'select',
//							'options'    => array(
//								'_self'  => __( 'Open in same the window', 'learnpress' ),
//								'_blank' => __( 'Open in a new window', 'learnpress' )
//							),
//							'visibility' => array(
//								'state'       => 'show',
//								'conditional' => array(
//									array(
//										'field'   => 'admin_bar_link',
//										'compare' => '=',
//										'value'   => 'yes'
//									)
//								)
//							)
//						),
//						array(
//							'title'   => __( 'Courses per page', 'learnpress' ),
//							'id'      => 'profile_courses_limit',
//							'default' => '10',
//							'type'    => 'number',
//							'min'     => 1,
//							'desc'    => __( 'Number of courses displayed per page in profile.', 'learnpress' )
//						),
//						array(
//							'title'   => __( 'Enable login form', 'learnpress' ),
//							'id'      => 'enable_login_profile',
//							'default' => 'no',
//							'type'    => 'yes-no',
//							'desc'    => __( 'Enable login from profile if the user is not logged in.', 'learnpress' )
//						),
//						array(
//							'title'   => __( 'Enable register form', 'learnpress' ),
//							'id'      => 'enable_register_profile',
//							'default' => 'no',
//							'type'    => 'yes-no',
//							'desc'    => __( 'Enable register from profile if the user is not logged in.', 'learnpress' )
//						)
					)
				),

				apply_filters(
					'learn-press/profile-settings-fields/sub-tabs',
					array(
						array(
							'title' => __( 'Permalinks', 'learnpress' ),
							'type'  => 'heading',
						),
						array(
							'title'       => __( 'Overview', 'learnpress' ),
							'id'          => 'profile_endpoints[overview]',
							'type'        => 'text',
							'default'     => 'overview',
							'placeholder' => 'overview',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.dashboard', 'overview' ) . "</code>" )
						),
						array(
							'title'       => __( 'Courses', 'learnpress' ),
							'id'          => 'profile_endpoints[courses]',
							'type'        => 'text',
							'default'     => 'courses',
							'placeholder' => 'courses',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.courses', 'courses' ) . "</code>" )
						),
						array(
							'title'       => __( 'Quizzes', 'learnpress' ),
							'id'          => 'profile_endpoints[quizzes]',
							'type'        => 'text',
							'default'     => 'quizzes',
							'placeholder' => 'quizzes',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.quizzes', 'quizzes' ) . "</code>" )
						),
						array(
							'title'       => __( 'Orders', 'learnpress' ),
							'id'          => 'profile_endpoints[orders]',
							'type'        => 'text',
							'default'     => 'orders',
							'placeholder' => 'orders',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.orders', 'orders' ) . "</code>" )
						),
						array(
							'title'       => __( 'Order details', 'learnpress' ),
							'id'          => 'profile_endpoints[order-details]',
							'type'        => 'text',
							'default'     => 'order-details',
							'placeholder' => 'order-details',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.order-details', 'order-details' ) . "</code>/123" )
						)
					),
					$this
				),
				apply_filters(
					'learn-press/profile-settings-fields/settings-tab',
					array(
						array(
							'title'       => __( 'Settings', 'learnpress' ),
							'id'          => 'profile_endpoints[settings]',
							'type'        => 'text',
							'default'     => 'settings',
							'placeholder' => 'settings',
							'desc'        => sprintf( 'e.g.  %s', "{$profile_url}/<code>{$settings_slug}</code>" )
						),
						array(
							'title'       => __( 'Basic Information <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-basic-information]',
							'type'        => 'text',
							'default'     => 'basic-information',
							'placeholder' => 'basic-information',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-basic-information', 'basic-information' ) . "</code>" )
						),
						array(
							'title'       => __( 'Avatar <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-avatar]',
							'type'        => 'text',
							'default'     => 'avatar',
							'placeholder' => 'avatar',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-avatar', 'avatar' ) . "</code>" )
						),
						array(
							'title'       => __( 'Change Password <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-change-password]',
							'type'        => 'text',
							'default'     => 'change-password',
							'placeholder' => 'change-password',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-change-password', 'change-password' ) . "</code>" )
						),
						array(
							'title'       => __( 'Privacy <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-privacy]',
							'type'        => 'text',
							'default'     => 'privacy',
							'placeholder' => 'privacy',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-privacy', 'privacy' ) . "</code>" )
						)
					)
				)
			)
		);

		/**
		 * @deprecated
		 */
		$settings = apply_filters( 'learn_press_profile_settings', $settings );

		$settings = apply_filters( 'learn-press/settings/profile', $settings );

		return $settings;
	}
}

return new LP_Settings_Profile();
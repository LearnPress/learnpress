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
		$settings_slug = $settings->get( 'profile_endpoints.profile-settings' );
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
							'desc'  => __( 'General settings.', 'learnpress' )
						)
					)
				),

				apply_filters(
					'learn-press/profile-settings-fields/sub-tabs',
					array(
						array(
							'title' => __( 'Permalinks', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'The slugs of tabs display in profile page. Each tab should be unique.', 'learnpress' )
						),
						array(
							'title'       => __( 'Dashboard', 'learnpress' ),
							'id'          => 'profile_endpoints[profile-dashboard]',
							'type'        => 'text',
							'default'     => 'dashboard',
							'placeholder' => 'dashboard',
							'desc'        => sprintf( '%s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.dashboard', 'dashboard' ) . "</code>" )
						),
						array(
							'title'       => __( 'Courses', 'learnpress' ),
							'id'          => 'profile_endpoints[profile-courses]',
							'type'        => 'text',
							'default'     => 'courses',
							'placeholder' => 'courses',
							'desc'        => sprintf( '%s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.courses', 'courses' ) . "</code>" )
						),
						array(
							'title'       => __( 'Quizzes', 'learnpress' ),
							'id'          => 'profile_endpoints[profile-quizzes]',
							'type'        => 'text',
							'default'     => 'quizzes',
							'placeholder' => 'quizzes',
							'desc'        => sprintf( '%s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.quizzes', 'quizzes' ) . "</code>" )
						),
						array(
							'title'       => __( 'Orders', 'learnpress' ),
							'id'          => 'profile_endpoints[profile-orders]',
							'type'        => 'text',
							'default'     => 'orders',
							'placeholder' => 'orders',
							'desc'        => sprintf( '%s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.orders', 'orders' ) . "</code>" )
						),
						array(
							'title'       => __( 'Order details', 'learnpress' ),
							'id'          => 'profile_endpoints[profile-order-details]',
							'type'        => 'text',
							'default'     => 'order-details',
							'placeholder' => 'order-details',
							'desc'        => sprintf( '%s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.order-details', 'order-details' ) . "</code>/123" )
						)
					),
					$this
				),
				apply_filters(
					'learn-press/profile-settings-fields/settings-tab',
					array(
						array(
							'title'       => __( 'General <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[profile-settings]',
							'type'        => 'text',
							'default'     => 'settings',
							'placeholder' => 'settings',
							'desc'        => sprintf( '%s', "{$profile_url}/<code>{$settings_slug}</code>" )
						),
						array(
							'title'       => __( 'Basic Information <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-basic-information]',
							'type'        => 'text',
							'default'     => 'basic-information',
							'placeholder' => 'basic-information',
							'desc'        => sprintf( '%s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-basic-information', 'basic-information' ) . "</code>" )
						),
						array(
							'title'       => __( 'Avatar <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-avatar]',
							'type'        => 'text',
							'default'     => 'avatar',
							'placeholder' => 'avatar',
							'desc'        => sprintf( '%s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-avatar', 'avatar' ) . "</code>" )
						),
						array(
							'title'       => __( 'Change Password <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-change-password]',
							'type'        => 'text',
							'default'     => 'change-password',
							'placeholder' => 'change-password',
							'desc'        => sprintf( '%s', "{$profile_url}/{$settings_slug}/<code>" . $settings->get( 'profile_endpoints.settings-change-password', 'change-password' ) . "</code>" )
						),
						array(
							'title'       => __( 'Privacy <small>Settings</small>', 'learnpress' ),
							'id'          => 'profile_endpoints[settings-privacy]',
							'type'        => 'text',
							'default'     => 'privacy',
							'placeholder' => 'privacy',
							'desc'        => sprintf( '%s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.privacy', 'privacy' ) . "</code>" )
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
<?php

/**
 * Class LP_Settings_Profile
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'LP_Settings_Profile', false ) ) {
	return new LP_Settings_Profile();
}

class LP_Settings_Profile extends LP_Abstract_Settings_Page {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id   = 'profile';
		$this->text = esc_html__( 'Profile', 'learnpress' );

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

		if ( learn_press_get_page_id( 'profile' ) ) {
			$profile_post = get_post( learn_press_get_page_id( 'profile' ) );

			if ( $profile_post ) {
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
							'desc'    => esc_html__( 'Enable login from profile if the user is not logged in.', 'learnpress' ),
						),
						array(
							'title'   => esc_html__( 'Enable register form', 'learnpress' ),
							'id'      => 'enable_register_profile',
							'default' => 'no',
							'type'    => 'checkbox',
							'desc'    => esc_html__( 'Enable register from profile if the user is not logged in.', 'learnpress' ),
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
							'desc'    => esc_html__( 'Custom fields for form register.', 'learnpress' ),
						),
						array(
							'type' => 'sectionend',
							'id'   => 'lp_profile_general',
						),
					)
				),
				apply_filters(
					'learn-press/profile-settings-fields/sub-tabs',
					array(
						array(
							'title' => esc_html__( 'Permalinks', 'learnpress' ),
							'type'  => 'title',
							'id'    => 'lp_profile_permalinks',
						),
						array(
							'title'       => esc_html__( 'Courses', 'learnpress' ),
							'id'          => 'profile_endpoints[courses]',
							'type'        => 'text',
							'default'     => 'courses',
							'placeholder' => 'courses',
							'desc'        => sprintf( 'e.g. %s', "{$profile_url}/<code>" . $settings->get( 'profile_endpoints.courses', 'courses' ) . '</code>' ),
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

		$settings = apply_filters( 'learn-press/settings/profile', $settings );

		return $settings;
	}
}

return new LP_Settings_Profile();

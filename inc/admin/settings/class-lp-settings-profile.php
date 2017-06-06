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
	 * @return array
	 */
	public function get_settings( $section = null, $tab = null ) {
		$settings = apply_filters(
			'learn-press/profile-settings-fields',
			array_merge(
				apply_filters( 'learn-press/profile-settings-fields/general', array(
						array(
							'title' => __( 'General', 'learnpress' ),
							'type'  => 'heading',
							'desc'=>__('General settings', 'learnpress')
						),
						array(
							'title'   => __( 'Profile page', 'learnpress' ),
							'id'      => $this->get_field_name( 'profile_page_id' ),
							'default' => '',
							'type'    => 'pages-dropdown'
						),
						array(
							'title'   => __( 'Add link to admin bar', 'learnpress' ),
							'id'      => $this->get_field_name( 'admin_bar_link' ),
							'default' => 'yes',
							'type'    => 'yes-no'
						),
						array(
							'title'       => __( 'Text link', 'learnpress' ),
							'id'          => $this->get_field_name( 'admin_bar_link_text' ),
							'default'     => '',
							'type'        => 'text',
							'placeholder' => __( 'Default: View Course Profile', 'learnpress' ),
							'visibility' => array(
								'state'       => 'show',
								'state_callback' => 'conditional_logic_gray_state',
								'conditional' => array(
									array(
										'field'   => $this->get_field_name( 'admin_bar_link' ),
										'compare' => '=',
										'value'   => 'yes'
									)
								)
							)
						),
						array(
							'title'   => __( 'Target link', 'learnpress' ),
							'id'      => $this->get_field_name( 'admin_bar_link_target' ),
							'default' => 'yes',
							'type'    => 'select',
							'options' => array(
								'_self'  => __( 'Self', 'learnpress' ),
								'_blank' => __( 'New window', 'learnpress' )
							),
							'visibility' => array(
								'state'       => 'show',
								'state_callback' => 'conditional_logic_gray_state',
								'conditional' => array(
									array(
										'field'   => $this->get_field_name( 'admin_bar_link' ),
										'compare' => '=',
										'value'   => 'yes'
									)
								)
							)
						),
						array(
							'title'   => __( 'Courses limit', 'learnpress' ),
							'id'      => $this->get_field_name( 'profile_courses_limit' ),
							'default' => '10',
							'type'    => 'number',
							'min'     => 1
						),
						array(
							'title'   => __( 'Redirect to page', 'learnpress' ),
							'id'      => $this->get_field_name( 'logout_redirect_page_id' ),
							'default' => '',
							'type'    => 'pages-dropdown'
						)
					)
				),
				apply_filters( 'learn-press/profile-settings-fields/sub-tabs', array(
						array(
							'title' => __( 'Sub Tabs', 'learnpress' ),
							'type'  => 'heading',
							'desc'=>__('Profile sub-tabs', 'learnpress')
						),
						array(
							'title'   => __( 'Profile page', 'learnpress' ),
							'id'      => $this->get_field_name( 'profile_page_id' ),
							'default' => '',
							'type'    => 'pages-dropdown',
						)
					)
				),
				apply_filters( 'learn-press/profile-settings-fields/avatar', array(
						array(
							'title' => __( 'Avatar', 'learnpress' ),
							'type'  => 'heading',
							'desc'=>__('User avatar settings', 'learnpress')
						),
						array(
							'title'   => __( 'Profile page', 'learnpress' ),
							'id'      => $this->get_field_name( 'profile_page_id' ),
							'default' => '',
							'type'    => 'pages-dropdown'
						)
					)
				)
			)
		);
		$settings = apply_filters( 'learn_press_profile_settings', $settings );

		return $settings;
	}
}

return new LP_Settings_Profile();
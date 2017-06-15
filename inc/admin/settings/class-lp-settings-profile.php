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
							'desc'  => __( 'General settings', 'learnpress' )
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
							'visibility'  => array(
								'state'       => 'show',
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
							'title'      => __( 'Target link', 'learnpress' ),
							'id'         => $this->get_field_name( 'admin_bar_link_target' ),
							'default'    => 'yes',
							'type'       => 'select',
							'options'    => array(
								'_self'  => __( 'Self', 'learnpress' ),
								'_blank' => __( 'New window', 'learnpress' )
							),
							'visibility' => array(
								'state'       => 'show',
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
							'title'   => __( 'Logout redirect', 'learnpress' ),
							'id'      => $this->get_field_name( 'logout_redirect_page_id' ),
							'default' => '',
							'type'    => 'pages-dropdown',
							'desc'    => __( 'The page where user will be redirected to after logging out.', 'learnpress' )
						)
					)
				),
				apply_filters( 'learn-press/profile-settings-fields/sub-tabs', array(
						array(
							'title' => __( 'Sub tab slugs', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'The slugs of tabs display in profile page. Each tab should be unique.', 'learnpress' )
						),
						array(
							'title'   => __( 'Courses', 'learnpress' ),
							'id'      => 'learn_press_profile_endpoints[profile-courses]',
							'default' => '',
							'type'    => 'text',
							'std'     => 'courses',
							'desc'    => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/courses]</code>' )
						),
						array(
							'title'   => __( 'Quizzes', 'learnpress' ),
							'id'      => 'learn_press_profile_endpoints[profile-quizzes]',
							'default' => '',
							'type'    => 'text',
							'std'     => 'quizzes',
							'desc'    => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/quizzes]</code>' )
						),
						array(
							'title'   => __( 'Orders', 'learnpress' ),
							'id'      => 'learn_press_profile_endpoints[profile-orders]',
							'default' => '',
							'type'    => 'text',
							'std'     => 'orders',
							'desc'    => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/orders]</code>' )
						),
						array(
							'title'   => __( 'Order details', 'learnpress' ),
							'id'      => 'learn_press_profile_endpoints[profile-order-details]',
							'default' => '',
							'type'    => 'text',
							'std'     => 'order-details',
							'desc'    => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/order-details/123]</code>' )
						)
					)
				),
				apply_filters( 'learn-press/profile-settings-fields/avatar', array(
						array(
							'title' => __( 'Avatar', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'User avatar settings', 'learnpress' )
						),
						array(
							'title'   => __( 'Enable custom avatar', 'learnpress' ),
							'id'      => $this->get_field_name( 'profile_avatar' ),
							'default' => 'yes',
							'type'    => 'yes-no'
						),
						array(
							'title'      => __( 'Size', 'learnpress' ),
							'id'         => $this->get_field_name( 'profile_picture_thumbnail_size' ),
							'default'    => '',
							'type'       => 'image-dimensions',
							'visibility' => array(
								'state'       => 'show',
								'conditional' => array(
									array(
										'field'   => $this->get_field_name( 'profile_avatar' ),
										'compare' => '=',
										'value'   => 'yes'
									)
								)
							)
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
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
				apply_filters(
					'learn-press/profile-settings-fields/general',
					array(
						array(
							'title' => __( 'General', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'General settings.', 'learnpress' )
						),
						array(
							'title'   => __( 'Profile page', 'learnpress' ),
							'id'      => 'profile_page_id',
							'default' => '',
							'type'    => 'pages-dropdown'
						),
						array(
							'title'   => __( 'Add link to admin bar', 'learnpress' ),
							'id'      => 'admin_bar_link',
							'default' => 'yes',
							'type'    => 'yes-no'
						),
						array(
							'title'       => __( 'Text link', 'learnpress' ),
							'id'          => 'admin_bar_link_text',
							'default'     => '',
							'type'        => 'text',
							'placeholder' => get_post_field( 'post_title', learn_press_get_page_id( 'profile' ) ),
							'desc'        => __( 'If empty, text is name of page used for profile.', 'learnpress' ),
							'visibility'  => array(
								'state'       => 'show',
								'conditional' => array(
									array(
										'field'   => 'admin_bar_link',
										'compare' => '=',
										'value'   => 'yes'
									)
								)
							)
						),
						array(
							'title'      => __( 'Target link', 'learnpress' ),
							'id'         => 'admin_bar_link_target',
							'default'    => 'yes',
							'type'       => 'select',
							'options'    => array(
								'_self'  => __( 'Open in same window', 'learnpress' ),
								'_blank' => __( 'Open in new window', 'learnpress' )
							),
							'visibility' => array(
								'state'       => 'show',
								'conditional' => array(
									array(
										'field'   => 'admin_bar_link',
										'compare' => '=',
										'value'   => 'yes'
									)
								)
							)
						),
						array(
							'title'   => __( 'Courses limit', 'learnpress' ),
							'id'      => 'profile_courses_limit',
							'default' => '10',
							'type'    => 'number',
							'min'     => 1,
							'desc'    => __( 'Number of courses displayed per page in profile.', 'learnpress' )
						),
						array(
							'title'   => __( 'Enable login form', 'learnpress' ),
							'id'      => 'enable_login_profile',
							'default' => 'no',
							'type'    => 'yes-no',
							'desc'    => __( 'Enable login from profile if the user is not logged in.', 'learnpress' )
						),
						array(
							'title'   => __( 'Enable register form', 'learnpress' ),
							'id'      => 'enable_register_profile',
							'default' => 'no',
							'type'    => 'yes-no',
							'desc'    => __( 'Enable register from profile if the user is not logged in.', 'learnpress' )
						)
					)
				),
				apply_filters(
					'learn-press/profile-settings-fields/sub-tabs',
					array(
						array(
							'title' => __( 'Sub tab slugs', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'The slugs of tabs display in profile page. Each tab should be unique.', 'learnpress' )
						),
						array(
							'title' => __( 'Dashboard', 'learnpress' ),
							'id'    => 'profile_endpoints[profile-dashboard]',
							'type'  => 'text',
							'std'   => '',
							'desc'  => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/]</code>' )
						),
						array(
							'title' => __( 'Courses', 'learnpress' ),
							'id'    => 'profile_endpoints[profile-courses]',
							'type'  => 'text',
							'std'   => 'courses',
							'desc'  => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/courses]</code>' )
						),
						array(
							'title' => __( 'Quizzes', 'learnpress' ),
							'id'    => 'profile_endpoints[profile-quizzes]',
							'type'  => 'text',
							'std'   => 'quizzes',
							'desc'  => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/quizzes]</code>' )
						),
						array(
							'title' => __( 'Orders', 'learnpress' ),
							'id'    => 'profile_endpoints[profile-orders]',
							'type'  => 'text',
							'std'   => 'orders',
							'desc'  => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/orders]</code>' )
						),
						array(
							'title' => __( 'Order details', 'learnpress' ),
							'id'    => 'profile_endpoints[profile-order-details]',
							'type'  => 'text',
							'std'   => 'order-details',
							'desc'  => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/order-details/123]</code>' )
						)
					),
					$this
				),
				apply_filters(
					'learn-press/profile-settings-fields/settings-tab',
					array(
						array(
							'title' => __( 'Settings Tab', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'The slugs of sections in settings tab. Each slugs should be unique.', 'learnpress' )
						),
						array(
							'title' => __( 'Slug', 'learnpress' ),
							'id'    => 'profile_endpoints[profile-settings]',
							'type'  => 'text',
							'std'   => 'settings',
							'desc'  => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/order-details/123]</code>' )
						),
						array(
							'title'   => __( 'Basic Information', 'learnpress' ),
							'id'      => 'profile_endpoints[settings-basic-information]',
							'type'    => 'text',
							'default' => 'basic-information',
							'desc'    => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/settings/basic-information]</code>' )
						),
						array(
							'title'   => __( 'Avatar', 'learnpress' ),
							'id'      => 'profile_endpoints[settings-avatar]',
							'type'    => 'text',
							'default' => 'avatar',
							'desc'    => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/settings/basic-information]</code>' )
						),
						array(
							'title'   => __( 'Change Password', 'learnpress' ),
							'id'      => 'profile_endpoints[settings-change-password]',
							'type'    => 'text',
							'default' => 'change-password',
							'desc'    => sprintf( __( 'Example link is %s', 'learnpress' ), '<code>[profile/admin/settings/basic-information]</code>' )
						)
					)
				),
				apply_filters(
					'learn-press/profile-settings-fields/avatar',
					array(
						array(
							'title' => __( 'Avatar', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'User avatar settings.', 'learnpress' )
						),
						array(
							'title'   => __( 'Enable custom avatar', 'learnpress' ),
							'id'      => 'profile_avatar',
							'default' => 'yes',
							'type'    => 'yes-no'
						),
						array(
							'title'      => __( 'Size', 'learnpress' ),
							'id'         => 'profile_picture_thumbnail_size',
							'default'    => array( 'width' => 200, 'height' => 200 ),
							'type'       => 'image-dimensions',
							'visibility' => array(
								'state'       => 'show',
								'conditional' => array(
									array(
										'field'   => 'profile_avatar',
										'compare' => '=',
										'value'   => 'yes'
									)
								)
							),
							'desc'       => __( 'The height and width of avatar should be equal.', 'learnpress' )
						)
					)
				),
				apply_filters(
					'learn-press/profile-settings-fields/publicity',
					array(
						array(
							'title' => __( 'Publicity', 'learnpress' ),
							'type'  => 'heading',
							'desc'  => __( 'Publicity and sharing your profile content.', 'learnpress' )
						),
						array(
							'title'   => __( 'My dashboard', 'learnpress' ),
							'id'      => 'profile_publicity[dashboard]',
							'default' => 'yes',
							'type'    => 'yes-no'
						),
						array(
							'title'      => __( 'Courses', 'learnpress' ),
							'id'         => 'profile_publicity[courses]',
							'default'    => 'no',
							'type'       => 'yes-no',
							'visibility' => array(
								'state'       => 'show',
								'conditional' => array(
									array(
										'field'   => 'profile_publicity[dashboard]',
										'compare' => '=',
										'value'   => 'yes'
									)
								)
							)
						),
						array(
							'title'      => __( 'Quizzes', 'learnpress' ),
							'id'         => 'profile_publicity[quizzes]',
							'default'    => 'no',
							'type'       => 'yes-no',
							'visibility' => array(
								'state'       => 'show',
								'conditional' => array(
									array(
										'field'   => 'profile_publicity[dashboard]',
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

		/**
		 * @deprecated
		 */
		$settings = apply_filters( 'learn_press_profile_settings', $settings );

		$settings = apply_filters( 'learn-press/settings/profile', $settings );

		return $settings;
	}
}

return new LP_Settings_Profile();
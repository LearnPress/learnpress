<?php

/**
 * Class LP_Settings_General
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings/Classes
 * @version 1.0
 */
class LP_Settings_General extends LP_Abstract_Settings_Page {
	/**
	 * Construct
	 */
	public function __construct() {
		$this->id   = 'general';
		$this->text = esc_html__( 'General', 'learnpress' );

		parent::__construct();
	}

	/**
	 * Return fields for settings page.
	 *
	 * @param string $section
	 * @param string $tab
	 *
	 * @return mixed
	 */
	public function get_settings( $section = '', $tab = '' ) {
		$currencies = learn_press_currencies();

		foreach ( $currencies as $code => $name ) {
			$s                   = learn_press_get_currency_symbol( $code );
			$currencies[ $code ] = sprintf( '%s (%s)', $name, $s );
		}

		$settings = apply_filters(
			'learn-press/general-settings-fields',
			array(
				array(
					'title' => esc_html__( 'Pages setup', 'learnpress' ),
					'type'  => 'title',
				),
				array(
					'title'   => esc_html__( 'All courses page', 'learnpress' ),
					'id'      => 'courses_page_id',
					'default' => '',
					'type'    => 'pages-dropdown',
				),
				array(
					'title'   => esc_html__( 'Profile page', 'learnpress' ),
					'id'      => 'profile_page_id',
					'default' => '',
					'type'    => 'pages-dropdown',
				),
				array(
					'title'   => esc_html__( 'Checkout page', 'learnpress' ),
					'id'      => 'checkout_page_id',
					'default' => '',
					'type'    => 'pages-dropdown',
				),
				array(
					'title'   => esc_html__( 'Become instructors page', 'learnpress' ),
					'id'      => 'become_a_teacher_page_id',
					'default' => '',
					'type'    => 'pages-dropdown',
				),
				array(
					'title'   => esc_html__( 'Terms and conditions', 'learnpress' ),
					'id'      => 'term_conditions_page_id',
					'default' => '',
					'type'    => 'pages-dropdown',
				),
				array(
					'title'   => esc_html__( 'Logout Redirect', 'learnpress' ),
					'id'      => 'logout_redirect_page_id',
					'default' => '',
					'type'    => 'pages-dropdown',
					'desc'    => __( 'The page where user will be redirected to after logging out.', 'learnpress' ),
				),
				array(
					'type' => 'sectionend',
				),
				array(
					'title' => esc_html__( 'Currency', 'learnpress' ),
					'type'  => 'title',
					'desc'  => esc_html__( 'Setting up your currency unit and its formatting.', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Currency', 'learnpress' ),
					'id'      => 'currency',
					'default' => 'USD',
					'type'    => 'select',
					'class'   => 'lp-select-2',
					'options' => $currencies,
				),
				array(
					'title'    => esc_html__( 'Currency position', 'learnpress' ),
					'desc_tip' => esc_html__( 'This controls the position of the currency symbol.', 'learnpress' ),
					'id'       => 'currency_pos',
					'default'  => 'left',
					'type'     => 'select',
					'options'  => learn_press_currency_positions(),
				),
				array(
					'title'    => esc_html__( 'Thousands separator', 'learnpress' ),
					'desc_tip' => esc_html__( 'This sets the thousand separator of displayed prices.', 'learnpress' ),
					'id'       => 'thousands_separator',
					'default'  => ',',
					'type'     => 'text',
					'css'      => 'min-width: 50px; width: 50px;',
				),
				array(
					'title'    => esc_html__( 'Decimals separator', 'learnpress' ),
					'desc_tip' => esc_html__( 'This sets the decimal separator of displayed prices.', 'learnpress' ),
					'id'       => 'decimals_separator',
					'default'  => '.',
					'type'     => 'text',
					'css'      => 'min-width: 50px; width: 50px;',
				),
				array(
					'title'    => esc_html__( 'Number of decimals', 'learnpress' ),
					'desc_tip' => esc_html__( 'This sets the number of decimal points shown in displayed prices.', 'learnpress' ),
					'id'       => 'number_of_decimals',
					'default'  => '2',
					'type'     => 'number',
					'css'      => 'width: 50px;',
				),
				array(
					'type' => 'sectionend',
				),
				array(
					'title' => esc_html__( 'Other', 'learnpress' ),
					'type'  => 'title',
				),
				array(
					'title'    => esc_html__( 'Publish profile', 'learnpress' ),
					'id'       => 'publish_profile',
					'default'  => 'no',
					'type'     => 'checkbox',
					'desc_tip' => esc_html__( 'This option will add a sub-item "Privacy" under Setting tab on the Profile page. If users want to publish or hide their course, quiz tab when other users visit their profile page, they need to enable/disable that option in the Privacy section.', 'learnpress' ),
					'desc'     => __( 'Public all user profile page (Overview tab only).', 'learnpress' ),
				),
				array(
					'title'   => esc_html__( 'Instructor registration', 'learnpress' ),
					'desc'    => esc_html__( 'Enable the option in all registration forms.', 'learnpress' ),
					'id'      => 'instructor_registration',
					'default' => 'no',
					'type'    => 'checkbox',
				),
				array(
					'type' => 'sectionend',
				),
			)
		);

		$settings = apply_filters( 'learn_press_general_settings', $settings );

		return $settings;
	}

}
return new LP_Settings_General();

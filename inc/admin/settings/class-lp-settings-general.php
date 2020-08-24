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
		$this->text = __( 'General', 'learnpress' );
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
					'title' => __( 'Important Pages', 'learnpress' ),
					'type'  => 'heading',
				),
				array(
					'title'   => __( 'All Courses', 'learnpress' ),
					'id'      => 'courses_page_id',
					'default' => '',
					'type'    => 'pages-dropdown'
				),
				array(
					'title'   => __( 'Profile', 'learnpress' ),
					'id'      => 'profile_page_id',
					'default' => '',
					'type'    => 'pages-dropdown'
				),
				array(
					'title'   => __( 'Checkout', 'learnpress' ),
					'id'      => 'checkout_page_id',
					'default' => '',
					'type'    => 'pages-dropdown'
				),
				array(
					'title'   => __( 'Become Instructors', 'learnpress' ),
					'id'      => 'become_a_teacher_page_id',
					'default' => '',
					'type'    => 'pages-dropdown'
				),

				array(
					'title'   => __( 'Terms & Conditions', 'learnpress' ),
					'id'      => 'term_conditions_page_id',
					'default' => '',
					'type'    => 'pages-dropdown'
				),
// 3.x.x
//				array(
//					'title'   => __( 'Logout redirect', 'learnpress' ),
//					'id'      => 'logout_redirect_page_id',
//					'default' => '',
//					'type'    => 'pages-dropdown',
//					'desc'    => __( 'The page where user will be redirected to after logging out.', 'learnpress' )
//				),
				array(
					'title' => __( 'Currency', 'learnpress' ),
					'type'  => 'heading',
					'desc'  => __( 'Setting up your currency unit and its formatting.', 'learnpress' )
				),
				array(
					'title'  => __( 'Returning Customer Discount', 'learnpress' ),
					'id'    => 'returning_customer_discount',
					'type'  => 'number',
					'clone' => false,
					'desc'  => __( 'Set a percentage to discount the courses by if the its a returning customer (enter zero or leave blank for no discount)', 'learnpress' ),
					'min'   => 0,
					'std'   => 0
				),
				array(
					'title'   => __( 'Currency', 'learnpress' ),
					'id'      => 'currency',
					'default' => 'USD',
					'type'    => 'select',
					'class'   => 'lp-select-2',
					'options' => $currencies
				),
				array(
					'title'   => __( 'Currency Position', 'learnpress' ),
					'id'      => 'currency_pos',
					'default' => 'left',
					'type'    => 'select',
					'options' => learn_press_currency_positions()
				),
				array(
					'title'   => __( 'Thousands Separator', 'learnpress' ),
					'id'      => 'thousands_separator',
					'default' => ',',
					'type'    => 'text'
				),
				array(
					'title'   => __( 'Decimals Separator', 'learnpress' ),
					'id'      => 'decimals_separator',
					'default' => '.',
					'type'    => 'text'
				),
				array(
					'title'   => __( 'Number Of Decimals', 'learnpress' ),
					'id'      => 'number_of_decimals',
					'default' => '2',
					'type'    => 'number'
				),
				array(
					'title' => __( 'Other', 'learnpress' ),
					'type'  => 'heading',
				),
				array(
					'title'   => __( 'Publish Profile', 'learnpress' ),
					'id'      => 'publish_profile',
					'default' => 'no',
					'type'    => 'yes-no',
					'desc'    => __( 'User course data will be public.', 'learnpress' )
				),
				array(
					'title'   => __( 'Instructor Registration', 'learnpress' ),
					'desc'    => __( 'Enable the option in all registration forms.', 'learnpress' ),
					'id' => 'instructor_registration',
					'default' => 'no',
					'type'    => 'yes-no'
				)
					/*,
				array(
					'title' => __( 'Logout', 'learnpress' ),
					'type'  => 'title'
				),
				array(
					'title'   => __( 'Redirect to page', 'learnpress' ),
					'id' => 'logout_redirect_page_id',
					'default' => '',
					'type'    => 'pages-dropdown'
				),*/
			)
		);
		// Deprecated
		$settings = apply_filters( 'learn_press_general_settings', $settings );

		return $settings;
	}

}

return new LP_Settings_General();
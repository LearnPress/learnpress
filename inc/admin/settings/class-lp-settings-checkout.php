<?php

/**
 * Class LP_Settings_Checkout
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Classes/Settings
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_Settings_Checkout extends LP_Settings_Base {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->id   = 'checkout';
		$this->text = __( 'Checkout', 'learn_press' );

		parent::__construct();
	}

	/**
	 * Tab's sections
	 *
	 * @return mixed
	 */
	function get_sections() {
		$sections = array(
			'general' => __( 'General', 'learn_press' )
		);
		return $sections = apply_filters( 'learn_press_settings_sections_' . $this->id, $sections );
	}

	function output_section_general() {
		$view = learn_press_get_admin_view( 'settings/checkout.php' );
		include_once $view;
	}
}
//
return new LP_Settings_Checkout();
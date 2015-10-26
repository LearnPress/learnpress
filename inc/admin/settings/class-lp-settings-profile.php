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

class LP_Settings_Profile extends LP_Settings_Base {

	/**
	 * Constructor
	 */
	function __construct() {
		$this->id   = 'profile';
		$this->text = __( 'Profile', 'learn_press' );

		parent::__construct();
	}

	function output(){
		$view = learn_press_get_admin_view( 'settings/profile.php' );
		include_once $view;
	}
}
//
return new LP_Settings_Profile();
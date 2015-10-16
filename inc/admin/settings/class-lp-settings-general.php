<?php

/**
 * Class LP_Settings_General
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Settings/Classes
 * @version 1.0
 */
class LP_Settings_General extends LP_Settings_Base{
	/**
	 * Construct
	 */
	function __construct() {
		$this->id   = 'general';
		$this->text = __( 'General', 'learn_press' );
		//add_action( 'learn_press_settings_general', array( $this, 'output' ) );
		//add_action( 'learn_press_settings_save_general', array( $this, 'save' ) );
		parent::__construct();
	}

	function output() {
		$view = learn_press_get_admin_view( 'settings/general.php' );
		include_once $view;
	}

	/*function save() {
		$settings = LP_Admin_Settings::instance( 'general' );// $_POST['lpr_settings']['general'];
		$settings->bind( $_POST['learn_press'] );
		$settings->update();
	}*/
}

new LP_Settings_General();
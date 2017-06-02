<?php

/**
 * Class LP_Abstract_Settings
 */
class LP_Abstract_Settings {

	public function __construct() {
	}

	public function get_settings(){
		return false;
	}

	/**
	 * Print admin fields options.
	 */
	public function admin_options() {
		if(!$settings = $this->get_settings()){
			printf('There is no options.');
			return;
		}
		print_r($settings);
	}
}
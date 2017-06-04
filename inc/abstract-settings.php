<?php

/**
 * Class LP_Abstract_Settings
 */
class LP_Abstract_Settings {

	public function __construct() {
	}

	public function get_settings() {
		return false;
	}

	/**
	 * Print admin fields options.
	 */
	public function admin_options() {
		$settings = $this->get_settings();
		LP_Meta_Box_Helper::render_fields( $settings );
	}
}
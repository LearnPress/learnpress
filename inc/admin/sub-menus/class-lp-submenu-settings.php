<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_Submenu_Settings
 */
class LP_Submenu_Settings extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Settings constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-settings';
		$this->menu_title = __( 'Settings', 'learnpress' );
		$this->page_title = __( 'LearnPress Settings', 'learnpress' );
		$this->priority   = 30;

		$this->tabs = learn_press_settings_tabs_array();
	}

	/**
	 * Display menu content
	 */
	public function page_content() {
		echo 'xxxxx';
	}
}

return new LP_Submenu_Settings();
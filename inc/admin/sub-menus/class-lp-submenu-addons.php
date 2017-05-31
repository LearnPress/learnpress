<?php

/**
 * Class LP_Submenu_Addons
 */
class LP_Submenu_Addons extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Addons constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-addons';
		$this->menu_title = __( 'Addons', 'learnpress' );
		$this->page_title = __( 'LearnPress Addons', 'learnpress' );
		$this->priority   = 20;
	}

	/**
	 * Display content
	 */
	public function admin_content() {
		echo 'xxxxx';
	}
}
return new LP_Submenu_Addons();
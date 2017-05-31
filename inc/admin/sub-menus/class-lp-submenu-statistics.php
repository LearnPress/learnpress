<?php

/**
 * Class LP_Submenu_Statistics
 */
class LP_Submenu_Statistics extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Statistics constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-statistics';
		$this->menu_title = __( 'Statistics', 'learnpress' );
		$this->page_title = __( 'LearnPress Statistics', 'learnpress' );
		$this->priority   = 10;
	}

	public function admin_content() {
		echo 'xxxxx';
	}
}
return new LP_Submenu_Statistics();
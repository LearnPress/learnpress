<?php
/**
 * Class LP_Submenu_Themes
 *
 * @since 4.2.1
 * @version 1.0.0
 */
class LP_Submenu_Themes extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Addons constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-themes';
		$this->menu_title = __( 'Themes', 'learnpress' );
		$this->page_title = __( 'LearnPress Themes', 'learnpress' );
		$this->priority   = 20;
		$this->callback   = [ $this, 'display' ];

		parent::__construct();
	}

	public function display() {
		learn_press_admin_view( 'addons/html-themes' );
	}
}

return new LP_Submenu_Themes();

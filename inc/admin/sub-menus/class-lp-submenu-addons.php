<?php
/**
 * Class LP_Submenu_Addons
 *
 * @since 3.0.0
 */
class LP_Submenu_Addons extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Addons constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-addons';
		$this->menu_title = __( 'Add-ons', 'learnpress' );
		$this->page_title = __( 'LearnPress Add-ons', 'learnpress' );
		$this->priority   = 20;
		$this->callback   = [ $this, 'display' ];

		parent::__construct();
	}

	public function display() {
		echo '<h1>' . __( 'LearnPress Addons' ) . '</h1>';
		echo '<div class="lp-addons-page">';
		lp_skeleton_animation_html( 20 );
		echo '</div>';
	}
}

return new LP_Submenu_Addons();

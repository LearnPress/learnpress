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
		$this->menu_title = __( 'Add-ons', 'learnpress' ) . '<span class="lp-notify has-addon-update"></span>';
		$this->page_title = __( 'LearnPress Add-ons', 'learnpress' );
		$this->priority   = 20;
		$this->callback   = [ $this, 'display' ];

		parent::__construct();
	}

	public function display() {
		echo '<h1>' . __( 'LearnPress Addons' ) . '</h1>';
		echo '<p><strong><i>* If you use premium theme and theme include addons, you can go to tab Plugins to download/update</strong></i></p>';
		echo sprintf(
			'<p>* If you buy premium addon separately, you can enter purchase code( %s ) and download/update addons on here</p>',
			'<a href="https://thimpress.com/my-account/" target="_blank">get from your account</a>'
		);
		echo '<div class="lp-addons-page">';
		lp_skeleton_animation_html( 20 );
		echo '</div>';
	}
}

return new LP_Submenu_Addons();

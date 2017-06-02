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

		$sections       = array(
			'installed'  => __( 'Installed' ),
			'more' => __( 'Get more' )
		);
		$this->sections = apply_filters( 'learn-press/admin/page-addons/sections', $sections );
		parent::__construct();
	}

	/**
	 * Display content
	 */
	public function page_content() {
		parent::page_content();
		$this->display_section();
	}
}
return new LP_Submenu_Addons();
<?php

/**
 * Class LP_Submenu_Tools
 */
class LP_Submenu_Tools extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Tools constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-tools';
		$this->menu_title = __( 'Tools', 'learnpress' );
		$this->page_title = __( 'LearnPress Tools', 'learnpress' );
		$this->priority   = 40;

		$this->tabs = apply_filters( 'learn-press/admin/tools-tabs', array(
				'template' => __( 'Template', 'learnpress' ),
				'database' => __( 'Database', 'learnpress' )
			)
		);

		parent::__construct();
	}

	public function page_content_database() {
		learn_press_admin_view( 'tools/html-database' );
	}

	public function page_content_template() {
		learn_press_admin_view( 'tools/html-template' );
	}

	public function enqueue_assets() {
		wp_enqueue_script( 'learn-press-submenu-tools', LP()->plugin_url( 'assets/js/admin/admin-tools.js' ), array( 'jquery' ) );
	}

	/**
	 * Display page
	 */
	public function page_content() {
		parent::page_content();
	}
}

return new LP_Submenu_Tools();
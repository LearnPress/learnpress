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

		$this->tabs = apply_filters(
			'learn-press/admin/tools-tabs',
			array(
				'template' => __( 'Template', 'learnpress' ),
				'database' => __( 'Database', 'learnpress' ),
				'course'   => __( 'Course', 'learnpress' ),
				'cache'    => __( 'Cache', 'learnpress' )
			)
		);

		parent::__construct();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		$this->_process_actions();
	}

	public function admin_scripts() {
		learn_press_admin_assets()->enqueue_script( 'lp-update' );
	}

	protected function _process_actions() {
		$has_action = true;
		switch ( LP_Request::get( 'page' ) ) {
			case 'lp-clear-cache':
				LP_Hard_Cache::flush();
				break;
			case'lp-toggle-hard-cache-option':
				update_option( 'learn_press_enable_hard_cache', LP_Request::get( 'v' ) == 'yes' ? 'yes' : 'no' );
				break;
			default:
				$has_action = false;
		}

		if ( $has_action ) {
			die();
		}
	}

	public function page_content_database() {
		learn_press_admin_view( 'tools/html-database' );
	}

	public function page_content_template() {
		learn_press_admin_view( 'tools/html-template' );
	}

	public function page_content_cache() {
		learn_press_admin_view( 'tools/html-cache' );
	}

	public function page_content_course() {
		learn_press_admin_view( 'tools/html-course' );
	}

	public function enqueue_assets() {
		//wp_enqueue_script( 'learn-press-submenu-tools', LP()->plugin_url( 'assets/js/admin/admin-tools.js' ), array( 'jquery' ) );
	}

	/**
	 * Display page
	 */
	public function page_content() {
		parent::page_content();
	}
}

return new LP_Submenu_Tools();
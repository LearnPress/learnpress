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
		$this->callback   = [ $this, 'display' ];

		$this->tabs = apply_filters(
			'learn-press/admin/tools-tabs',
			array(
				'course'          => __( 'Course Data', 'learnpress' ),
				'assign_course'   => __( 'Assign/Unassigned Course', 'learnpress' ),
				'database'        => __( 'Database', 'learnpress' ),
				'template'        => __( 'Templates', 'learnpress' ),
				'lp_beta_version' => __( 'LearnPress Beta Version', 'learnpress' ),
				'cache'           => __( 'Cache', 'learnpress' ),
			)
		);

		parent::__construct();
	}

	/**
	 * Show tools for database.
	 *
	 * @return void
	 */
	public function page_content_database() {
		learn_press_admin_view( 'tools/html-database' );
	}

	/**
	 * Show template override by theme.
	 *
	 * @return void
	 */
	public function page_content_template() {
		learn_press_admin_view( 'tools/html-template' );
	}

	/**
	 * Show tools course data.
	 *
	 * @return void
	 */
	public function page_content_course() {
		learn_press_admin_view( 'tools/html-course' );
	}

	/**
	 * Show beta version LP.
	 *
	 * @return void
	 */
	public function page_content_lp_beta_version() {
		$lp_beta_version_info = LP_Admin_Notice::check_lp_beta_version();
		learn_press_admin_view(
			'admin-notices/beta-version',
			[
				'data' => [
					'check' => 1,
					'info'  => $lp_beta_version_info,
				],
			]
		);
	}

	public function page_content_assign_course() {
		learn_press_admin_view( 'tools/course/html-assign-course' );
		learn_press_admin_view( 'tools/course/html-unassign-course' );
	}

	/**
	 * Temporary, go to this page run clear all cache
	 *
	 * @since 4.2.5.4
	 * @version 1.0.0
	 */
	public function page_content_cache() {
		$lp_cache = new LP_Cache( true );
		$lp_cache->clear_all();

		echo sprintf(
			'<form action="" method="post"><button class="button button-primary" type="submit">%s</button></form>',
			__( 'Clear all cache', 'learnpress' )
		);
	}

	/**
	 * Display page
	 */
	public function page_content() {
		parent::page_content();
	}
}

return new LP_Submenu_Tools();

<?php

/**
 * Class LP_Submenu_Statistics
 *
 * @since 4.2.5.5
 * @author khanhbd
 */
class LP_Submenu_Statistics extends LP_Abstract_Submenu {
	/**
	 * LP_Submenu_Statistics_Beta constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-statistics';
		$this->menu_title = __( 'Statistics', 'learnpress' );
		$this->page_title = __( 'LearnPress Statistics', 'learnpress' );
		$this->priority   = 10;
		$this->callback   = array( $this, 'display' );
		$tabs             = array(
			'overview'    => __( 'Overview', 'learnpress' ),
			'orders'      => __( 'Orders', 'learnpress' ),
			'courses'     => __( 'Courses', 'learnpress' ),
			'users'       => __( 'Users', 'learnpress' ),
			'instructors' => __( 'Instructors', 'learnpress' ),
		);
		$this->tabs       = apply_filters(
			'learn-press/admin/page-statistic-tabs',
			$tabs
		);

		// add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

		parent::__construct();
	}

	public function page_content_courses() {
		learn_press_admin_view( $this->tab_view( 'courses' ) );
	}

	public function page_content_overview() {
		learn_press_admin_view( $this->tab_view( 'overview' ) );
	}

	public function page_content_users() {
		learn_press_admin_view( $this->tab_view( 'users' ) );
	}

	public function page_content_orders() {
		learn_press_admin_view( $this->tab_view( 'orders' ) );
	}

	public function page_content_instructors() {
		learn_press_admin_view( $this->tab_view( 'instructors' ) );
	}

	/**
	 * Resolve the view file for a statistics tab, allowing a full-body override
	 * ( swap the whole tab template without touching the learn_press_admin_view
	 * path filter's file-matching ).
	 *
	 * @param string $tab Tab id.
	 * @return string View name passed to learn_press_admin_view().
	 * @since 4.4.2
	 */
	private function tab_view( string $tab ): string {
		/**
		 * Filter the view file rendered for a statistics tab.
		 *
		 * @param string $view_name Default "statistics/{tab}".
		 * @param string $tab       Tab id.
		 * @since 4.4.2
		 */
		return (string) apply_filters( 'learn-press/statistics/tab-view', "statistics/{$tab}", $tab );
	}
}

return new LP_Submenu_Statistics();

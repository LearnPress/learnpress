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
		$this->callback   = [ $this, 'display' ];
		$tabs             = array(
			'overview' => __( 'Overview', 'learnpress' ),
			'orders'   => __( 'Orders', 'learnpress' ),
			'courses'  => __( 'Courses', 'learnpress' ),
			'users'    => __( 'Users', 'learnpress' ),
		);
		$this->tabs       = apply_filters(
			'learn-press/admin/page-statistic-tabs',
			$tabs
		);

		//add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

		parent::__construct();
	}

	public function page_content_courses() {
		learn_press_admin_view( 'statistics/courses' );
	}

	public function page_content_overview() {
		learn_press_admin_view( 'statistics/overview' );
	}

	public function page_content_users() {
		learn_press_admin_view( 'statistics/users' );
	}

	public function page_content_orders() {
		learn_press_admin_view( 'statistics/orders' );
	}
}

return new LP_Submenu_Statistics();

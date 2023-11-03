<?php

/**
 * Class LP_Submenu_Statistics_Beta
 *
 * @since 3.0.0
 */
class LP_Submenu_Statistics_Beta extends LP_Abstract_Submenu {
	/**
	 * LP_Submenu_Statistics_Beta constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-statistics-beta';
		$this->menu_title = __( 'Statistics Beta', 'learnpress' );
		$this->page_title = __( 'LearnPress Statistics Beta', 'learnpress' );
		$this->priority   = 100;
		$this->callback   = [ $this, 'display' ];
		$tabs             = array(
			'overview' => __( 'Overview', 'learnpress' ),
			'orders'   => __( 'Orders', 'learnpress' ),
			'courses'  => __( 'Courses', 'learnpress' ),
			'users'    => __( 'Users', 'learnpress' ),
		);
		$this->tabs       = apply_filters(
			'learn-press/admin/page-statistic-beta-tabs',
			$tabs
		);

		//add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

		parent::__construct();
	}

	public function page_content_courses() {
		learn_press_admin_view( 'statistics-beta/courses' );
	}

	public function page_content_overview() {
		learn_press_admin_view( 'statistics-beta/overview' );
	}

	public function page_content_users() {
		learn_press_admin_view( 'statistics-beta/users' );
	}

	public function page_content_orders() {
		learn_press_admin_view( 'statistics-beta/orders' );
	}
}

return new LP_Submenu_Statistics_Beta();

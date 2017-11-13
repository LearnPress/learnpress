<?php
/**
 * Admin statistic
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Admin_Submenu_Statistic
 */
class LP_Admin_Submenu_Statistic {
	/**
	 * @var string
	 */
	public $tab = '';

	/**
	 * @var string
	 */
	public $section = '';

	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * LP_Admin_Submenu_Statistic constructor.
	 */
	public function __construct() {
		add_action( 'learn_press_get_stats_general', array( $this, 'get_stats_general' ) );
		add_action( 'learn_press_get_stats_users', array( $this, 'get_stats_users' ) );
		add_action( 'learn_press_get_stats_courses', array( $this, 'get_stats_courses' ) );
		add_action( 'learn_press_get_stats_orders', array( $this, 'get_stats_orders' ) );
		add_action( 'wp_ajax_learn_press_load_chart', array( $this, 'load_chart' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_chart_scripts' ) );
	}

	/**
	 * Statistic page
	 */
	public function display() {
		$this->tab     = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
		$this->section = isset( $_GET['section'] ) ? $_GET['section'] : '';
		$tabs = array();
		if( current_user_can(LP_TEACHER_ROLE) ) {
			$this->tab     = isset( $_GET['tab'] ) ? $_GET['tab'] : 'courses';
			$tabs          = apply_filters( 'learn_press_statistics_tabs', array(
				'courses' => __( 'Courses', 'learnpress' ),
				'orders'  => __( 'Orders', 'learnpress' ),
			) );
		} else {
			$tabs          = apply_filters( 'learn_press_statistics_tabs', array(
				'general'   => __( 'General', 'learnpress' ),
				'users'   => __( 'Users', 'learnpress' ),
				'courses' => __( 'Courses', 'learnpress' ),
				'orders'  => __( 'Orders', 'learnpress' ),
			) );
		}
		echo '<div class="wrap">';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab == $this->tab ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=learn-press-statistics&tab=$tab'>$name</a>";
		}
		echo '</h2>';
		do_action( 'learn_press_get_stats_' . $this->tab . '' );
		echo '</div>';
	}

	public function get_stats_general() {
		if( current_user_can(LP_TEACHER_ROLE) ) {
			return;
		}
		require_once learn_press_get_admin_view( 'statistics/general.php' );
	}

	/**
	 *
	 */
	public function get_stats_users() {
		if( current_user_can(LP_TEACHER_ROLE) ) {
			return;
		}
		require_once learn_press_get_admin_view( 'statistics/users.php' );
	}

	/**
	 *
	 */
	public function get_stats_courses() {
		require_once learn_press_get_admin_view( 'statistics/courses.php' );
	}

	/**
	 *
	 */
	public function get_stats_orders() {
		require_once learn_press_get_admin_view( 'statistics/orders.php' );
	}

	public function load_chart() {
		$type     = learn_press_get_request( 'type' );
		$response = null;
		switch ( $type ) {
			case 'user-last-7-days':
				$response = learn_press_get_chart_users( null, 'days', 7 );
				break;
			case 'user-last-30-days':
				$response = learn_press_get_chart_users( null, 'days', 30 );
				break;
			case 'user-last-12-months':
				$response = learn_press_get_chart_users( null, 'months', 12 );
				break;
			case 'user-custom-time':
				$range     = learn_press_get_request( 'range' );
				$from_time = strtotime( $range[0] );
				$to_time   = strtotime( $range[1] );
				list( $from_d, $from_m, $from_y ) = explode( ' ', date( 'd m Y', $from_time ) );
				list( $to_d, $to_m, $to_y ) = explode( ' ', date( 'd m Y', $to_time ) );
				if ( $from_y != $to_y ) {
					$response = learn_press_get_chart_users( $to_time, 'years', $to_y - $from_y + 1 );
				} else {
					if ( $from_m != $to_m ) {
						$response = learn_press_get_chart_users( $to_time, 'months', $to_m - $from_m + 1 );
					} else {
						$response = learn_press_get_chart_users( $to_time, 'days', $to_d - $from_d + 1 );
					}
				}
				break;
			case 'user-all':
				global $wpdb;
				$results = $wpdb->get_row( "
					SELECT min(u.user_registered) as `from`, max(u.user_registered) as `to`
					FROM {$wpdb->users} u
				" );

				if ( $results ) {
					$_POST['range'] = array( date( 'Y/m/d', strtotime( $results->from ) ), date( 'Y/m/d', strtotime( $results->to ) ) );
					$_POST['type']  = 'user-custom-time';
					$this->load_chart();
					return;
				}
				break;

			//////////////////
			case 'course-last-7-days':
				$response = learn_press_get_chart_courses( null, 'days', 7 );
				break;
			case 'course-last-30-days':
				$response = learn_press_get_chart_courses( null, 'days', 30 );
				break;
			case 'course-last-12-months':
				$response = learn_press_get_chart_courses( null, 'months', 12 );
				break;
			case 'course-custom-time':
				$range     = learn_press_get_request( 'range' );
				$from_time = strtotime( $range[0] );
				$to_time   = strtotime( $range[1] );
				list( $from_d, $from_m, $from_y ) = explode( ' ', date( 'd m Y', $from_time ) );
				list( $to_d, $to_m, $to_y ) = explode( ' ', date( 'd m Y', $to_time ) );
				if ( $from_y != $to_y ) {
					$months = abs( ( date( 'Y', $to_time ) - date( 'Y', $from_time ) ) * 12 + ( date( 'm', $to_time ) - date( 'm', $from_time ) ) ) + 1;
					if ( $months > 12 ) {
						$response = learn_press_get_chart_courses( $to_time, 'years', $to_y - $from_y + 1 );
					} else {
						$response = learn_press_get_chart_courses( $to_time, 'months', $months );
					}
				} else {
					if ( $from_m != $to_m ) {
						$response = learn_press_get_chart_courses( $to_time, 'months', $to_m - $from_m + 1 );
					} else {
						$response = learn_press_get_chart_courses( $to_time, 'days', $to_d - $from_d + 1 );
					}
				}
				break;
			case 'course-all':
				global $wpdb;
				$results = $wpdb->get_row(
					$wpdb->prepare( "
						SELECT min(c.post_date) as `from`, max(c.post_date) as `to`
						FROM {$wpdb->posts} c
						WHERE c.post_date <> %s
						AND c.post_type = %s
					", '0000-00-00 00:00:00', 'lp_course' )
				);

				if ( $results ) {
					$_POST['range'] = array( date( 'Y/m/d', strtotime( $results->from ) ), date( 'Y/m/d', strtotime( $results->to ) ) );
					$_POST['type']  = 'course-custom-time';
					$this->load_chart();
					return;
				}

			//////////////////
			case 'order-last-7-days':
				$response = learn_press_get_chart_orders( null, 'days', 7 );
				break;
			case 'order-last-30-days':
				$response = learn_press_get_chart_orders( null, 'days', 30 );
				break;
			case 'order-last-12-months':
				$response = learn_press_get_chart_orders( null, 'months', 12 );
				break;
			case 'order-custom-time':
				$range     = learn_press_get_request( 'range' );
				$from_time = strtotime( $range[0] );
				$to_time   = strtotime( $range[1] );
				list( $from_d, $from_m, $from_y ) = explode( ' ', date( 'd m Y', $from_time ) );
				list( $to_d, $to_m, $to_y ) = explode( ' ', date( 'd m Y', $to_time ) );
				if ( $from_y != $to_y ) {
					$months = abs( ( date( 'Y', $to_time ) - date( 'Y', $from_time ) ) * 12 + ( date( 'm', $to_time ) - date( 'm', $from_time ) ) ) + 1;
					if ( $months > 12 ) {
						$response = learn_press_get_chart_orders( $to_time, 'years', $to_y - $from_y + 1 );
					} else {
						$response = learn_press_get_chart_orders( $to_time, 'months', $months );
					}
				} else {
					if ( $from_m != $to_m ) {
						$response = learn_press_get_chart_orders( $to_time, 'months', $to_m - $from_m + 1 );
					} else {
						$response = learn_press_get_chart_orders( $to_time, 'days', $to_d - $from_d + 1 );
					}
				}
				break;
			case 'order-all':
				global $wpdb;
				$results = $wpdb->get_row(
					$wpdb->prepare( "
						SELECT min(c.post_date) as `from`, max(c.post_date) as `to`
						FROM {$wpdb->posts} c
						WHERE c.post_date <> %s
						AND c.post_type = %s
					", '0000-00-00 00:00:00', 'lp_order' )
				);

				if ( $results ) {
					$_POST['range'] = array( date( 'Y/m/d', strtotime( $results->from ) ), date( 'Y/m/d', strtotime( $results->to ) ) );
					$_POST['type']  = 'order-custom-time';
					$this->load_chart();
					return;
				}
		}

		learn_press_send_json( $response );
	}

	/**
	 *
	 */
	public function load_chart_scripts() {
		//wp_enqueue_style( 'lpr-jquery-ui-css', LP_CSS_URL . 'jquery-ui.css' );
		//wp_enqueue_script( 'lpr-jquery-ui-js', LP_JS_URL . 'jquery-ui.js', array( 'jquery' ), '', false );
		wp_enqueue_script( 'learn-press-chart', LP_JS_URL . 'chart.min.js', array( 'jquery', 'jquery-ui-datepicker' ) );
		wp_enqueue_script( 'learn-press-statistic', LP_JS_URL . 'admin/statistic.js' );
	}

	/**
	 * @return LP_Admin_Submenu_Statistic|null
	 */
	public static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

return LP_Admin_Submenu_Statistic::instance();




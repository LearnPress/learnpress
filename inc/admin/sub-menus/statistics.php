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
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * LP_Admin_Submenu_Statistic constructor.
	 */
	function __construct() {
		add_action( 'learn_press_get_stats_students', array( $this, 'get_stats_students' ) );
		add_action( 'learn_press_get_stats_courses', array( $this, 'get_stats_courses' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_chart_scripts' ) );
	}

	/**
	 * Statistic page
	 */
	function display() {
		$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'students';
		$tabs        = apply_filters( 'learn_press_statistics_tabs', array(
			'students' => __( 'Students', 'learnpress' ),
			'courses'  => __( 'Courses', 'learnpress' ),
		) );
		echo '<div class="wrap">';
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $tab => $name ) {
			$class = ( $tab == $current_tab ) ? ' nav-tab-active' : '';
			echo "<a class='nav-tab$class' href='?page=learn_press_statistics&tab=$tab'>$name</a>";
		}
		echo '</h2>';
		do_action( 'learn_press_get_stats_' . $current_tab . '' );
		echo '</div>';
	}

	/**
	 *
	 */
	function get_stats_students() {
		require_once( LP_PLUGIN_PATH . "/inc/admin/statistics/students.php" );
	}

	/**
	 *
	 */
	function get_stats_courses() {
		require_once( LP_PLUGIN_PATH . "/inc/admin/statistics/courses.php" );
	}

	/**
	 *
	 */
	function load_chart_scripts() {
		//wp_enqueue_style( 'lpr-jquery-ui-css', LP_CSS_URL . 'jquery-ui.css' );
		//wp_enqueue_script( 'lpr-jquery-ui-js', LP_JS_URL . 'jquery-ui.js', array( 'jquery' ), '', false );
		wp_enqueue_script( 'learn-press-chart', LP_JS_URL . 'chart.min.js', array( 'jquery' ) );
		wp_enqueue_script( 'learn-press-statistic', LP_JS_URL . 'admin/statistic.js' );
	}

	/**
	 * @return LP_Admin_Submenu_Statistic|null
	 */
	static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

return LP_Admin_Submenu_Statistic::instance();




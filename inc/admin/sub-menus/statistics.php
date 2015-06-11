<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Statistic page
 */
function learn_press_statistic_page() {
	$current_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'students';
	$tabs        = apply_filters( 'learn_press_statistics_tabs', array(
		'students' => __( 'Students', 'learn_press' ),
		'courses'  => __( 'Courses', 'learn_press' ),
	) );
	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $tab => $name ) {
		$class = ( $tab == $current_tab ) ? ' nav-tab-active' : '';
		echo "<a class='nav-tab$class' href='?page=learn_press_statistics&tab=$tab'>$name</a>";
	}
	echo '</h2>';
	do_action( 'learn_press_get_stats_' . $current_tab . '' );
}

add_action( 'learn_press_get_stats_students', 'learn_press_get_stats_students' );
function learn_press_get_stats_students() {
	require_once( LPR_PLUGIN_PATH . "/inc/admin/statistics/students.php" );
}

add_action( 'learn_press_get_stats_courses', 'learn_press_get_stats_courses' );
function learn_press_get_stats_courses() {
	require_once( LPR_PLUGIN_PATH . "/inc/admin/statistics/courses.php" );
}

function learn_press_load_chart_scripts() {
	wp_enqueue_style( 'lpr-jquery-ui-css', LPR_CSS_URL . 'jquery-ui.css' );
	wp_enqueue_script( 'lpr-jquery-ui-js', LPR_JS_URL . 'jquery-ui.js', array( 'jquery' ), '', false );
	wp_enqueue_script( 'lpr-chart', LPR_JS_URL . 'chart.min.js', array( 'jquery' ), '', false );
	wp_enqueue_script( 'lpr-custom-chart', LPR_JS_URL . 'custom.chart.js', array( 'jquery' ), '', false );
}

add_action( 'admin_enqueue_scripts', 'learn_press_load_chart_scripts' );


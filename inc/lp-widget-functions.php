<?php
/**
 * Include & register widget.
 *
 * @package LearnPress/Widgets
 * @author ThimPress <Nhamdv>
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/Widgets/course-extra.php';
require_once dirname( __FILE__ ) . '/Widgets/course-info.php';
require_once dirname( __FILE__ ) . '/Widgets/course-progress.php';
require_once dirname( __FILE__ ) . '/Widgets/featured-courses.php';
require_once dirname( __FILE__ ) . '/Widgets/popular-courses.php';
require_once dirname( __FILE__ ) . '/Widgets/recent-courses.php';
require_once dirname( __FILE__ ) . '/Widgets/course-filter.php';

add_action(
	'widgets_init',
	function() {
		register_widget( 'LP_Widget_Course_Extra' );
		register_widget( 'LP_Widget_Course_Info' );
		register_widget( 'LP_Widget_Course_Progress' );
		register_widget( 'LP_Widget_Featured_Courses' );
		register_widget( 'LP_Widget_Popular_Courses' );
		register_widget( 'LP_Widget_Recent_Courses' );
		register_widget( 'LP_Widget_Course_Filter' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function() {
		if ( isset( $_GET['legacy-widget-preview'] ) ) {
			wp_enqueue_style( 'learnpress-widgets-admin', LP_PLUGIN_URL . 'assets/css/widgets.css', array() );
		}
	}
);

add_action(
	'elementor/editor/before_enqueue_scripts',
	function() {
		wp_enqueue_script( 'learnpress-widgets-eleentor-select2', LP_PLUGIN_URL . 'assets/src/js/vendor/select2.full.min.js', array( 'jquery' ), false );
		wp_enqueue_script( 'learnpress-widgets-eleentor', LP_PLUGIN_URL . 'assets/js/dist/admin/pages/widgets.js', array( 'jquery' ), false );
	}
);


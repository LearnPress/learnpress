<?php
/**
 * Include & register widget.
 *
 * @package LearnPress/Widgets
 * @author ThimPress <Nhamdv>
 * @version 4.0.0
 */

defined( 'ABSPATH' ) || exit;

require_once dirname( __FILE__ ) . '/widgets/course-extra.php';
require_once dirname( __FILE__ ) . '/widgets/course-info.php';
require_once dirname( __FILE__ ) . '/widgets/course-progress.php';
require_once dirname( __FILE__ ) . '/widgets/course-sidebar-preview.php';
require_once dirname( __FILE__ ) . '/widgets/featured-courses.php';
require_once dirname( __FILE__ ) . '/widgets/popular-courses.php';
require_once dirname( __FILE__ ) . '/widgets/recent-courses.php';

add_action(
	'widgets_init',
	function() {
		register_widget( 'LP_Widget_Course_Extra' );
		register_widget( 'LP_Widget_Course_Info' );
		register_widget( 'LP_Widget_Course_Progress' );
		register_widget( 'LP_Widget_Course_Sidebar_Preview' );
		register_widget( 'LP_Widget_Featured_Courses' );
		register_widget( 'LP_Widget_Popular_Courses' );
		register_widget( 'LP_Widget_Recent_Courses' );
	}
);

<?php

/**
 * Recent Courses Widget.
 *
 * @author  ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  4.0.0
 * @extends  LP_Widget
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Course_Sidebar_Preview' ) ) {

	/**
	 * Class LP_Widget_Course_Sidebar_Preview
	 */
	class LP_Widget_Course_Sidebar_Preview extends LP_Widget {

		/**
		 * LP_Widget_Course_Sidebar_Preview constructor.
		 */
		public function __construct() {
			$this->widget_cssclass    = 'learnpress widget_course_sidebar_preview';
			$this->widget_description = esc_html__( 'Display the Course Sidebar Preview', 'learnpress' );
			$this->widget_id          = 'learnpress_widget_course_sidebar_preview';
			$this->widget_name        = esc_html__( 'LearnPress - Course Sidebar Preview', 'learnpress' );

			parent::__construct();

			add_filter( 'learn-press/widget/display-' . $this->id_base, 'learn_press_is_course' );
		}

		/**
		 * Show widget in frontend.
		 */
		public function widget( $args, $instance ) {
			if ( ! learn_press_is_course() ) {
				return;
			}

			LP()->template( 'course' )->course_sidebar_preview();
		}
	}
}

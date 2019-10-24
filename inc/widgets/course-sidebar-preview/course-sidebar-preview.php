<?php

/**
 * Recent Courses Widget.
 *
 * @author  ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  3.0.0
 * @extends  LP_Widget
 */

/**
 * Prevent loading this file directly
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

			// widget options
			$this->options = array(

			);

			// register widget
			parent::__construct();

			add_filter( 'learn-press/widget/display-' . $this->id_base, 'learn_press_is_course' );
		}

		/**
		 * Show widget in frontend.
		 */
		public function show() {
			LP()->template()->course_sidebar_preview();
		}

	}
}
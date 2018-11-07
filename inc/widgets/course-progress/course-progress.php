<?php

/**
 * Course Progress Widget.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  3.0.0
 * @extends  LP_Widget
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Course_Progress' ) ) {

	/**
	 * Class LP_Widget_Course_Progress
	 */
	class LP_Widget_Course_Progress extends LP_Widget {

		/**
		 * @var array
		 */
		private $courses = array();

		/**
		 * @var null
		 */
		private $curd = null;

		/**
		 * LP_Widget_Course_Progress constructor.
		 */
		public function __construct() {

			// course curd
			$this->curd = new LP_Course_CURD();

			// widget options
			$this->options = array(
				'title'     => array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "title",
					'type' => 'text',
					'std'  => __( 'Course Progress', 'learnpress' )
				),
				'css_class' => array(
					'name' => __( 'CSS Class', 'learnpress' ),
					'id'   => "css_class",
					'type' => 'text',
					'std'  => ''
				)
			);

			// register widget
			parent::__construct();

			add_filter( 'learn-press/widget/display-' . $this->id_base, array( $this, 'is_singular' ) );
		}

		public function is_singular() {
			return learn_press_is_course() && $this->get_remaining_time();
		}

		/**
		 * Get remaining time for current course.
		 *
		 * @return bool|int|string
		 */
		public function get_remaining_time() {
			if ( ! $course = LP_Global::course() ) {
				return false;
			}

			if ( ! $user = LP_Global::user() ) {
				return false;
			}

			if ( false === ( $remaining_time = $user->get_course_remaining_time( $course->get_id() ) ) ) {
				return false;
			}

			return $remaining_time;
		}

		/**
		 * Show widget in frontend.
		 */
		public function show() {

			if ( false === ( $remaining_time = $this->get_remaining_time() ) ) {
				return;
			}

			$widget = $this;

			include $this->get_locate_template( $this->get_slug() );
		}

	}
}
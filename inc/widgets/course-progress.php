<?php

/**
 * Course Progress Widget.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  4.0.0
 * @extends  LP_Widget
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Course_Progress' ) ) {

	/**
	 * Class LP_Widget_Course_Progress
	 */
	class LP_Widget_Course_Progress extends LP_Widget {

		public function __construct() {

			$this->widget_cssclass    = 'learnpress widget_course_progress';
			$this->widget_description = esc_html__( 'Display the Course Progress', 'learnpress' );
			$this->widget_id          = 'learnpress_widget_course_progress';
			$this->widget_name        = esc_html__( 'LearnPress - Course Progress', 'learnpress' );
			$this->settings           = array(
				'title'     => array(
					'label' => esc_html__( 'Title', 'learnpress' ),
					'type'  => 'text',
					'std'   => esc_html__( 'Course Progress', 'learnpress' ),
				),
				'css_class' => array(
					'label' => esc_html__( 'CSS Class', 'learnpress' ),
					'type'  => 'text',
					'std'   => '',
				),
			);

			parent::__construct();
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
			$course = LP_Global::course();

			if ( ! $course ) {
				return false;
			}

			$user = LP_Global::user();

			if ( ! $user ) {
				return false;
			}

			$remaining_time = $user->get_course_remaining_time( $course->get_id() );

			if ( false === $remaining_time ) {
				return false;
			}

			return $remaining_time;
		}

		/**
		 * Show widget in frontend.
		 */
		public function widget( $args, $instance ) {

			if ( ! $this->is_singular() ) {
				return;
			}

			$remaining_time = $this->get_remaining_time();

			if ( false === $remaining_time ) {
				return;
			}

			$this->widget_start( $args, $instance );

			include learn_press_locate_template( 'widgets/course-progress.php' );

			$this->widget_end( $args );
		}

	}
}

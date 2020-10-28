<?php

/**
 * Course Info Widget.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  4.0.0
 * @extends  LP_Widget
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Course_Info' ) ) {

	/**
	 * Class LP_Widget_Course_Info
	 */
	class LP_Widget_Course_Info extends LP_Widget {

		/**
		 * LP_Widget_Course_Info constructor.
		 */
		public function __construct() {
			$this->widget_cssclass    = 'learnpress widget_course_info';
			$this->widget_description = esc_html__( 'Display the Course Infomation', 'learnpress' );
			$this->widget_id          = 'learnpress_widget_course_info';
			$this->widget_name        = esc_html__( 'LearnPress - Course Info', 'learnpress' );
			$this->settings           = array(
				'title'     => array(
					'label' => esc_html__( 'Title', 'learnpress' ),
					'type'  => 'text',
					'std'   => esc_html__( 'Course Info', 'learnpress' ),
				),
				'css_class' => array(
					'label' => esc_html__( 'CSS Class', 'learnpress' ),
					'type'  => 'text',
					'std'   => '',
				),
			);

			parent::__construct();
		}

		/**
		 * Show widget in frontend.
		 */
		public function widget( $args, $instance ) {
			if ( ! learn_press_is_course() ) {
				return;
			}

			$this->widget_start( $args, $instance );

			include learn_press_locate_template( 'widgets/course-info.php' );

			$this->widget_end( $args );
		}
	}
}

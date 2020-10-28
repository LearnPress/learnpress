<?php

/**
 * Recent Courses Widget.
 *
 * @author  ThimPress <nhamdv>
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  4.0.0
 * @extends  LP_Widget
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Recent_Courses' ) ) {

	/**
	 * Class LP_Widget_Recent_Courses
	 */
	class LP_Widget_Recent_Courses extends LP_Widget {

		public function __construct() {
			$this->widget_cssclass    = 'learnpress widget_course_recent';
			$this->widget_description = esc_html__( 'Display the Recent courses', 'learnpress' );
			$this->widget_id          = 'learnpress_widget_course_recent';
			$this->widget_name        = esc_html__( 'LearnPress - Recent Courses', 'learnpress' );
			$this->settings           = array(
				'title'                  => array(
					'label' => __( 'Title', 'learnpress' ),
					'type'  => 'text',
					'std'   => __( 'Recent Courses', 'learnpress' ),
				),
				'show_teacher'           => array(
					'label' => __( 'Show teacher', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'show_lesson'            => array(
					'label' => __( 'Show lesson', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'show_thumbnail'         => array(
					'label' => __( 'Show Thumbnail', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'limit'                  => array(
					'label' => __( 'Limit', 'learnpress' ),
					'type'  => 'number',
					'min'   => 1,
					'std'   => 4,
				),
				'desc_length'            => array(
					'label' => __( 'Description Length', 'learnpress' ),
					'type'  => 'number',
					'min'   => 0,
					'std'   => 10,
				),
				'show_enrolled_students' => array(
					'label' => __( 'Show Enrolled Students', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'show_price'             => array(
					'label' => __( 'Show Price', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'css_class'              => array(
					'label' => __( 'CSS Class', 'learnpress' ),
					'type'  => 'text',
					'std'   => '',
				),
				'bottom_link_text'       => array(
					'label' => __( 'Go to Courses', 'learnpress' ),
					'type'  => 'text',
					'std'   => 'LP Courses',
				),
			);

			parent::__construct();
		}

		/**
		 * Show widget in frontend.
		 */
		public function widget( $args, $instance ) {
			if ( $this->get_cached_widget( $args ) ) {
				return;
			}

			ob_start();

			$curd    = new LP_Course_CURD();
			$courses = $curd->get_recent_courses( array( 'limit' => (int) $instance['limit'] ) );

			$this->widget_start( $args, $instance );

			include learn_press_locate_template( 'widgets/recent-courses.php' );

			$this->widget_end( $args );

			echo $this->cache_widget( $args, ob_get_clean() );
		}
	}
}

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
				'course_id' => array(
					'label'     => esc_html__( 'Select Course', 'learnpress' ),
					'type'      => 'autocomplete',
					'post_type' => LP_COURSE_CPT,
					'std'       => '',
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
		 * @throws Exception
		 */
		public function lp_rest_api_content( $instance, $params ) {
			$user_id = get_current_user_id();

			if ( empty( $user_id ) ) {
				return new WP_Error( 'no_user', esc_html__( 'You need login to view Course Progress', 'learnpress' ) );
			}

			if ( empty( $instance['course_id'] ) ) {
				return new WP_Error( 'no_course', esc_html__( 'Please choose a course!', 'learnpress' ) );
			}

			$course = learn_press_get_course( $instance['course_id'] );
			if ( ! $course ) {
				return new WP_Error( 'no_course', esc_html__( 'Course is invalid', 'learnpress' ) );
			}

			$user = learn_press_get_user( $user_id );

			$course_data = $user->get_course_data( $course->get_id() );
			if ( ! $course_data ) {
				return new WP_Error( 'no_enroll', sprintf( esc_html__( 'You haven\'t started %s', 'learnpress' ), $course->get_title() ) );
			}

			if ( ! $user->has_enrolled_or_finished( $instance['course_id'] ) ) {
				return new WP_Error( 'no_enroll', sprintf( esc_html__( 'You haven\'t started %s', 'learnpress' ), $course->get_title() ) );
			}

			$course_results = $course_data->get_result();

			$instance['css_class'] = $instance['css_class'] ?? '';

			return learn_press_get_template_content(
				'widgets/course-progress',
				compact( 'user', 'course', 'instance', 'course_data', 'course_results' )
			);
		}
	}
}

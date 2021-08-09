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

		public function widget( $args, $instance ) {
			if ( ! learn_press_is_course() ) {
				return;
			}

			wp_enqueue_script( 'lp-widgets' );

			$serialized_instance = serialize( $instance );

			$data = array_merge(
				$this->widget_data_attr,
				array(
					'widget'   => $this->widget_id,
					'instance' => base64_encode( $serialized_instance ),
					'hash'     => wp_hash( $serialized_instance ),
					'courseId' => get_the_ID(),
					'userId'   => get_current_user_id(),
				)
			);

			echo $this->lp_widget_content( $data, $args, $instance );
		}

		public function lp_rest_api_content( $instance, $params ) {
			if ( ! empty( $params['courseId'] ) && ! empty( $params['userId'] ) ) {
				$course = learn_press_get_course( $params['courseId'] );
				$user   = learn_press_get_user( $params['userId'] );

				if ( $course && $user ) {
					return learn_press_get_template_content(
						'widgets/course-progress',
						array(
							'course'   => $course,
							'user'     => $user,
							'instance' => $instance,
						)
					);
				}
			}

			return new WP_Error( 'no_params', esc_html__( 'Error: Data Course progress invalid', 'learnpress' ) );
		}
	}
}

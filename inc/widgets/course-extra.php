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

if ( ! class_exists( 'LP_Widget_Course_Extra' ) ) {
	class LP_Widget_Course_Extra extends LP_Widget {

		public function __construct() {
			$this->widget_cssclass    = 'learnpress widget_course_extra';
			$this->widget_description = esc_html__( 'Display the Extra information in Course settings', 'learnpress' );
			$this->widget_id          = 'learnpress_widget_course_extra';
			$this->widget_name        = esc_html__( 'LearnPress - Course Extra', 'learnpress' );
			$this->settings           = array(
				'type'      => array(
					'label'   => esc_html__( 'Type', 'learnpress' ),
					'type'    => 'select',
					'options' => array(
						'key_features'    => esc_html__( 'Key features', 'learnpress' ),
						'target_audience' => esc_html__( 'Target audience', 'learnpress' ),
						'requirements'    => esc_html__( 'Requirements', 'learnpress' ),
					),
					'std'     => 'key_features',
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

			$classes = array( 'lp_widget_course_extra' );

			if ( ! empty( $instance['css_class'] ) ) {
				$classes[] = $instance['css_class'];
			}

			echo '<div class="' . implode( ' ', $classes ) . '">';

			switch ( $instance['type'] ) {
				case 'key_features':
					LP()->template( 'course' )->course_extra_key_features();
					break;
				case 'target_audience':
					LP()->template( 'course' )->course_extra_target_audiences();
					break;
				case 'requirements':
					LP()->template( 'course' )->course_extra_requirements();
					break;
			}

			echo '</div>';

			$this->widget_end( $args );
		}
	}
}

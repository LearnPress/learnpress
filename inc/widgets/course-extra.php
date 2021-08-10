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
				'title'     => array(
					'label' => esc_html__( 'Title', 'learnpress' ),
					'type'  => 'text',
					'std'   => esc_html__( 'Course Extra', 'learnpress' ),
				),
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
		 * Show widget in frontend.
		 */
		public function lp_rest_api_content( $instance, $params ) {
			if ( empty( $instance['course_id'] ) ) {
				return new WP_Error( 'no_params', esc_html__( 'Error: Please select a Course.', 'learnpress' ) );
			}

			$classes = array( 'lp-widget-course-extra' );

			$instance['css_class'] = $instance['css_class'] ?? '';
			$classes[]             = $instance['css_class'];

			$course_id = absint( $instance['course_id'] );
			$course    = learn_press_get_course( $course_id );

			if ( ! $course ) {
				return new WP_Error( 'no_course', esc_html__( 'No Course found!', 'learnpress' ) );
			}

			ob_start();
			?>

			<div class="<?php echo implode( ' ', $classes ); ?>">
				<h3><?php echo $course->get_title(); ?></h3>

				<div class="lp-widget-course-extra__content">
					<?php
					switch ( $instance['type'] ) {
						case 'key_features':
							LP()->template( 'course' )->course_extra_key_features( $course_id );
							break;
						case 'target_audience':
							LP()->template( 'course' )->course_extra_target_audiences( $course_id );
							break;
						case 'requirements':
							LP()->template( 'course' )->course_extra_requirements( $course_id );
							break;
					}
					?>
				</div>
			</div>

			<?php
			return ob_get_clean();
		}
	}
}

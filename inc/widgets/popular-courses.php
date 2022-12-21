<?php
/**
 * Popular Courses Widget.
 *
 * @author  ThimPress <nhamdv>
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  4.0.0
 * @extends  LP_Widget
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Popular_Courses' ) ) {
	class LP_Widget_Popular_Courses extends LP_Widget {

		public function __construct() {
			$this->widget_cssclass    = 'learnpress widget_course_popular';
			$this->widget_description = esc_html__( 'Display the Popular courses', 'learnpress' );
			$this->widget_id          = 'learnpress_widget_course_popular';
			$this->widget_name        = esc_html__( 'LearnPress - Popular Courses', 'learnpress' );
			$this->settings           = array(
				'title'            => array(
					'label' => __( 'Title', 'learnpress' ),
					'type'  => 'text',
					'std'   => __( 'Popular Courses', 'learnpress' ),
				),
				'show_teacher'     => array(
					'label' => __( 'Show instructor', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'show_thumbnail'   => array(
					'label' => __( 'Show thumbnail', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'limit'            => array(
					'label' => __( 'Limit', 'learnpress' ),
					'type'  => 'number',
					'min'   => 1,
					'std'   => 4,
				),
				'desc_length'      => array(
					'label' => __( 'Description length', 'learnpress' ),
					'id'    => 'desc_length',
					'type'  => 'number',
					'min'   => 0,
					'std'   => 10,
				),
				'show_price'       => array(
					'label' => __( 'Show price', 'learnpress' ),
					'type'  => 'checkbox',
					'std'   => 1,
				),
				'css_class'        => array(
					'label' => __( 'CSS class', 'learnpress' ),
					'type'  => 'text',
					'std'   => '',
				),
				'bottom_link_text' => array(
					'label' => __( 'Go to Courses', 'learnpress' ),
					'type'  => 'text',
					'std'   => 'Go to Courses',
				),
			);

			parent::__construct();
		}

		/**
		 * Show widget in frontend.
		 */
		public function lp_rest_api_content( $instance, $params ) {
			$data         = '';
			$lp_course_db = LP_Course_DB::getInstance();

			try {
				$instance['show_teacher']     = $instance['show_teacher'] ?? 1;
				$instance['show_thumbnail']   = $instance['show_thumbnail'] ?? 1;
				$instance['limit']            = $instance['limit'] ?? 3;
				$instance['desc_length']      = $instance['desc_length'] ?? 10;
				$instance['show_price']       = $instance['show_price'] ?? 1;
				$instance['css_class']        = $instance['css_class'] ?? '';
				$instance['bottom_link_text'] = $instance['bottom_link_text'] ?? esc_html__( 'Go to Courses', 'learnpress' );

				$filter        = new LP_Course_Filter();
				$filter->limit = $instance['limit'];
				$lp_course_db->get_courses_order_by_popular( $filter );
				$courses = $lp_course_db->get_courses( $filter );
				$courses = $lp_course_db->get_values_by_key( $courses, 'ID' );

				$data = learn_press_get_template_content(
					'widgets/popular-courses.php',
					array(
						'courses'  => $courses,
						'instance' => $instance,
					)
				);
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e->getMessage() );
			}

			return $data;
		}
	}

}

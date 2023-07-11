<?php
/**
 * Course Filter Widget.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  4.0.0
 * @extends  LP_Widget
 */

use LearnPress\Helpers\Config;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Course_Filter' ) ) {
	class LP_Widget_Course_Filter extends LP_Widget {

		public function __construct() {
			$this->widget_cssclass    = 'learnpress widget_course_filter';
			$this->widget_description = esc_html__( 'Display the Course Filter', 'learnpress' );
			$this->widget_id          = 'learnpress_widget_course_filter';
			$this->widget_name        = esc_html__( 'LearnPress - Course Filter', 'learnpress' );
			$this->settings           = Config::instance()->get( 'filter-course', 'widgets/course' );

			parent::__construct();
		}

		/**
		 * Show widget in frontend.
		 */
		public function lp_rest_api_content( $instance, $params ) {
			if ( empty( $instance['enable'] ) ) {
				return '';
			}

			$field = $instance['field'];
			unset( $field['order'] );

			$data = array(
				'field'             => $field,
				'search_suggestion' => $instance['search_suggestion'],
			);

			ob_start();
			do_action( 'learn-press/filter-courses/layout', $data );
			return ob_get_clean();

			//          return learn_press_get_template_content(
			//              apply_filters(
			//                  'learn-press/shortcode/course-filter/template',
			//                  'shortcode/course-filter/content.php'
			//              ),
			//              compact( 'data' )
			//          );
		}
	}
}

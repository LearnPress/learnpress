<?php
/**
 * Course Filter Widget.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  1.0.0
 * @since 4.2.3.2
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
			$data = array_merge(
				[ 'params_url' => $params['params_url'] ?? lp_archive_skeleton_get_args() ],
				$instance
			);

			ob_start();
			do_action( 'learn-press/filter-courses/layout', $data );
			return ob_get_clean();
		}
	}
}

<?php
/**
 * Register Form Shortcode.
 *
 * @author   ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  3.0.0
 * @extends  LP_Abstract_Shortcode
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcode_Course_Curriculum' ) ) {

	/**
	 * Class LP_Shortcode_Course_Curriculum
	 */
	class LP_Shortcode_Course_Curriculum extends LP_Abstract_Shortcode {
		/**
		 * LP_Shortcode_Course_Curriculum constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
		}

		/**
		 * Shortcode content.
		 *
		 * @return string
		 */
		public function output() {

			ob_start();
			global $post;
			$post = get_post( $this->_atts['id'] );

			require_once realpath( LP_PLUGIN_PATH . '/inc/course/class-model-user-can-view-course-item.php' );

			if ( ! $post || ( LP_COURSE_CPT !== get_post_type( $post->ID ) ) ) {
				learn_press_display_message( __( 'Invalid course.', 'learnpress' ), 'error' );
			} else {
				setup_postdata( $post );
				learn_press_get_template( 'single-course/tabs/curriculum.php' );
				wp_reset_postdata();
			}

			$output = ob_get_clean();

			return $output;
		}

	}
}

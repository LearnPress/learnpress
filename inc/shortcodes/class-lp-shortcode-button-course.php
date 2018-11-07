<?php
/**
 * Button Course Shortcode.
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

if ( ! class_exists( 'LP_Shortcode_Button_Course' ) ) {

	/**
	 * Class LP_Shortcode_Button_Course
	 *
	 * @since 3.0.0
	 */
	class LP_Shortcode_Button_Course extends LP_Abstract_Shortcode {

		/**
		 * LP_Shortcode_Button_Course constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
			$this->_atts = shortcode_atts(
				array(
					'id'            => 0,
					'enroll_text'   => '',
					'purchase_text' => '',
				),
				$this->_atts
			);
		}

		/**
		 * Output form.
		 *
		 * @return string
		 */
		public function output() {
			ob_start();

			$atts = $this->_atts;

			if ( '@current' === $atts['id'] ) {
				$course_id = learn_press_is_course() ? get_the_ID() : 0;
			} else {
				$course_id = $atts['id'];
			}

			if ( $course_id && $course = learn_press_get_course( $course_id ) ) {
				LP_Global::set_course( $course );
				global $post;
				$post = get_post( $course_id );

				setup_postdata( $post );
				add_filter( 'learn-press/enroll-course-button-text', array( $this, 'enroll_button_text' ) );
				add_filter( 'learn-press/purchase-course-button-text', array( $this, 'purchase_button_text' ) );

				learn_press_course_enroll_button();
				learn_press_course_purchase_button();

				remove_filter( 'learn-press/purchase-course-button-text', array( $this, 'purchase_button_text' ) );
				remove_filter( 'learn-press/enroll-course-button-text', array( $this, 'enroll_button_text' ) );
				wp_reset_postdata();
				LP_Global::reset();
			}

			return ob_get_clean();
		}

		/**
		 * @param string $text
		 *
		 * @return string
		 */
		public function purchase_button_text( $text ) {
			if ( $this->_atts['purchase_text'] ) {
				$text = $this->_atts['purchase_text'];
			}

			return $text;
		}

		/**
		 * @param string $text
		 *
		 * @return string
		 */
		public function enroll_button_text( $text ) {
			if ( $this->_atts['enroll_text'] ) {
				$text = $this->_atts['enroll_text'];
			}

			return $text;
		}
	}
}
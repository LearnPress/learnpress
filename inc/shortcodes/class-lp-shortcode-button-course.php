<?php
/**
 * Button Course Shortcode.
 *
 * @author   ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  3.0.1
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
					'btn_label'     => '',
				),
				$this->_atts
			);
		}

		/**
		 * Output button course.
		 *
		 * @return string
		 */
		public function output() {
			ob_start();

			$atts = $this->_atts;

			if ( 'current' === $atts['id'] ) {
				$course_id = learn_press_is_course() ? get_the_ID() : 0;
			} else {
				$course_id = $atts['id'];
			}

			try {
				$course = learn_press_get_course( $course_id );
				if ( ! $course ) {
					return '';
				}

				// Load js button course.
				wp_enqueue_script( 'lp-single-course' );

				if ( $course->is_free() ) {
					add_filter( 'learn-press/enroll-course-button-text', array( $this, 'button_text_enroll' ) );
				} elseif ( $course->get_external_link() ) {
					add_filter( 'learn-press/course-external-link-text', array( $this, 'button_text_external_link' ) );
				} elseif ( $course->is_no_required_enroll() ) {

				} else {
					add_filter( 'learn-press/purchase-course-button-text', array( $this, 'button_text_purchase' ) );
				}

				do_action( 'learn-press/course-buttons', $course );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}

			return ob_get_clean();
		}

		/**
		 * Label button purchase.
		 *
		 * @param string $text
		 *
		 * @return string
		 */
		public function button_text_purchase( string $text ): string {
			if ( $this->_atts['purchase_text'] ) {
				$text = $this->_atts['purchase_text'];
			} elseif ( $this->_atts['btn_label'] ) {
				$text = $this->_atts['btn_label'];
			}

			return $text;
		}

		/**
		 * Label button enroll.
		 *
		 * @param string $text
		 *
		 * @return string
		 */
		public function button_text_enroll( string $text ): string {
			if ( $this->_atts['enroll_text'] ) {
				$text = $this->_atts['enroll_text'];
			} elseif ( $this->_atts['btn_label'] ) {
				$text = $this->_atts['btn_label'];
			}

			return $text;
		}
	}
}

new LP_Shortcode_Button_Course();

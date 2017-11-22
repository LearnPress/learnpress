<?php
/**
 * Button Purchase Shortcode.
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

if ( ! class_exists( 'LP_Shortcode_Button_Purchase' ) ) {

	/**
	 * Class LP_Shortcode_Button_Purchase
	 *
	 * @since 3.0.0
	 */
	class LP_Shortcode_Button_Purchase extends LP_Abstract_Shortcode {

		/**
		 * LP_Shortcode_Button_Purchase constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
			$this->_atts = shortcode_atts(
				array(
					'id'   => 0,
					'text' => ''
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

			if ( $course_id && ( $course = learn_press_get_course( $course_id ) ) ) {
				LP_Global::set_course( $course );
				global $post;
				$post = get_post( $course_id );

				setup_postdata( $post );
				add_filter( 'learn-press/purchase-course-button-text', array( $this, 'button_text' ) );

				learn_press_get_template( 'single-course/buttons/purchase.php', array( 'course' => $course ) );

				remove_filter( 'learn-press/purchase-course-button-text', array( $this, 'button_text' ) );
				wp_reset_postdata();
				LP_Global::reset();
			} else {

			}

			return ob_get_clean();
		}

		/**
		 * @param string $text
		 *
		 * @return string
		 */
		public function button_text( $text ) {
			if ( $this->_atts['text'] ) {
				$text = $this->_atts['text'];
			}

			return $text;
		}
	}
}
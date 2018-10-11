<?php
/**
 * Popular Courses Shortcode.
 *
 * @author  ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  3.0.0
 * @extends  LP_Abstract_Shortcode_Courses
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcode_Popular_Courses' ) ) {

	/**
	 * Class LP_Shortcode_Popular_Courses
	 */
	class LP_Shortcode_Popular_Courses extends LP_Abstract_Shortcode_Courses {
		/**
		 * LP_Popular_Courses_Shortcode constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
		}

		/**
		 * Query courses.
		 *
		 * @since 3.0.0
		 */
		public function query_courses() {
			$this->_query = $this->curd->get_popular_courses( array(
				'limit'    => $this->_atts['limit'],
				'order_by' => $this->_atts['order_by'],
				'order'    => $this->_atts['order']
			) );
		}
	}
}
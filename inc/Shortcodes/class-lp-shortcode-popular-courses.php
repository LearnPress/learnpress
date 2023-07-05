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
			$lp_course_db = LP_Course_DB::getInstance();

			try {
				$filter        = new LP_Course_Filter();
				$filter->limit = $this->_atts['limit'] ?? $filter->limit;
				$lp_course_db->get_courses_order_by_popular( $filter );
				$courses      = $lp_course_db->get_courses( $filter );
				$this->_query = $lp_course_db->get_values_by_key( $courses, 'ID' );
			} catch ( Throwable $e ) {

			}

		}
	}
}

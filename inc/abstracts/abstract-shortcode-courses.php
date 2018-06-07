<?php
/**
 * Abstract Shortcode Courses.
 *
 * @author  ThimPress
 * @category Abstract
 * @package  Learnpress/Classes
 * @version  3.0.0
 * @extends  LP_Abstract_Shortcode
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Abstract_Shortcode_Courses' ) ) {

	/**
	 * Class LP_Abstract_Shortcode
	 *
	 * Abstract class for shortcodes
	 *
	 * @since 3.0.0
	 */
	abstract class LP_Abstract_Shortcode_Courses extends LP_Abstract_Shortcode {

		/**
		 * @var null
		 */
		protected $curd = null;

		/**
		 * @var null
		 */
		protected $courses = null;

		/**
		 * @var WP_Query
		 */
		protected $_query = null;

		/**
		 * LP_Abstract_Shortcode_Courses constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );

			// course curd
			$this->curd = new LP_Course_CURD();

			// shortcode atts
			$this->_atts = wp_parse_args( $this->_atts, $this->get_atts() );
		}

		/**
		 * Query course
		 *
		 * @return mixed
		 */
		abstract function query_courses();

		/**
		 * Get shortcode atts.
		 *
		 * @return array
		 */
		public function get_atts() {
			$atts = parent::get_atts();

			$atts = wp_parse_args( $atts, array(
				'limit'    => 1,
				'order_by' => 'post_date',
				'order'    => 'DESC'
			) );

			$limit    = $atts['limit'];
			$order_by = $atts['order_by'];
			$order    = $atts['order'];

			// valid atts
			if ( ! absint( $limit ) ) {
				$limit = 10;
			}

			$arr_orders_by = array( 'post_date', 'post_title', 'post_status', 'comment_count' );
			if ( ! in_array( $order_by, $arr_orders_by ) || ! in_array( 'post_' . $order_by, $arr_orders_by ) ) {
				$order_by = 'post_date';
			} else {
				if ( $order_by !== 'comment_count' ) {
					$order_by = 'post_' . $order_by;
				}
			}

			$arr_orders    = array( 'DESC', 'ASC' );
			$order         = strtoupper( $order );
			if ( ! in_array( $order, $arr_orders ) ) {
				$order = 'DESC';
			}

			return array( 'limit' => $limit, 'order_by' => $order_by, 'order' => $order );
		}

		/**
		 * Output shortcode.
		 */
		public function output() {
			ob_start();
			$this->query_courses();
			$this->output_courses();

			return ob_get_clean();
		}

		/**
		 * Loop course.
		 */
		public function output_courses() {

			global $wpdb;

			$post_ids = $wpdb->get_col( $this->_query );
			$query    = new LP_Query_Course( array( 'post__in' => $post_ids ) );

			if ( $query->have_posts() ) {
				do_action( 'learn_press_before_courses_loop' );

				learn_press_begin_courses_loop();

				while ( $query->have_posts() ) : $query->the_post();
					learn_press_get_template_part( 'content', 'course' );
				endwhile;

				learn_press_end_courses_loop();

				do_action( 'learn_press_after_courses_loop' );

				wp_reset_postdata();
			} else {
				learn_press_display_message( __( 'No course found.', 'learnpress' ), 'error' );
			}

		}
	}
}
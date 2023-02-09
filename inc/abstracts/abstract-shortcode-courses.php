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
		 * @editor tungnx
		 */
		public function get_atts() {
			$atts = parent::get_atts();

			$atts = wp_parse_args(
				$atts,
				array(
					'limit'    => 5,
					'order_by' => 'post_date',
					'order'    => 'DESC',
				)
			);

			$order_by = $atts['order_by'];
			$order    = $atts['order'];

			$arr_orders_by = array( 'post_date', 'post_title', 'post_status', 'comment_count' );
			if ( ! in_array( $order_by, $arr_orders_by ) || ! in_array( 'post_' . $order_by, $arr_orders_by ) ) {
				$atts['order_by'] = 'post_date';
			} else {
				if ( $order_by !== 'comment_count' ) {
					$atts['order_by'] = 'post_' . $order_by;
				}
			}

			$arr_orders = array( 'DESC', 'ASC' );
			$order      = strtoupper( $order );

			if ( ! in_array( $order, $arr_orders ) ) {
				$atts['order'] = 'DESC';
			}

			return $atts;
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
		 * @editor tungnx
		 */
		public function output_courses() {
			$attrs = $this->get_atts();

			wp_enqueue_style( 'learnpress' );
			//wp_enqueue_script( 'lp-courses' );

			$post_ids = $this->_query;

			if ( ! $post_ids ) {
				$post_ids = array();
			}

			$query = new LP_Query_Course( array( 'post__in' => $post_ids ) );
			$args  = $query->get_wp_query_vars();

			$query = new WP_Query( $args );

			$args_template = array( 'query' => $query );

			if ( ! empty( $attrs['title'] ) ) {
				$args_template['title'] = $attrs['title'];
			}

			learn_press_get_template( 'shortcode/list-courses', $args_template );
		}
	}
}

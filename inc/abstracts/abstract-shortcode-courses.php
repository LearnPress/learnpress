<?php

/**
 * Class LP_Abstract_Shortcode
 *
 * Abstract class for shortcodes
 *
 * @since 3.x.x
 */
abstract class LP_Abstract_Shortcode_Courses extends LP_Abstract_Shortcode {

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

		$this->_atts = wp_parse_args(
			$this->_atts,
			array(
				'limit'    => 10,
				'order_by' => 'date',
				'order'    => 'DESC'
			)
		);
	}

	abstract function query_courses();

	/**
	 * Get query.
	 *
	 * @return LP_Query_Course|WP_Query
	 */
	public function get_courses() {
		return $this->_query;
	}

	public function get_atts() {
		$atts = parent::get_atts();

		$limit    = $atts['limit'];
		$order_by = $atts['order_by'];
		$order    = $atts['order'];


		// Validation date
		$arr_orders_by = array( 'post_date', 'post_title', 'post_status', 'comment_count' );
		$arr_orders    = array( 'DESC', 'ASC' );
		$order         = strtoupper( $order );

		if ( ! in_array( $order_by, $arr_orders_by ) || ! in_array( 'post_' . $order_by, $arr_orders_by ) ) {
			$order_by = 'post_date';
		} else {
			if ( $order_by !== 'comment_count' ) {
				$order_by = 'post_' . $order_by;
			}
		}

		if ( ! in_array( $order, $arr_orders ) ) {
			$order = 'DESC';
		}
		if ( ! absint( $limit ) ) {
			$limit = 10;
		}

		return array(
			'limit'    => $limit,
			'order_by' => $order_by,
			'order'    => $order
		);
	}

	/**
	 * Output content
	 */
	public function output() {
		ob_start();
		$this->query_courses();
		$this->output_courses();

		return ob_get_clean();
	}

	public function output_courses() {
		if ( $this->_query->have_posts() ) :
			global $post;
			do_action( 'learn_press_before_courses_loop' );

			learn_press_begin_courses_loop();

			while ( $this->_query->have_posts() ) : $this->_query->the_post();

				learn_press_get_template_part( 'content', 'course' );

			endwhile;

			learn_press_end_courses_loop();

			do_action( 'learn_press_after_courses_loop' );

			wp_reset_postdata();
		else:
			learn_press_display_message( __( 'No course found.', 'learnpress' ), 'error' );
		endif;
	}

	/**
	 * Output courses
	 */
	public function _output_courses() {
		global $post;
		if ( $this->_posts->have_posts() ) {

			do_action( 'learn_press_before_courses_loop' );

			learn_press_begin_courses_loop();

			while ( $this->_posts->have_posts() ) {
				$this->_posts->the_post();
				setup_postdata( $post );
				learn_press_get_template_part( 'content', 'course' );
			}

			learn_press_end_courses_loop();
			wp_reset_postdata();

		} else {
			learn_press_display_message( __( 'No course found.', 'learnpress' ), 'error' );
		}
	}
}
<?php

/**
 * Class LP_Cart
 *
 * Simple Cart object for now. Maybe need to expand later
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Cart {

	/**
	 * Unique instance of LP_Cart
	 *
	 * @var LP_Cart object
	 */
	private static $instance = false;

	private $_cart_content = array();

	/**
	 * Constructor
	 */
	function __construct() {
		if ( self::$instance ) return;
		if ( !$this->_cart_content = LP_Session::get( 'cart' ) ) {
			$this->_cart_content = array(
				'items'     => array(),
				'sub_total' => 0,
				'total'     => 0
			);
		}
		LP_Request_Handler::register( 'add-course-to-cart', array( $this, 'add_to_cart' ), 20 );
		add_action( 'learn_press_add_to_cart', array( $this, 'calculate_totals' ), 10 );

	}

	/**
	 * Add course to cart
	 *
	 * @param int $course_id
	 * @param int $quantity
	 * @param     array
	 */
	function add_to_cart( $course_id, $quantity = 1, $item_data = array() ) {
		$course = learn_press_get_course( $course_id );

		if ( !empty( $this->_cart_content['items'][$course_id] ) ) {
			$quantity += $this->_cart_content['items'][$course_id]['quantity'];
		}

		$this->_cart_content['items'][$course_id] = apply_filters( 'learn_press_add_cart_item', array(
				'item_id'  => $course_id,
				'quantity' => $quantity,
				'data'     => $item_data
			)
		);
		do_action( 'learn_press_add_to_cart', $course_id, $quantity, $item_data, $this );

		if ( learn_press_is_enable_cart() ) {
			wp_redirect( get_the_permalink( $course_id ) );
		} else {
			wp_redirect( learn_press_get_page_link( 'checkout' ) );
		}
		die();
	}

	function calculate_totals() {
		if ( $items = $this->_cart_content['items'] ) {
			$sub_total = 0;
			foreach ( $items as $item_id => $item ) {
				$course = learn_press_get_course( $item_id );
				if ( $course ) {
					$sub_total += $course->get_price() * $item['quantity'];
				}
			}
			$this->_cart_content['sub_total'] = $sub_total;
			$this->_cart_content['total']     = $sub_total;
		}
		$this->update_session();
	}

	function update_session() {
		LP_Session::set( 'cart', $this->_cart_content );
	}

	/**
	 * Get cart id
	 *
	 * @return mixed
	 */
	function get_cart_id() {
		return $_SESSION['learn_press_cart']['cart_id'];
	}

	/**
	 * Get all items from cart
	 *
	 * @deprecated
	 * @return mixed
	 */
	function get_products() {
		_deprecated_function( __CLASS__ . '->' . __FUNCTION__, '1.0', __CLASS__ . '->get_items' );
		return $this->get_items();
	}

	/**
	 * Get all items from cart
	 *
	 * @return mixed
	 */
	function get_items() {
		if( ! did_action( 'learn_press_get_cart_from_session' ) ){
			$this->get_cart_from_session();
		}
		return $this->_cart_content['items'];
	}

	function get_cart_from_session(){
		$this->_cart_content = LP_Session::get( 'cart' );
		do_action( 'learn_press_get_cart_from_session' );
	}
	/**
	 * Get cart sub-total
	 *
	 * @return mixed
	 */
	function get_subtotal() {

		$sub_total = learn_press_format_price( $this->_cart_content['sub_total'], true );
		return apply_filters( 'learn_press_get_cart_subtotal', $sub_total, $this->get_cart_id() );
	}

	/**
	 * Get cart total
	 *
	 * @return mixed
	 */
	function get_total() {
		$sub_total = $this->get_subtotal();
		$total     = $sub_total;
		return apply_filters( 'learn_press_get_cart_total', $total, $this->get_cart_id() );
	}

	/**
	 * Generate unique cart id
	 *
	 * @return string
	 */
	function generate_cart_id() {
		return md5( time() );
	}

	function get_item_subtotal( $course, $quantity = 1 ) {
		$price           = $course->get_price();
		$row_price       = $price * $quantity;
		$course_subtotal = learn_press_format_price( $row_price, true );
		return apply_filters( 'learn_press_cart_item_subtotal', $course_subtotal, $course, $quantity, $this );
	}

	/**
	 * Clean all items from cart
	 *
	 * @return $this
	 */
	function empty_cart() {
		unset( $_SESSION['learn_press_cart']['products'] );
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function get_checkout_url() {
		$checkout_url = learn_press_get_page_link( 'checkout' );
		return apply_filters( 'learn_press_get_checkout_url', $checkout_url );
	}

	function needs_payment(){
		// TODO: check if need payment
		return true;
	}

	/**
	 * Destroy cart instance
	 */
	function destroy() {
		unset( $_SESSION['learn_press_cart'] );
	}

	/**
	 * Get unique instance of LP_Cart object
	 *
	 * @param bool|false $prop
	 * @param bool|false $args
	 *
	 * @return LP_Cart|mixed
	 */
	static function instance( $prop = false, $args = false ) {
		if ( !self::$instance ) {
			self::$instance = new self();
		}
		$ins = self::$instance;
		if ( $prop ) {
			$prop = 'get_' . $prop;
		}
		return $prop && is_callable( array( $ins, $prop ) ) ? call_user_func_array( array( $ins, $prop ), (array) $args ) : $ins;
	}
}

if ( !is_admin() ) {
	$GLOBALS['learn_press_cart'] = LP_Cart::instance();
}

function learn_press_get_cart( $prop = null ) {
	return LP_Cart::instance( $prop );
}

function learn_press_get_cart_description() {
	$products    = learn_press_get_cart( 'products' );
	$description = '';
	if ( $products ) {
		foreach ( $products as $prop ) {
			$description .= get_the_title( $prop['id'] );
		}
	}
	return apply_filters( 'learn_press_cart_description', $description );
}

function learn_press_get_cart_course_url() {
	$products = learn_press_get_cart( 'products' );
	$return   = '';
	if ( $products ) {
		foreach ( $products as $prop ) {
			$return = get_permalink( $prop['id'] );
			break;
		}
	}
	return apply_filters( 'learn_press_cart_course_url', $return );
}

function learn_press_get_cart_total() {
	return learn_press_get_cart( 'total' );
}

function learn_press_is_enable_cart() {
	return LP()->settings->get( 'enable_cart' ) == 'yes';
}
//learn_press_get_cart_description();
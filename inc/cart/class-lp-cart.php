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
		//if ( self::$instance ) return;
		if ( !is_array( $this->_cart_content = LP_Session::get( 'cart' ) ) ) {
			$this->_cart_content = $this->get_default_cart_content();
		}

		LP_Request_Handler::register( 'add-course-to-cart', array( $this, 'add_to_cart' ), 20 );
		add_action( 'learn_press_add_to_cart', array( $this, 'calculate_totals' ), 10 );
	}

	function __get( $key ) {
		$return = null;
		if ( !isset( $this->{$key} ) ) {
			switch ( $key ) {
				case 'subtotal':
					$this->subtotal = $this->_cart_content['subtotal'];
					break;
				case 'total':
					$this->total = $this->_cart_content['total'];
			}
		}
		if ( isset( $this->{$key} ) ) {
			$return = $this->{$key};
		}
		return $return;
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
				'subtotal' => 0,
				'total'    => 0,
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
			$subtotal = 0;
			foreach ( $items as $item_id => $item ) {
				$course = learn_press_get_course( $item_id );
				if ( $course ) {
					$subtotal += $course->get_price() * $item['quantity'];
				}
				$this->_cart_content['items'][$item_id]['subtotal'] = $subtotal;
				$this->_cart_content['items'][$item_id]['total']    = $subtotal;
			}
			$this->_cart_content['subtotal'] = $subtotal;
			$this->_cart_content['total']    = $subtotal;
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
		if ( !did_action( 'learn_press_get_cart_from_session' ) ) {
			$this->get_cart_from_session();
		}
		return $this->_cart_content['items'];
	}

	function get_cart_from_session() {
		$this->_cart_content = LP_Session::get( 'cart' );
		do_action( 'learn_press_get_cart_from_session' );
	}

	/**
	 * Get cart sub-total
	 *
	 * @return mixed
	 */
	function get_subtotal() {

		$subtotal = learn_press_format_price( $this->_cart_content['subtotal'], true );
		return apply_filters( 'learn_press_get_cart_subtotal', $subtotal, $this->get_cart_id() );
	}

	/**
	 * Get cart total
	 *
	 * @return mixed
	 */
	function get_total() {
		$subtotal = $this->get_subtotal();
		$total    = $subtotal;
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
		LP_Session::set( 'cart', $this->get_default_cart_content() );
		return $this;
	}

	function is_empty() {
		return !$this->_cart_content['items'];
	}

	function get_cart_content() {
		return $this->_cart_content;
	}

	function get_default_cart_content() {
		return apply_filters( 'learn_press_default_cart_content', array(
				'items'    => array(),
				'subtotal' => 0,
				'total'    => 0
			)
		);
	}

	/**
	 * @return mixed
	 */
	public function get_checkout_url() {
		$checkout_url = learn_press_get_page_link( 'checkout' );
		return apply_filters( 'learn_press_get_checkout_url', $checkout_url );
	}

	function needs_payment() {
		// TODO: check if need payment
		return true;
	}

	/**
	 * Destroy cart instance
	 */
	function destroy() {

	}

	/**
	 * Get unique instance of LP_Cart object
	 *
	 * @return LP_Cart|mixed
	 */
	static function instance() {
		if ( !self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}
}

/**
 * Return LP_Cart object instance
 *
 * @return mixed
 */
function learn_press_get_cart() {
	return LP()->cart;
}

/**
 * Get description for cart by join all item titles into one
 *
 * @return string
 */
function learn_press_get_cart_description() {
	$items       = LP()->cart->get_items();
	$description = array();
	if ( $items ) {
		foreach ( $items as $item ) {
			$description[] = apply_filters( 'learn_press_cart_item_description', get_the_title( $item['item_id'] ) );
		}
	}
	return apply_filters( 'learn_press_cart_description', join( ', ', $description ) );
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

/**
 * Return total of cart
 *
 * @return mixed
 */
function learn_press_get_cart_total() {
	return LP()->cart->total;
}

/**
 * Return true if enable cart
 *
 * @return bool
 */
function learn_press_is_enable_cart() {
	return LP()->settings->get( 'enable_cart' ) == 'yes';
}
//learn_press_get_cart_description();
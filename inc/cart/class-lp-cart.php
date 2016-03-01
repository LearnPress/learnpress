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

defined( 'ABSPATH' ) || exit();

class LP_Cart {

	/**
	 * Unique instance of LP_Cart
	 *
	 * @var LP_Cart object
	 */
	private static $instance = false;

	/**
	 * Hold the content of the cart
	 *
	 * @var array
	 */
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
		LP_Request_Handler::register( 'remove-cart-item', array( $this, 'remove_item' ), 20 );
		add_action( 'learn_press_add_to_cart', array( $this, 'calculate_totals' ), 10 );
	}

	function __get( $key ) {
		$return = null;
		if ( !isset( $this->{$key} ) ) {
			switch ( $key ) {
				case 'subtotal':
					$this->subtotal = floatval( $this->_cart_content['subtotal'] );
					break;
				case 'total':
					$this->total = floatval( $this->_cart_content['total'] );
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

		if ( !learn_press_is_enable_cart() ) {
			$this->empty_cart();
		}
		$course = learn_press_get_course( $course_id );

		/*
		if ( !empty( $this->_cart_content['items'][$course_id] ) ) {
			$quantity += $this->_cart_content['items'][$course_id]['quantity'];
		}*/

		$quantity = 1;

		$this->_cart_content['items'][$course_id] = apply_filters( 'learn_press_add_cart_item', array(
				'item_id'  => $course_id,
				'quantity' => $quantity,
				'subtotal' => $course->get_price() * $quantity,
				'total'    => $course->get_price() * $quantity,
				'data'     => $item_data
			)
		);
		do_action( 'learn_press_add_to_cart', $course_id, $quantity, $item_data, $this );
		$button = '';


		if ( learn_press_is_enable_cart() && LP()->settings->get( 'no_checkout_free_course' ) != 'yes' ) {
			if ( LP()->settings->get( 'redirect_after_add' ) == 'yes' ) {
				$redirect = learn_press_get_page_link( 'cart' );
				if ( !$redirect ) {
					learn_press_add_notice( sprintf( __( 'Cart page is not setting up.', 'learnpress' ) ) );
					$redirect = add_query_arg( '', '' );
				} else {
					$button = sprintf( '<a href="%s">%s</a>', get_the_permalink( $course_id ), __( 'Back to class', 'learnpress' ) );
				}
			} else {
				$redirect = get_the_permalink( $course_id );
				if ( !learn_press_get_page_link( 'cart' ) ) {
					learn_press_add_notice( sprintf( __( 'Checkout page is not setting up.', 'learnpress' ) ) );
				} else {
					$button = sprintf( '<a href="%s">%s</a>', learn_press_get_page_link( 'cart' ), __( 'View cart', 'learnpress' ) );
				}
			}
		} else {
			if( LP()->settings->get( 'no_checkout_free_course' ) == 'yes' && $this->total == 0 ){
				$checkout_results = LP_Checkout::instance()->process_checkout();
				if ( is_ajax() ) {
					learn_press_send_json(
						$checkout_results
					);
				} else {
					wp_redirect( $checkout_results['redirect'] );
					die();
				}
			}else {
				$redirect = learn_press_get_page_link( 'checkout' );
				if ( !$redirect ) {
					learn_press_add_notice( sprintf( __( 'Checkout page is not setting up.', 'learnpress' ) ) );
					$redirect = add_query_arg( '', '' );
				}
			}
		}
		$redirect = apply_filters( 'learn_press_add_to_cart_redirect', $redirect, $course_id );
		learn_press_add_notice( sprintf( __( '<strong>%s</strong> has been added to your cart. %s', 'learnpress' ), get_the_title( $course_id ), $button ) );

		if ( is_ajax() ) {
			learn_press_send_json(
				array(
					'redirect' => $redirect,
					'result'   => 'success',
					'messages' => learn_press_get_notices( true )
				)
			);
		} else {
			wp_redirect( $redirect );
			die();
		}
	}

	function has_item( $item_id ) {
		return isset( $this->_cart_content['items'][$item_id] );
	}

	function remove_item( $item_id ) {
		if ( isset( $this->_cart_content['items'][$item_id] ) ) {

			do_action( 'learn_press_remove_cart_item', $item_id, $this );

			unset( $this->_cart_content['items'][$item_id] );

			do_action( 'learn_press_cart_item_removed', $item_id, $this );

			$this->calculate_totals();

			return true;
		}

		return false;
	}

	function calculate_totals() {
		if ( $items = $this->_cart_content['items'] ) {
			$subtotal = 0;
			foreach ( $items as $item_id => $item ) {
				$course = learn_press_get_course( $item_id );
				if ( $course ) {
					$_subtotal = $course->get_price() * $item['quantity'];
				}
				$this->_cart_content['items'][$item_id]['subtotal'] = $_subtotal;
				$this->_cart_content['items'][$item_id]['total']    = $_subtotal;
				$subtotal += $_subtotal;
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
		return !empty( $_SESSION['learn_press_cart']['cart_id'] ) ? $_SESSION['learn_press_cart']['cart_id'] : 0;
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

		do_action( 'learn_press_before_empty_cart' );

		LP_Session::set( 'cart', $this->get_default_cart_content() );
		$this->get_cart_from_session();

		do_action( 'learn_press_emptied_cart' );

		return $this;
	}

	/**
	 * Check if cart is empty or not
	 *
	 * @return bool
	 */
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
		return apply_filters( 'learn_press_cart_needs_payment', $this->total > 0, $this );
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

function learn_press_clear_cart_after_payment() {
	global $wp;

	if ( !empty( $wp->query_vars['lp-order-received'] ) ) {

		$order_id  = absint( $wp->query_vars['lp-order-received'] );
		$order_key = isset( $_GET['key'] ) ? $_GET['key'] : '';

		if ( $order_id > 0 && ( $order = learn_press_get_order( $order_id ) ) ) {
			if ( $order->order_key === $order_key ) {
				LP()->cart->empty_cart();
			}
		}
	}

	if ( LP()->session->order_awaiting_payment > 0 ) {
		$order = learn_press_get_order( LP()->session->order_awaiting_payment );

		if ( $order && $order->id > 0 ) {
			if ( !$order->has_status( array( 'failed', 'pending', 'cancelled' ) ) ) {
				LP()->cart->empty_cart();
				LP()->session->order_awaiting_payment = null;
			}
		}
	}
}

add_action( 'get_header', 'learn_press_clear_cart_after_payment' );

//learn_press_get_cart_description();
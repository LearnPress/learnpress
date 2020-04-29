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
	private static $instances = array();

	/**
	 * Hold the content of the cart
	 *
	 * @var array
	 */
	protected $_cart_content = array();

	/**
	 * Key of cart content stored in session
	 *
	 * @var string
	 */
	protected $_cart_session_key = 'cart';

	/**
	 * Cart total
	 *
	 * @var int
	 */
	public $total = 0;

	/**
	 * Cart subtotal
	 *
	 * @var int
	 */
	public $subtotal = 0;

	/**
	 * Constructor
	 */
	public function __construct() {

		LP_Request_Handler::register( 'add-course-to-cart', array( $this, 'add_to_cart' ), 20 );
		LP_Request_Handler::register( 'remove-cart-item', array( $this, 'remove_item' ), 20 );

		add_action( 'learn-press/add-to-cart', array( $this, 'calculate_totals' ), 10 );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 ); // Set cookies
		add_action( 'shutdown', array(
			$this,
			'maybe_set_cart_cookies'
		), 0 ); // Set cookies before shutdown and ob flushing
		add_action( 'wp_loaded', array( $this, 'init' ) );
	}

	/**
	 * Init cart.
	 * Get data from session and put to cart content.
	 */
	function init() {
		$this->get_cart_from_session();
	}

	public function maybe_set_cart_cookies() {

		if ( ! headers_sent()/* && did_action( 'wp_loaded' )*/ ) {
			//$this->set_cart_cookies( ! $this->is_empty() );
		}
	}

	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			learn_press_setcookie( 'wordpress_lp_cart', 1 );
		} elseif ( isset( $_COOKIE['wordpress_lp_cart'] ) ) {
			learn_press_setcookie( 'wordpress_lp_cart', 0, time() - HOUR_IN_SECONDS );
		}
		do_action( 'learn_press_set_cart_cookies', $set );
	}

	/**
	 * Get data from session
	 *
	 * @return array
	 */
	public function get_cart_for_session() {
		$cart_session = array();

		if ( $this->get_cart() ) {
			foreach ( $this->get_cart() as $key => $values ) {
				$cart_session[ $key ] = $values;
				unset( $cart_session[ $key ]['data'] ); // Unset product object
			}
		}

		return $cart_session;
	}

	/**
	 * Get cart content.
	 *
	 * @return array
	 */
	public function get_cart() {
		if ( ! did_action( 'wp_loaded' ) ) {
			learn_press_debug( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) );
			_doing_it_wrong( __FUNCTION__, __( 'Get cart should not be called before the wp_loaded action.', 'learnpress' ), '2.3' );
		}

		if ( ! did_action( 'learn_press_cart_loaded_from_session' ) ) {
			$this->get_cart_from_session();
		}

		return array_filter( (array) $this->_cart_content );
	}

	/**
	 * Add course to cart.
	 *
	 * @param int   $course_id
	 * @param int   $quantity
	 * @param array $item_data
	 *
	 * @return mixed
	 */
	public function add_to_cart( $course_id, $quantity = 1, $item_data = array() ) {
		try {
			$course = learn_press_get_course( $course_id );

			// Check if course can be purchased
			if ( ! $course->is_purchasable() ) {
				throw new Exception( __( 'Sorry! This course is not purchasable.', 'learnpress' ) );
			}

			// Check if course is in stock
			if ( ! $course->is_in_stock() ) {
				throw new Exception( __( 'Sorry! The number of enrolled students has reached limit', 'learnpress' ) );
			}

			$item_data = apply_filters( 'learn-press/cart-item-data', sanitize_post( $item_data, 'raw' ), $course_id );

			$cart_id = $this->generate_cart_id( $course_id, $item_data );

			$this->_cart_content[ $cart_id ] = apply_filters( 'learn_press_add_cart_item',
				array_merge(
					$item_data,
					array(
						'item_id'  => $course_id,
						'quantity' => $quantity,
						'data'     => $course
					)
				)
			);

			if ( did_action( 'wp' ) ) {
				$this->set_cart_cookies( true );
			}

			// @deprecated
			do_action( 'learn_press_add_to_cart', $course_id, $quantity, $item_data, $this );

			/**
			 * @see LP_Cart::calculate_totals()
			 */
			do_action( 'learn-press/add-to-cart', $course_id, $quantity, $item_data, $cart_id );

			return $cart_id;
		}
		catch ( Exception $e ) {
			if ( $message = $e->getMessage() ) {
				learn_press_add_message( $e->getMessage(), 'error' );
			}

			return false;
		}
	}

	/**
	 * Remove an item from cart
	 *
	 * @param $item_id
	 *
	 * @return bool
	 */
	public function remove_item( $item_id ) {
		if ( isset( $this->_cart_content['items'][ $item_id ] ) ) {

			do_action( 'learn_press_remove_cart_item', $item_id, $this );

			unset( $this->_cart_content['items'][ $item_id ] );

			do_action( 'learn_press_cart_item_removed', $item_id, $this );

			$this->calculate_totals();

			return true;
		}

		return false;
	}

	/**
	 * Re-calculate cart totals and update data to session
	 */
	public function calculate_totals() {

		$this->total = $this->subtotal = 0;

		if ( $items = $this->get_cart() ) {
			foreach ( $items as $cart_id => $item ) {
				$course = learn_press_get_course( $item['item_id'] );
				if ( ! $course ) {
					continue;
				}
				$subtotal = apply_filters( 'learn-press/calculate_sub_total', $course->get_price() * $item['quantity'], $item);
				$total    = $subtotal;

				$this->_cart_content[ $cart_id ]['subtotal'] = $subtotal;
				$this->_cart_content[ $cart_id ]['total']    = $total;

				$this->subtotal += $subtotal;
				$this->total    += $total;
			}
		}

		// Update cart content to session
		$this->update_session();
	}

	/**
	 * Update cart content to session
	 */
	public function update_session() {
		learn_press_session_set( $this->_cart_session_key, $this->get_cart_for_session() );
	}

	/**
	 * Get cart id
	 *
	 * @return mixed
	 */
	public function get_cart_id() {
		return ! empty( $_SESSION['learn_press_cart']['cart_id'] ) ? $_SESSION['learn_press_cart']['cart_id'] : 0;
	}

	/**
	 * Get all items from cart.
	 *
	 * @return array
	 */
	public function get_items() {
		$items = $this->get_cart();

		return $items;
	}

	/**
	 * Load cart content data from session
	 */
	public function get_cart_from_session() {
		if ( ! did_action( 'learn_press_get_cart_from_session' ) ) {

			if ( $cart = learn_press_session_get( $this->_cart_session_key ) ) {
				foreach ( $cart as $cart_id => $values ) {
					if ( ! empty( $values['item_id'] ) ) {
						$course = learn_press_get_course( $values['item_id'] );
						if ( $course && $course->exists() && $values['quantity'] > 0 ) {
							if ( ! $course->is_purchasable() ) {
								learn_press_add_message( sprintf( __( '%s has been removed from your cart because it can no longer be purchased.', 'learnpress' ), $course->get_title() ), 'error' );
								do_action( 'learn-press/remove-cart-item-from-session', $cart, $values );
							} else {
								$data                            = array_merge( $values, array( 'data' => $course ) );
								$this->_cart_content[ $cart_id ] = apply_filters( 'learn-press/get-cart-item-from-session', $data, $values, $cart_id );
							}
						}
					}
				}
			}

			do_action( 'learn_press_cart_loaded_from_session' );
			LP()->session->set( 'cart', $this->get_cart_for_session() );
			do_action( 'learn_press_get_cart_from_session' );

			// Update total
			$this->calculate_totals();
		}
	}

	/**
	 * Get cart sub-total
	 *
	 * @return mixed
	 */
	public function get_subtotal() {
		$subtotal = learn_press_format_price( $this->subtotal, true );

		// @deprecated
		$subtotal = apply_filters( 'learn_press_get_cart_subtotal', $subtotal );

		return apply_filters( 'learn-press/cart-subtotal', $subtotal );
	}

	/**
	 * Get cart total
	 *
	 * @return mixed
	 */
	public function get_total() {
		$total = learn_press_format_price( $this->total, true );

		// @deprecated
		$total = apply_filters( 'learn_press_get_cart_total', $total );

		return apply_filters( 'learn-press/cart-total', $total );
	}

	/**
	 * Generate unique cart id from course id and data.
	 *
	 * @param int   $course_id
	 * @param mixed $data
	 *
	 * @return string
	 */
	public function generate_cart_id( $course_id, $data = '' ) {
		$cart_id = array( $course_id );
		if ( is_array( $data ) ) {
			foreach ( $data as $key => $value ) {
				$cart_id[1] = '';
				if ( is_array( $value ) || is_object( $value ) ) {
					$value = http_build_query( $value );
				}
				$cart_id[1] .= trim( $key ) . trim( $value );
			}
		}

		return apply_filters( 'learn-press/cart-id', md5( join( '_', $cart_id ) ), $cart_id, sanitize_post( $data, 'raw' ) );
	}

	/**
	 * Return sub-total of cart content
	 *
	 * @param LP_Course $course
	 * @param int       $quantity
	 *
	 * @return mixed
	 */
	public function get_item_subtotal( $course, $quantity = 1 ) {
		$price           = $course->get_price();
		$row_price       = $price * $quantity;
		$course_subtotal = learn_press_format_price( $row_price, true );

		return apply_filters( 'learn-press/cart/item-subtotal', $course_subtotal, $course, $quantity, $this );
	}

	/**
	 * Clean all items from cart
	 *
	 * @return $this
	 */
	public function empty_cart() {

		do_action( 'learn-press/cart/before-empty' );

		$this->_cart_content = array();

		unset( LP()->session->order_awaiting_payment );
		unset( LP()->session->cart );

		do_action( 'learn-press/cart/emptied' );

		return $this;
	}

	/**
	 * Check if cart is empty or not
	 *
	 * @return bool
	 */
	public function is_empty() {
		return sizeof( $this->get_cart() ) === 0; //!$this->_cart_content['items'];
	}

	/**
	 * Get checkout url for checkout page
	 * Return default url of checkout page
	 *
	 * @return mixed
	 */
	public function get_checkout_url() {
		$checkout_url = learn_press_get_page_link( 'checkout' );

		return apply_filters( 'learn_press_get_checkout_url', $checkout_url );
	}

	/**
	 * Checks if need to payment
	 * Return true if cart total greater than 0
	 *
	 * @return mixed
	 */
	public function needs_payment() {
		return apply_filters( 'learn_press_cart_needs_payment', $this->total > 0, $this );
	}

	/**
	 * Process action for purchase course button
	 *
	 * @param $course_id
	 */
	public function purchase_course_handler( $course_id ) {

		do_action( 'learn_press_before_purchase_course_handler', $course_id, $this );

		if ( apply_filters( 'learn_press_purchase_single_course', true ) ) {
			$this->empty_cart();
		}
		$this->add_to_cart( $course_id, 1, $_POST );
		$redirect      = learn_press_get_checkout_url();
		$has_checkout  = $redirect ? true : false;
		$need_checkout = $this->needs_payment();

		// In case the course is FREE and "No checkout free course" is turn off
		if ( ! $need_checkout ) {
			$user = learn_press_get_current_user();
			if ( ! $user->has_purchased_course( $course_id )/* || $user->has_finished_course( $course_id ) */ ) {
				require_once LP_PLUGIN_PATH . '/inc/gateways/class-lp-gateway-none.php';
				$checkout = learn_press_get_checkout( array( 'payment_method' => new LP_Gateway_None() ) );

				/**
				 * + Auto enroll
				 */
				add_filter( 'learn_press_checkout_success_result', '_learn_press_checkout_success_result', 10, 2 );
				$checkout->process_checkout();
				remove_filter( 'learn_press_checkout_success_result', '_learn_press_checkout_success_result', 10 );
			}/* else {
				if ( $user->has_finished_course( $course_id ) ) {
					learn_press_add_message( __( 'You have already finished course', 'learnpress' ) );
				} else {
					learn_press_add_message( __( 'You have already enrolled course', 'learnpress' ) );
				}
			}*/
		} else {

			// Checkout page is not setting up
			if ( ! $has_checkout ) {
				learn_press_add_message( __( 'Checkout page hasn\'t been setup', 'learnpress' ), 'error' );
			} else {
				wp_redirect( apply_filters( 'learn_press_checkout_redirect', $redirect ) );
				exit();
			}

		}

		return;
	}

	/**
	 * Destroy cart instance
	 */
	public function destroy() {

	}


	/**
	 * Get unique instance of LP_Cart object
	 *
	 * @return LP_Cart|mixed
	 */
	public static function instance() {
		$class = __CLASS__;
		if ( function_exists( 'get_called_class' ) ) {
			$class = get_called_class();
		}

		$backtrace = debug_backtrace();

		if ( isset( $backtrace[2]['function'] ) ) {
			if ( 'call_user_func' === $backtrace[2]['function'] ) {
				$class = $backtrace[2]['args'][0][0];
			}
		} elseif ( isset( $backtrace[2]['class'] ) ) {
			$class = $backtrace[2]['class'];
		}

		if ( empty( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
		}

		return self::$instances[ $class ];
	}
}
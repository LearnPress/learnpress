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
	protected $_cart_content = array();

	/**
	 * Key of cart content stored in session
	 *
	 * @var string
	 */
	protected $_cart_session_key = 'cart';

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( !is_array( $this->_cart_content = learn_press_session_get( $this->_cart_session_key ) ) ) {
			$this->_cart_content = $this->get_default_cart_content();
		}

		$remove_items = array();
		if ( !empty( $this->_cart_content['items'] ) ) {
			foreach ( $this->_cart_content['items'] as $k => $item ) {
				if ( empty( $item['item_id'] ) || !get_post( $item['item_id'] ) ) {
					$remove_items[] = $item['item_id'];
				}
			}
		}
		if ( $remove_items ) {
			foreach ( $remove_items as $item_id ) {
				$this->remove_item( $item_id );
			}
		}
		LP_Request_Handler::register( 'add-course-to-cart', array( $this, 'add_to_cart' ), 20 );
		LP_Request_Handler::register( 'remove-cart-item', array( $this, 'remove_item' ), 20 );

		add_action( 'learn_press_add_to_cart', array( $this, 'calculate_totals' ), 10 );
		add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 ); // Set cookies
		add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 ); // Set cookies before shutdown and ob flushing
		add_action( 'wp_loaded', array( $this, 'init' ) );
	}

	function init() {
		$this->get_cart_from_session();
	}

	public function __get( $key ) {
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

	public function maybe_set_cart_cookies() {
		if ( !headers_sent()/* && did_action( 'wp_loaded' )*/ ) {
			$this->set_cart_cookies( !$this->is_empty() );
		}
	}

	private function set_cart_cookies( $set = true ) {
		if ( $set ) {
			learn_press_setcookie( 'learn_press_items_in_cart', 1 );
			$this->get_cart_for_session();
		} elseif ( isset( $_COOKIE['learn_press_items_in_cart'] ) ) {
			learn_press_setcookie( 'learn_press_items_in_cart', 0, time() - HOUR_IN_SECONDS );
		}
		do_action( 'learn_press_set_cart_cookies', $set );
	}

	public function get_cart_for_session() {
		$cart_session = array();

		if ( $this->get_cart() ) {
			foreach ( $this->get_cart() as $key => $values ) {
				$cart_session[$key] = $values;
				unset( $cart_session[$key]['data'] ); // Unset product object
			}
		}

		return $cart_session;
	}

	public function get_cart() {
		if ( !did_action( 'wp_loaded' ) ) {
			_doing_it_wrong( __FUNCTION__, __( 'Get cart should not be called before the wp_loaded action.', 'learnpress' ), '2.3' );
		}
		if ( !did_action( 'learn_press_cart_loaded_from_session' ) ) {
			$this->get_cart_from_session();
		}
		return array_filter( (array) $this->_cart_content );
	}

	/**
	 * Add course to cart
	 *
	 * @param int $course_id
	 * @param int $quantity
	 * @param     array
	 */
	public function add_to_cart( $course_id, $quantity = 1, $item_data = array() ) {

		$course = learn_press_get_course( $course_id );
		// course is not purchasable
		if ( !$course->is_purchasable() ) {
			learn_press_display_message( __( 'Sorry! This course is not purchasable.', 'learnpress' ) );
			if ( $redirect = apply_filter( 'learn_press_course_is_not_purchasable_redirect', get_the_permalink( $course_id ), $course_id ) ) {
				wp_redirect( $redirect );
				exit();
			}
			return;
		}

		/**
		 * Generate course information and stores into session
		 * for redirection if needed then we can remove it immediately
		 */
		$quantity = apply_filters( 'learn_press_purchase_course_quantity', $quantity, $course_id );
		$price    = $course->is_free() ? 0 : $course->get_price();
		/*$course_info = apply_filters( 'learn_press_purchase_course_info', array(
				'item_id'  => $course_id,
				'quantity' => $quantity,
				'subtotal' => $price * $quantity,
				'total'    => $price * $quantity,
				'data'     => apply_filters( 'learn_press_purchase_course_info_data', $_REQUEST )
			)
		);*/

		$this->_cart_content['items'][$course_id] = apply_filters( 'learn_press_add_cart_item', array(
				'item_id'  => $course_id,
				'quantity' => $quantity,
				'subtotal' => $price * $quantity,
				'total'    => $price * $quantity,
				'data'     => $item_data
			)
		);

		if ( did_action( 'wp' ) ) {
			$this->set_cart_cookies( true );
		}

		do_action( 'learn_press_add_to_cart', $course_id, $quantity, $item_data, $this );

		return;

		if ( !learn_press_is_enable_cart() ) {
			$this->empty_cart();
		}
		$course = learn_press_get_course( $course_id );

		/*
		if ( !empty( $this->_cart_content['items'][$course_id] ) ) {
			$quantity += $this->_cart_content['items'][$course_id]['quantity'];
		}*/
		if ( !$course->is_purchasable() ) {
			learn_press_add_message( __( 'Sorry! This course is not purchasable.', 'learnpress' ) );
			wp_redirect( get_the_permalink( $course_id ) );
			exit();
		}

		$quantity                                 = 1;
		$price                                    = $course->is_free() ? 0 : $course->get_price();
		$this->_cart_content['items'][$course_id] = apply_filters( 'learn_press_add_cart_item', array(
				'item_id'  => $course_id,
				'quantity' => $quantity,
				'subtotal' => $price * $quantity,
				'total'    => $price * $quantity,
				'data'     => $item_data
			)
		);

		learn_press_session_set( $this->_cart_session_key, $this->_cart_content );

		$this->set_cart_cookies( !$this->is_empty() );

		do_action( 'learn_press_add_to_cart', $course_id, $quantity, $item_data, $this );
		$button = '';

		// if the course is FREE and no require checkout screen

        /*
		if ( LP()->settings->get( 'no_checkout_free_course' ) == 'yes' && $this->total == 0 ) {
			if ( !is_user_logged_in() ) {
				$redirect = learn_press_get_page_link( 'profile' );
				if ( !$redirect ) {
					$redirect = wp_login_url();
				} else {
					$redirect = add_query_arg( 'next', 'enroll-course', $redirect );
				}
				learn_press_add_message( __( 'Please login to continue process.', 'learnpress' ) );
				$checkout_results['redirect'] = apply_filters( 'learn_press_no_checkout_free_course_redirect', $redirect );
			} else {
				add_filter( 'learn_press_checkout_success_result', '_learn_press_checkout_auto_enroll_free_course', 10, 2 );
				$checkout_results = LP_Checkout::instance()->process_checkout();
			}
			$redirect = $checkout_results['redirect'];
//			if ( is_lp_ajax() ) {
//				learn_press_send_json(
//					$checkout_results
//				);
//			} else {
//				wp_redirect( $checkout_results['redirect'] );
//				exit();
//			}
		}

		else {

        */

        if ( learn_press_is_enable_cart() ) {
            if ( LP()->settings->get( 'redirect_after_add' ) == 'yes' ) {
                $redirect = learn_press_get_page_link( 'cart' );
                if ( !$redirect ) {
                    learn_press_add_message( sprintf( __( 'Cart page is not setting up.', 'learnpress' ) ) );
                    $redirect = learn_press_get_current_url();
                } else {
                    $button = sprintf( '<a href="%s">%s</a>', get_the_permalink( $course_id ), __( 'Back to class', 'learnpress' ) );
                }
            } else {
                $redirect = get_the_permalink( $course_id );
                if ( !learn_press_get_page_link( 'cart' ) ) {
                    learn_press_add_message( sprintf( __( 'Checkout page is not setting up.', 'learnpress' ) ) );
                } else {
                    $button = sprintf( '<a href="%s">%s</a>', learn_press_get_page_link( 'cart' ), __( 'View cart', 'learnpress' ) );
                }
            }
            learn_press_add_message( sprintf( __( '<strong>%s</strong> has been added to your cart. %s', 'learnpress' ), get_the_title( $course_id ), $button ) );

        } else {
            $redirect = learn_press_get_page_link( 'checkout' );
            if ( !$redirect ) {
                learn_press_add_message( sprintf( __( 'Checkout page is not setting up.', 'learnpress' ) ) );
                $redirect = learn_press_get_current_url();
            }
        }

			/*
		}
			*/

		$redirect = apply_filters( 'learn_press_add_to_cart_redirect', $redirect, $course_id );

		if ( learn_press_is_ajax() ) {
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

	/**
	 * Checks to see if there is an item in cart
	 *
	 * @param $item_id
	 *
	 * @return bool
	 */
	public function has_item( $item_id ) {
		return isset( $this->_cart_content['items'][$item_id] );
	}

	/**
	 * Get an item in cart
	 *
	 * @param $item_id
	 *
	 * @return bool
	 */
	public function get_item( $item_id ) {
		return $this->has_item( $item_id ) ? $this->_cart_content['items'][$item_id] : null;
	}

	/**
	 * Remove an item from cart
	 *
	 * @param $item_id
	 *
	 * @return bool
	 */
	public function remove_item( $item_id ) {
		if ( isset( $this->_cart_content['items'][$item_id] ) ) {

			do_action( 'learn_press_remove_cart_item', $item_id, $this );

			unset( $this->_cart_content['items'][$item_id] );

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
		$subtotal = 0;

		if ( !empty( $this->_cart_content['items'] ) && $items = $this->_cart_content['items'] ) {
			foreach ( $items as $item_id => $item ) {
				$course = learn_press_get_course( $item_id );
				if ( $course ) {
					$_subtotal = $course->get_price() * $item['quantity'];
				}
				$this->_cart_content['items'][$item_id]['subtotal'] = $_subtotal;
				$this->_cart_content['items'][$item_id]['total']    = $_subtotal;
				$subtotal += $_subtotal;
			}

		}
		$this->_cart_content['subtotal'] = $subtotal;
		$this->_cart_content['total']    = $subtotal;
		$this->update_session();
	}

	/**
	 * Update cart content to session
	 */
	public function update_session() {
		learn_press_session_set( $this->_cart_session_key, $this->_cart_content );
	}

	/**
	 * Get cart id
	 *
	 * @return mixed
	 */
	public function get_cart_id() {
		return !empty( $_SESSION['learn_press_cart']['cart_id'] ) ? $_SESSION['learn_press_cart']['cart_id'] : 0;
	}

	/**
	 * Get all items from cart
	 *
	 * @return mixed
	 */
	public function get_items() {
		if ( !did_action( 'learn_press_get_cart_from_session' ) ) {
			$this->get_cart_from_session();
		}
		return !empty( $this->_cart_content['items'] ) ? $this->_cart_content['items'] : array();
	}

	/**
	 * Load cart content data from session
	 *
	 * @param bool $force
	 */
	public function get_cart_from_session( $force = false ) {
		if ( !did_action( 'learn_press_get_cart_from_session' ) || $force ) {
			$this->_cart_content = learn_press_session_get( $this->_cart_session_key );
			do_action( 'learn_press_get_cart_from_session' );
		}
	}

	/**
	 * Get cart sub-total
	 *
	 * @return mixed
	 */
	public function get_subtotal() {

		$subtotal = learn_press_format_price( $this->_cart_content['subtotal'], true );
		return apply_filters( 'learn_press_get_cart_subtotal', $subtotal, $this->get_cart_id() );
	}

	/**
	 * Get cart total
	 *
	 * @return mixed
	 */
	public function get_total() {
		$subtotal = $this->get_subtotal();
		$total    = $subtotal;
		return apply_filters( 'learn_press_get_cart_total', $total, $this->get_cart_id() );
	}

	/**
	 * Generate unique cart id
	 *
	 * @return string
	 */
	public function generate_cart_id() {
		return md5( time() );
	}

	/**
	 * Return sub-total of cart content
	 *
	 * @param     $course
	 * @param int $quantity
	 *
	 * @return mixed|void
	 */
	public function get_item_subtotal( $course, $quantity = 1 ) {
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
	public function empty_cart() {

		do_action( 'learn_press_before_empty_cart' );

		learn_press_session_set( $this->_cart_session_key, $this->get_default_cart_content() );

		$this->get_cart_from_session( true );

		do_action( 'learn_press_emptied_cart' );

		return $this;
	}

	/**
	 * Check if cart is empty or not
	 *
	 * @return bool
	 */
	public function is_empty() {
		return sizeof( $this->get_items() ) === 0; //!$this->_cart_content['items'];
	}

	/**
	 * Return cart content
	 *
	 * @return array|mixed|void
	 */
	public function get_cart_content() {
		return $this->_cart_content;
	}

	public function get_default_cart_content() {
		return apply_filters( 'learn_press_default_cart_content', array(
				'items'    => array(),
				'subtotal' => 0,
				'total'    => 0
			)
		);
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
	 * @return mixed|void
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
		if ( !$need_checkout ) {
			$user = learn_press_get_current_user();
			if ( !$user->has_purchased_course( $course_id )/* || $user->has_finished_course( $course_id ) */ ) {
				require_once LP_PLUGIN_PATH . '/inc/gateways/class-lp-gateway-none.php';
				$checkout = learn_press_get_checkout( array( 'payment_method' => new LP_Gateway_None() ) );

				/**
				 * + Auto enroll
				 */
				add_filter( 'learn_press_checkout_success_result', '_learn_press_checkout_success_result', 10, 2 );
				$results = $checkout->process_checkout();
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
			if ( !$has_checkout ) {
				learn_press_add_message( __( 'Checkout page is not setup', 'learnpress' ), 'error' );
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
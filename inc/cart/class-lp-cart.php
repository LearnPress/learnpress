<?php
/**
 * Class LP_Cart
 *
 * Simple Cart object for now. Maybe need to expand later
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0.1
 */

use LearnPress\Models\CourseModel;

defined( 'ABSPATH' ) || exit();

class LP_Cart {

	/**
	 * Unique instance of LP_Cart
	 *
	 * @var LP_Cart object
	 */
	private static $instance = null;

	/**
	 * Hold the content of the cart
	 *
	 * @var array
	 */
	protected $_cart_content = [];

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
	 *
	 * @param string $key . Added since 3.3.0
	 */
	public function __construct( $key = '' ) {
		if ( $key ) {
			$this->_cart_session_key = $key;
		}

		LP_Request::register( 'add-course-to-cart', array( $this, 'add_to_cart' ), 20 );
		LP_Request::register( 'remove-cart-item', array( $this, 'remove_item' ), 20 );

		//add_action( 'learn-press/add-to-cart', array( $this, 'calculate_totals' ), 10 );
		//add_action( 'wp', array( $this, 'maybe_set_cart_cookies' ), 99 );
		//add_action( 'shutdown', array( $this, 'maybe_set_cart_cookies' ), 0 );
		//add_action( 'wp_loaded', array( $this, 'init' ) );
	}

	/**
	 * Add course to cart.
	 *
	 * @param int $item_id
	 * @param int $quantity
	 * @param array $item_data
	 *
	 * @return string|false
	 */
	public function add_to_cart( int $item_id = 0, int $quantity = 1, array $item_data = array() ) {
		try {
			$item_type = get_post_type( $item_id );

			if ( ! in_array( $item_type, learn_press_get_item_types_can_purchase() ) ) {
				throw new Exception( 'Item type is invalid!', 'learnpress' );
			}

			switch ( $item_type ) {
				case LP_COURSE_CPT:
					$course = CourseModel::find( $item_id, true );
					if ( ! $course ) {
						throw new Exception( __( 'Course is not exists!', 'learnpress' ) );
					}
					break;
				default:
					$item_data = apply_filters( 'learnpress/cart/add-item/item_type_' . $item_type, $item_data, $item_id, $quantity );
					break;
			}

			$cart_id                         = $this->generate_cart_id( $item_id, $item_data );
			$this->_cart_content             = $this->get_cart_from_session();
			$this->_cart_content[ $cart_id ] = apply_filters(
				'learn_press_add_cart_item',
				array_merge(
					array(
						'item_id'  => $item_id,
						'quantity' => $quantity,
						//'data'     => array(),
					),
					$item_data
				)
			);

			// Update cart to session DB.
			$this->update_session( $this->_cart_content );

			do_action( 'learn-press/add-to-cart', $item_id, $quantity, $item_data, $cart_id );

			return $cart_id;
		} catch ( Exception $e ) {
			if ( $e->getMessage() ) {

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

			$this->update_session( $this->_cart_content );

			return true;
		}

		return false;
	}

	/**
	 * Re-calculate cart totals and update data to session
	 *
	 * @since 3.0.0
	 * @version 4.0.2
	 */
	public function calculate_totals() {
		$data           = new stdClass();
		$total          = 0;
		$data->subtotal = $total;
		$data->total    = $total;
		$subtotal       = $total;
		$this->total    = $total;
		$this->subtotal = $total;
		$items          = $this->get_items();

		if ( $items ) {
			foreach ( $items as $cart_id => $item ) {
				$item_type = get_post_type( $item['item_id'] );
				if ( ! in_array( $item_type, learn_press_get_item_types_can_purchase() ) ) {
					continue;
				}

				switch ( $item_type ) {
					case LP_COURSE_CPT:
						$course   = learn_press_get_course( $item['item_id'] );
						$subtotal = $course->get_price() * absint( $item['quantity'] );
						break;
					default:
						$subtotal = apply_filters( 'learnpress/cart/calculate_sub_total/item_type_' . $item_type, $subtotal, $item );
						break;
				}

				$total = $subtotal;

				$data->subtotal += $subtotal;
				$data->total    += $total;

				// For template old use.
				LearnPress::instance()->cart->subtotal += $subtotal;
				LearnPress::instance()->cart->total    += $total;
			}
		}

		return apply_filters( 'lp/cart/calculate_total', $data, $items );

		//$this->update_session();
	}

	/**
	 * Update cart content to session
	 *
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	public function update_session( array $cart_content = [] ) {
		//learn_press_session_set( $this->_cart_session_key, $this->get_cart_for_session() );
		//$cart = $this->get_cart();

		// Only save data item_id and quantity.
		/**
		 * Current certificate is not compatible with type $data_cart_save,
		 * because certificate override hook 'learn-press/review-order/cart-item-product' to set course.
		 * Need rewrite certificate to compatible with new cart.
		 */
		/*$data_cart_save = array_map(
			function ( $item ) {
				return array(
					'item_id'  => $item['item_id'],
					'quantity' => $item['quantity'],
				);
			},
			$cart
		);*/

		LearnPress::instance()->session->set( $this->_cart_session_key, $cart_content, true );
	}

	/**
	 * Get all items from cart.
	 *
	 * @return array
	 */
	public function get_items(): array {
		return $this->get_cart_from_session();
	}

	/**
	 * Load cart content data from session
	 *
	 * @since 3.0.0
	 * @version 3.0.2
	 * @modify 4.2.0 - tungnx
	 */
	public function get_cart_from_session() {
		$session_data = LearnPress::instance()->session->get_session_data();
		$cart         = maybe_unserialize( $session_data['cart'] ?? '' );
		if ( ! is_array( $cart ) ) {
			$cart = [];
		}

		$this->_cart_content = $cart;

		return $this->_cart_content;
	}

	/**
	 * Get cart sub-total
	 *
	 * @return mixed
	 */
	public function get_subtotal() {
		$subtotal = learn_press_format_price( $this->subtotal, true );

		return apply_filters( 'learn-press/cart-subtotal', $subtotal, $this );
	}

	/**
	 * Get cart total
	 *
	 * @return mixed
	 */
	public function get_total() {
		$total = learn_press_format_price( $this->total, true );

		return apply_filters( 'learn-press/cart-total', $total, $this );
	}

	/**
	 * Generate unique cart id from course id and data.
	 *
	 * @param int $course_id
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

		return apply_filters( 'learn-press/cart-id', md5( join( '_', $cart_id ) ), $cart_id, $data );
	}

	/**
	 * Return subtotal of cart content
	 *
	 * @param LP_Course|LP_Certificate|CourseModel $item
	 * @param int $quantity
	 *
	 * @return mixed
	 */
	public function get_item_subtotal( $item, int $quantity = 1 ) {
		$price           = $item->get_price();
		$row_price       = $price * $quantity;
		$course_subtotal = learn_press_format_price( $row_price, true );

		return apply_filters( 'learn-press/cart/item-subtotal', $course_subtotal, $item, $quantity, $this );
	}

	/**
	 * Clean all items from cart
	 *
	 * @return $this
	 */
	public function empty_cart(): LP_Cart {

		do_action( 'learn-press/cart/before-empty' );

		$this->_cart_content = array();
		$lp_session          = LearnPress::instance()->session;
		$lp_session->remove( 'order_awaiting_payment', true );
		$lp_session->remove( 'cart', true );
		//unset( LearnPress::instance()->session->order_awaiting_payment );
		//unset( LearnPress::instance()->session->cart );

		do_action( 'learn-press/cart/emptied' );

		return $this;
	}

	/**
	 * Check if cart is empty or not
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		$cart_content = $this->get_cart_from_session();

		return sizeof( $cart_content ) === 0;
	}

	/**
	 * Checks if need to payment
	 * Return true if cart total greater than 0
	 *
	 * @return mixed
	 */
	public function needs_payment() {
		$cart_data = $this->calculate_totals();

		return apply_filters( 'learn_press_cart_needs_payment', $cart_data->total > 0, $this );
	}


	/**
	 * Get unique instance of LP_Cart object
	 *
	 * @return LP_Cart
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

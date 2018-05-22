<?php
/**
 * Checkout Page Shortcode.
 *
 * @author   ThimPress
 * @category Shortcodes
 * @package  Learnpress/Shortcodes
 * @version  3.0.0
 * @extends  LP_Abstract_Shortcode
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcode_Checkout' ) ) {

	/**
	 * Class LP_Shortcode_Checkout
	 */
	class LP_Shortcode_Checkout extends LP_Abstract_Shortcode {

		/**
		 * LP_Checkout_Shortcode constructor.
		 *
		 * @param mixed $atts
		 */
		public function __construct( $atts = '' ) {
			parent::__construct( $atts );
		}

		/**
		 * Output form.
		 *
		 * @return string
		 */
		public function output() {

			global $wp;
			ob_start();

			if ( isset( $wp->query_vars['lp-order-received'] ) ) {
				$this->_order_received( $wp->query_vars['lp-order-received'] );
			} else {
				// Check cart has contents
				if ( LP()->cart->is_empty() ) {
					learn_press_get_template( 'checkout/empty-cart.php' );
				} else {
					learn_press_get_template( 'checkout/form.php' );
				}
			}

			return ob_get_clean();
		}

		/**
		 * Output received order information.
		 *
		 * @param int $order_id
		 */
		private function _order_received( $order_id = 0 ) {
			// Get the order
			$order_id  = absint( $order_id );
			$order_key = ! empty( $_GET['key'] ) ? $_GET['key'] : '';
			$order     = null;

			if ( $order_id > 0 && ( $origin_order = learn_press_get_order( $order_id ) ) && ! $origin_order->is_trashed() ) {
				if ( $origin_order->get_order_key() == $order_key ) {
					$order = $origin_order;
				}
			}

			LP()->session->remove( 'order_awaiting_payment' );
			LP()->cart->empty_cart();

			learn_press_print_messages();

			learn_press_get_template( 'checkout/order-received.php', array( 'order' => $order ) );
		}
	}
}


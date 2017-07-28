<?php

/**
 * Class LP_Checkout_Shortcode
 *
 * Shortcode to display the checkout form
 *
 * @since 3.x.x
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
				learn_press_get_template( 'cart/empty-cart.php' );
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

		if ( $order_id > 0 && ( $order = learn_press_get_order( $order_id ) ) && $order->get_data( 'post_status' ) != 'trash' ) {
			if ( $order->order_key != $order_key ) {
				unset( $order );
			}
		} else {
			learn_press_display_message( __( 'Invalid order!', 'learnpress' ), 'error' );

			return;
		}

		LP()->session->order_awaiting_payment = null;

		learn_press_get_template( 'checkout/order-received.php', array( 'order' => $order ) );
	}
}
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

use LearnPress\Helpers\Template;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Shortcode_Checkout' ) ) {

	/**
	 * Class LP_Shortcode_Checkout
	 */
	class LP_Shortcode_Checkout extends LP_Abstract_Shortcode {

		/**
		 * LP_Checkout_Shortcode constructor.
		 *
		 * @param  mixed $atts
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
			/**
			 * @var WP $wp
			 */
			global $wp;
			wp_enqueue_style( 'learnpress' );

			ob_start();
			if ( isset( $wp->query_vars['lp-order-received'] ) ) {
				$this->order_received( $wp->query_vars['lp-order-received'] );
			} else {
				$checkout_cart = LearnPress::instance()->get_cart();

				echo '<div id="learn-press-checkout" class="lp-content-area">';

				// Check cart has contents
				if ( $checkout_cart->is_empty() ) {
					Template::instance()->get_frontend_template( 'checkout/empty-cart.php' );
				} else {
					wp_enqueue_script( 'lp-checkout' );
					$checkout_cart->calculate_totals();
					Template::instance()->get_frontend_template( 'checkout/form.php' );
				}

				echo '</div>';
			}

			return ob_get_clean();
		}

		/**
		 * Output received order information.
		 *
		 * @param  int $order_id
		 *
		 * @return void
		 */
		private function order_received( int $order_id = 0 ) {
			$order_id       = absint( $order_id );
			$order_key      = LP_Request::get_param( 'key' );
			$order_received = learn_press_get_order( $order_id );
			if ( ! $order_received ) {
				return;
			}

			if ( $order_received->is_trashed() || $order_received->get_order_key() !== $order_key ) {
				return;
			}

			//LearnPress::instance()->session->remove( 'order_awaiting_payment' );
			//LearnPress::instance()->cart->empty_cart();
			//learn_press_print_messages();

			Template::instance()->get_frontend_template( 'checkout/order-received.php', compact( 'order_received' ) );
		}
	}
}


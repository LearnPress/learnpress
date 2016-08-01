<?php
/**
 * Class LP_Gateway_None
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Gateway_None extends LP_Gateway_Abstract {
	public function __construct() {
		$this->id = 'paypal';

		$this->method_title       = 'Paypal';
		$this->method_description = 'Make payment via Paypal';

		$this->title       = 'Paypal';
		$this->description = __( 'Pay with Paypal', 'learnpress' );

		parent::__construct();
	}

	public function process_payment( $order_id ) {
		$order = learn_press_get_order( $order_id );

		// Mark as processing (payment won't be taken until delivery)
		$order->update_status( 'completed', __( 'Payment to be made upon delivery.', 'learnpress-offline-payment' ) );


		// Remove cart
		LP()->cart->empty_cart();

		// Return thankyou redirect
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order )
		);
	}
}
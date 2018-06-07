<?php
die(__FILE__);
class LP_Gateway_Paypal_Basic extends LP_Gateway_Paypal{
	public function get_request_url( $order_id ) {

		$user = learn_press_get_current_user();

		$nonce = wp_create_nonce( 'learn-press-paypal-nonce' );
		$order = new LP_Order( $order_id );
		$custom = array( 'order_id' => $order_id, 'order_key' => $order->order_key );

		$query = array(
			'cmd'      => '_xclick',
			'amount'   => learn_press_get_cart_total(),
			'quantity' => '1',
			'business'      => $this->paypal_email,
			'item_name'     => learn_press_get_cart_description(),
			'return'        => add_query_arg( array( 'learn-press-transaction-method' => 'paypal-standard', 'paypal-nonce' => $nonce ), learn_press_get_cart_course_url() ),
			'currency_code' => learn_press_get_currency(),
			'notify_url'    => get_home_url() /* SITE_URL */ . '/?' . learn_press_get_web_hook( 'paypal-standard' ) . '=1',
			'no_note'       => '1',
			'shipping'      => '0',
			'email'         => $user->user_email,
			'rm'            => '2',
			'cancel_return' => learn_press_get_cart_course_url(),
			'custom'        => json_encode( $custom ),
			'no_shipping'   => '1'
		);

		$query = apply_filters( 'learn_press_paypal_standard_query', $query );

		$paypal_payment_url = $this->paypal_url . '?' . http_build_query( $query );

		return $paypal_payment_url;
	}
}
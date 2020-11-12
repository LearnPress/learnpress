<?php

/**
 * Class LP_Template_Checkout
 *
 * Groups templates related checkout page
 *
 * @since 3.x.x
 */
class LP_Template_Checkout extends LP_Abstract_Template {

	/**
	 * LP_Template_Checkout constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	public function review_order() {
		learn_press_get_template( 'checkout/review-order' );
	}

	public function payment() {
		$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();

		learn_press_get_template( 'checkout/payment.php', array( 'available_gateways' => $available_gateways ) );
	}

	public function order_comment() {
		learn_press_get_template( 'checkout/order-comment.php' );
	}

	public function account_logged_in() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		learn_press_get_template( 'checkout/account-logged-in' );
	}

	public function account_login() {
		if ( is_user_logged_in() || ! LP()->checkout()->is_enable_login() ) {
			return;
		}

		learn_press_get_template( 'checkout/account-login' );
	}

	public function account_register() {
		if ( is_user_logged_in() || ! LP()->checkout()->is_enable_register() ) {
			return;
		}

		learn_press_get_template( 'checkout/account-register' );
	}

	public function guest_checkout() {
		if ( is_user_logged_in() || ! LP()->checkout()->is_enable_guest_checkout() ) {
			return;
		}

		learn_press_get_template( 'checkout/guest-checkout' );
	}

	public function terms() {
		learn_press_get_template( 'checkout/term-conditions' );
	}
}

return new LP_Template_Checkout();

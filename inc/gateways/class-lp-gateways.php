<?php

/**
 * Class LP_Gateways
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LP_Gateways
 */
class LP_Gateways {

	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * @var array
	 */
	protected $payment_gateways = array();

	/**
	 * LP_Gateways constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Init gateways
	 */
	public function init() {
		if ( ! $this->payment_gateways ) {
			$gateways = array(
				'paypal'          => 'LP_Gateway_Paypal',
				'offline-payment' => 'LP_Gateway_Offline_Payment',
			);
			// @deprecated 4.2.3.5
			$gateways = apply_filters( 'learn_press_payment_method', $gateways );

			// 3.0.0
			$gateways = apply_filters( 'learn-press/payment-methods', $gateways );
			if ( $gateways ) {
				foreach ( $gateways as $k => $gateway ) {
					if ( is_string( $gateway ) && class_exists( $gateway ) ) {
						$gateway = new $gateway();

						$this->payment_gateways[ $k ] = $gateway;
					} elseif ( $gateway instanceof LP_Gateway_Abstract ) {
						$this->payment_gateways[ $gateway->id ] = $gateway;
					}
				}
			}
		}
	}

	/**
	 * Get all registered payments.
	 *
	 * @return array
	 * @version 4.0.2
	 */
	public function get_gateways(): array {
		$gateways               = array();
		$order_payment_gateways = get_option( 'learn_press_payment_order' );

		if ( count( $this->payment_gateways ) ) {
			if ( $order_payment_gateways ) {
				foreach ( $order_payment_gateways as $id ) {
					if ( isset( $this->payment_gateways[ $id ] ) ) {
						$gateways[ $id ] = $this->payment_gateways[ $id ];
					}
				}
			} else {
				$gateways = $this->payment_gateways;
			}

			// Case new array $gateways not have all gateways
			foreach ( $this->payment_gateways as $gateway ) {
				if ( isset( $gateways[ $gateway->id ] ) ) {
					continue;
				}

				$gateways[ $gateway->id ] = $gateway;
			}
		}

		return $gateways;
	}

	/**
	 * Get payment gateways are available for use.
	 *
	 * @return mixed
	 * @since 3.0.0
	 * @version 1.0.2
	 */
	public function get_available_payment_gateways() {
		/**
		 * @var LP_Gateway_Abstract[] $gateways
		 */
		$gateways            = $this->get_gateways();
		$_available_gateways = array();
		$gateway_first       = null;

		foreach ( $gateways as $slug => $gateway ) {
			if ( ! is_object( $gateway ) ) {
				continue;
			}

			// Not show woo payment gateway, because when enable will be buy course on checkout of Woocommerce
			if ( $slug == 'woocommerce' || $slug == 'woo-payment' ) {
				continue;
			}

			$gateway_is_available = $gateway->is_enabled();
			if ( $gateway_is_available ) {
				$_available_gateways[ $slug ] = $gateway;

				if ( is_null( $gateway_first ) ) {
					$gateway_first = $gateway;
				}
			}
		}

		// Set default payment if there is no payment is selected
		if ( $_available_gateways ) {
			$gateway_first->is_selected = true;
		}

		return apply_filters( 'learn-press/payment-gateways/available', $_available_gateways );
	}

	/**
	 * @return array
	 *
	 * @deprecated 4.2.3
	 * @uses LP_Request_Withdrawal::get_gateways() on Addon Commission <= 4.0.1
	 */
	/*public function get_availabe_gateways() {
		return $this->payment_gateways;
	}*/

	/**
	 * @param string $id
	 *
	 * @return bool|LP_Gateway_Abstract
	 * @since 3.0.0
	 *
	 */
	public function get_gateway( string $id ) {
		$gateways = $this->get_gateways();

		if ( $gateways ) {
			if ( isset( $gateways[ $id ] ) ) {
				return $gateways[ $id ];
			}
		}

		return false;
	}

	/**
	 * Ensure that only one instance of LP_Gateways is loaded
	 *
	 * @return LP_Gateways|null
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

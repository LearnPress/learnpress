<?php

/**
 * Class LP_Gateways
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
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
	public function __construct() {
		$this->init();
	}

	/**
	 *
	 */
	public function init() {
		if ( !$this->payment_gateways ) {
			$gateways = array(
				'paypal' => 'LP_Gateway_Paypal'
			);
			// Filter
			$gateways = apply_filters( 'learn_press_payment_method', $gateways );
			if ( $gateways ) {
				foreach ( $gateways as $k => $gateway ) {
					if ( is_string( $gateway ) && class_exists( $gateway ) ) {
						$gateway = new $gateway();
					}
					$this->payment_gateways[$k] = apply_filters( 'learn_press_payment_method_init', $gateway );
				}
			}
		}
	}

	/**
	 * @return array
	 */
	public function get_gateways() {
		$gateways = array();
		if ( count( $this->payment_gateways ) ) foreach ( $this->payment_gateways as $gateway ) {
			if ( is_string( $gateway ) && class_exists( $gateway ) ) {
				$gateway = new $gateway();
			}
			if ( !is_object( $gateway ) ) {
				continue;
			}
			$gateways[$gateway->id] = $gateway;
		}
		return $gateways;
	}

	/**
	 * @return mixed|void
	 */
	public function get_available_payment_gateways() {
		$this->init();
		$_available_gateways = array();
		foreach ( $this->payment_gateways as $slug => $gateway ) {
			// let custom addon can define how is enable/disable
			if ( apply_filters( 'learn_press_payment_gateway_available_' . $slug, false, $gateway ) ) {
				$_available_gateways[$slug] = $gateway;
			};
		}

		return apply_filters( 'learn_press_available_payment_gateways', $_available_gateways );
	}

	/**
	 * @return array
	 */
	public function get_availabe_gateways() {
		return $this->payment_gateways;
	}

	/**
	 * Ensure that only one instance of LP_Gateways is loaded
	 * @return LP_Gateways|null
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}
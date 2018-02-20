<?php

/**
 * Class LP_Gateway_Abstract
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Gateway_Abstract {
	/**
	 * @var mixed|null
	 */
	public $method_title = null;

	/**
	 * @var null
	 */
	public $method_description = null;

	/**
	 * @var null
	 */
	public $id = null;

	/**
	 * @var string
	 */
	public $order_button_text = '';

	/**
	 * @var null
	 */
	protected $title = null;

	/**
	 * @var null
	 */
	protected $description = null;

	/**
	 * Constructor
	 */
	public function __construct() {

		if ( !$this->method_title ) {
			$this->method_title = preg_replace( '!LP_Gateway_!', '', get_class( $this ) );
		}
		if ( !$this->id ) {
			$this->id = sanitize_title( $this->title );
		}
	}

	public function process_payment( $order ) {
		return array();
	}

	public function get_title() {
		return apply_filters( 'learn_press_gateway_title', $this->title, $this->id );
	}

	public function get_description() {
		return apply_filters( 'learn_press_gateway_description', $this->description, $this->id );
	}

	public function get_icon() {

		$icon = $this->icon ? '<img src="' .  $this->icon  . '" alt="' . esc_attr( $this->get_title() ) . '" />' : '';

		return apply_filters( 'learn_press_gateway_icon', $icon, $this->id );
	}

	public function get_payment_form() {
		return '';
	}

	public function validate_fields() {
		// TODO: validate fields if needed
		return true;
	}

	public function get_return_url( $order = null ) {

		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = learn_press_get_endpoint_url( 'lp-order-received', '', learn_press_get_page_link( 'checkout' ) );
		}

		return apply_filters( 'learn_press_get_return_url', $return_url, $order );
	}
}
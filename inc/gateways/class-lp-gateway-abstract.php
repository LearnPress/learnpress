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

class LP_Gateway_Abstract extends LP_Abstract_Settings {
	/**
	 * @var null|string
	 */
	public $id = null;
	/**
	 * @var LP_Settings
	 */
	protected $settings;
	/**
	 * Name of gateway will be displayed in admin settings.
	 *
	 * @var string
	 */
	protected $method_title = '';

	/**
	 * Description of gateway will be displayed in admin settings.
	 *
	 * @var string
	 */
	protected $method_description = '';

	/**
	 * @var string
	 */
	public $order_button_text = '';

	/**
	 * This payment is turn on or off?
	 *
	 * @var string
	 */
	public $enabled = 'no';

	/**
	 * @var null
	 */
	public $title = null;

	/**
	 * @var null
	 */
	public $description = null;

	/**
	 * @var string
	 */
	protected $icon = '';
	/**
	 * @var bool set default select when checkout
	 */
	public $is_selected = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		/*if ( ! $this->admin_name ) {
			$this->admin_name = preg_replace( '!LP_Gateway_!', '', get_class( $this ) );
		}*/

		if ( ! $this->id ) {
			$this->id = sanitize_title( $this->title );
		}

		$this->settings = LP_Settings::instance()->get_group( $this->id, '' );
		$this->enabled  = $this->settings->get( 'enable' );

		add_filter( 'learn-press/admin/get-settings/admin-options-' . $this->id, array( $this, 'get_settings' ) );
	}

	/**
	 * Return unique Id of payment
	 *
	 * @return null|string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Return method title.
	 *
	 * @return string
	 */
	public function get_method_title() {
		return $this->method_title;
	}

	/**
	 * Return method description.
	 *
	 * @return string
	 */
	public function get_method_description() {
		return $this->method_description;
	}

	/**
	 * Return method title displays in front end.
	 *
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'learn_press_gateway_title', $this->title, $this->id );
	}

	/**
	 * Return method description displays in front end.
	 *
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'learn_press_gateway_description', $this->description, $this->id );
	}

	/**
	 * Payment is turn on or off?
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return $this->enabled === 'yes';
	}

	public function enable( $status ) {
		if ( is_bool( $status ) ) {
			$this->enabled = $status;

			$options = get_option( 'learn_press_' . $this->get_id() );

			if ( ! $options ) {
				$options = array();
			}

			$options['enable'] = $status ? 'yes' : 'no';
			update_option( 'learn_press_' . $this->get_id(), $options );
		}

		return $this->enabled == 'yes';
	}

	/**
	 * Process the payment.
	 *
	 * @param $order_id
	 *
	 * @return array
	 */
	public function process_payment( $order_id ) {
		return array();
	}

	/**
	 * Get the icon of payment displays in front end.
	 *
	 * @return mixed
	 */
	public function get_icon() {
		$size = apply_filters( 'learn-press/default-payment-gateway-icon-sizes', null ); // array( 52, 32 ) is low quatity.

		if ( $size ) {
			$icon_size = sprintf( 'width: %dpx; height: %dpx', $size[0], $size[1] );
		} else {
			$icon_size = '';
		}

		$icon = $this->icon ? '<img class="gateway-icon" src="' . $this->icon . '" alt="' . esc_attr( $this->get_title() ) . '" style="' . $icon_size . '" />' : '';

		return apply_filters( 'learn_press_gateway_icon', $icon, $this->id );
	}

	/**
	 * Return the form where user can input payment details or anything else.
	 *
	 * @return string
	 */
	public function get_payment_form() {
		return '';
	}

	/**
	 * Validate required field before submitting fields.
	 *
	 * @return bool
	 */
	public function validate_fields() {
		// TODO: validate fields if needed
		return true;
	}

	/**
	 * @param LP_Order $order
	 *
	 * @return mixed
	 */
	public function get_return_url( $order = null ) {
		if ( $order ) {
			$return_url = $order->get_checkout_order_received_url();
		} else {
			$return_url = learn_press_get_endpoint_url( 'lp-order-received', '', learn_press_get_page_link( 'checkout' ) );
		}

		return apply_filters( 'learn_press_get_return_url', $return_url, $order );
	}

	public function __get( $prop ) {
		switch ( $prop ) {
			case 'method_title':
			case 'method_description':
			case 'id':
				_deprecated_argument( $prop, '3.0.0', sprintf( __( '%s has been deprecated. Please use % instead of.', 'learnpress' ), $prop, "get_{$prop}" ) );

				return call_user_func( array( $this, "get_{$prop}" ) );
			default:
				return property_exists( $this, $prop ) ? $this->{$prop} : false;
		}
	}

	/**
	 * @since 3.0.0
	 *
	 * return bool
	 * @deprecated 4.2.3.5
	 */
	public function is_display() {
		_deprecated_function( __METHOD__, '4.2.3.5' );
		$display = apply_filters( 'learn-press/payment-method/display', true, $this->id );
		$display = apply_filters( 'learn-press/payment-method-' . $this->id . '/display', $display );

		// @deprecated
		$display = apply_filters( 'learn_press_display_payment_method', $display, $this->id );

		return $display;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->method_title;
	}
}

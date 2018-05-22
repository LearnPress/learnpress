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
	 * @var mixed|null
	 */
	//public $method_title = '';

	/**
	 * @var null
	 */
	//public $method_description = null;

	/**
	 * @var null
	 */
	public $id = null;

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

	protected $stored = false;

	/**
	 * Constructor
	 */
	public function __construct() {

		if ( ! $this->admin_name ) {
			$this->admin_name = preg_replace( '!LP_Gateway_!', '', get_class( $this ) );
		}

		if ( ! $this->id ) {
			$this->id = sanitize_title( $this->title );
		}

		$this->settings = LP()->settings()->get_group( $this->id, '' );
		$this->enabled  = $this->settings->get( 'enable' );
		// Load settings
		$this->_load();
	}

	/**
	 * Load stored settings from database.
	 */
	protected function _load() {
		return;
		if ( false !== ( $this->stored = get_option( 'learn_press_' . $this->id ) ) ) {
			foreach ( $this->stored as $prop => $value ) {
				$prop   = preg_replace( '~[-]+~', '_', $prop );
				$setter = array( $this, "set_" . $prop );
				if ( is_callable( $setter ) ) {
					call_user_func_array( $setter, array( $value ) );
				} elseif ( property_exists( $this, $prop ) ) {
					$this->{$prop} = $value;
				}
			}
		}
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

	public function is_enabled() {
		///echo "[xxxxxxx",$this->id, ',',$this->enabled,"]";
		return $this->enabled == 'yes';
	}

	public function enable( $status ) {
		if ( is_bool( $status ) ) {
			$this->enabled = $status;
			if ( ! $options = get_option( 'learn_press_' . $this->get_id() ) ) {
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
	 * @param $order
	 *
	 * @return array
	 */
	public function process_payment( $order ) {
		return array();
	}

	/**
	 * Get the icon of payment displays in front end.
	 *
	 * @return mixed
	 */
	public function get_icon() {

		if ( $size = apply_filters( 'learn-press/default-payment-gateway-icon-sizes', array( 51, 32 ) ) ) {
			$icon_size = sprintf( 'width: %dpx; height: %dpx', $size[0], $size[1] );
		} else {
			$icon_size = '';
		}

		$icon = $this->icon ? '<img src="' . $this->icon . '" alt="' . esc_attr( $this->get_title() ) . '" style="' . $icon_size . '" />' : '';

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
	 * @param null $order
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

	public function get_admin_field_name( $name ) {
		if ( strpos( $name, '[' ) === 0 ) {
			$name = $this->id . $name;
		} else {
			$name = $this->id . '_' . $name;
		}

		return parent::get_admin_field_name( $name );
	}

	public function admin_options() {
		$settings = $this->get_settings();
		array_unshift( $settings,
			array(
				'title' => $this->get_method_title(),
				'desc'  => $this->get_method_description(),
				'type'  => 'heading'
			)
		);
		$settings = $this->sanitize_settings( $settings );
		LP_Meta_Box_Helper::render_fields( $settings );
	}

	/**
	 * @since 3.0.0
	 *
	 * return bool
	 */
	public function is_display() {
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
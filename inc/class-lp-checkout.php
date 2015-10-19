<?php

/**
 * Class LP_Checkout
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_Checkout {

	/**
	 * @var LP_Checkout object instance
	 * @access protected
	 */
	static protected $_instance = null;

	/**
	 * Payment method
	 *
	 * @var string
	 */
	public $payment_method = '';

	public $checkout_fields = array();

	/**
	 * Constructor
	 */
	function __construct() {
		if ( !is_user_logged_in() ) {
			$this->checkout_fields['user_login']    = __( 'Username', 'learn_press' );
			$this->checkout_fields['user_password'] = __( 'Password', 'learn_press' );
		}
		$this->checkout_fields = apply_filters( 'learn_press_checkout_fields', $this->checkout_fields );

		add_filter( 'learn_press_checkout_validate_field', array( $this, 'validate_fields' ), 10, 3 );
	}

	/**
	 * Creates temp new order if needed
	 *
	 * @return mixed|WP_Error
	 * @throws Exception
	 */
	function create_order() {
		global $wpdb;
		// Third-party can be controls to create a order
		if ( $order_id = apply_filters( 'learn_press_create_order', null, $this ) ) {
			return $order_id;
		}

		try {
			// Start transaction if available
			$wpdb->query( 'START TRANSACTION' );

			$order_data = array(
				'status'        => apply_filters( 'learn_press_default_order_status', 'pending' ),
				'customer_id'   => $this->customer_id,
				'customer_note' => isset( $this->posted['order_comments'] ) ? $this->posted['order_comments'] : '',
				'created_via'   => 'checkout'
			);

			// Insert or update the post data
			$order_id = absint( WC()->session->order_awaiting_payment );

			// Resume the unpaid order if its pending
			if ( $order_id > 0 && ( $order = wc_get_order( $order_id ) ) && $order->has_status( array( 'pending', 'failed' ) ) ) {

				$order_data['order_id'] = $order_id;
				$order                  = wc_update_order( $order_data );

				if ( is_wp_error( $order ) ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 401 ) );
				} else {
					$order->remove_order_items();
					do_action( 'woocommerce_resume_order', $order_id );
				}

			} else {

				$order = wc_create_order( $order_data );

				if ( is_wp_error( $order ) ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 400 ) );
				} else {
					$order_id = $order->id;
					do_action( 'woocommerce_new_order', $order_id );
				}
			}

			// Store the line items to the new/resumed order
			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$item_id = $order->add_product(
					$values['data'],
					$values['quantity'],
					array(
						'variation' => $values['variation'],
						'totals'    => array(
							'subtotal'     => $values['line_subtotal'],
							'subtotal_tax' => $values['line_subtotal_tax'],
							'total'        => $values['line_total'],
							'tax'          => $values['line_tax'],
							'tax_data'     => $values['line_tax_data'] // Since 2.2
						)
					)
				);

				if ( ! $item_id ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 402 ) );
				}

				// Allow plugins to add order item meta
				do_action( 'woocommerce_add_order_item_meta', $item_id, $values, $cart_item_key );
			}

			// Store fees
			foreach ( WC()->cart->get_fees() as $fee_key => $fee ) {
				$item_id = $order->add_fee( $fee );

				if ( ! $item_id ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 403 ) );
				}

				// Allow plugins to add order item meta to fees
				do_action( 'woocommerce_add_order_fee_meta', $order_id, $item_id, $fee, $fee_key );
			}

			// Store shipping for all packages
			foreach ( WC()->shipping->get_packages() as $package_key => $package ) {
				if ( isset( $package['rates'][ $this->shipping_methods[ $package_key ] ] ) ) {
					$item_id = $order->add_shipping( $package['rates'][ $this->shipping_methods[ $package_key ] ] );

					if ( ! $item_id ) {
						throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 404 ) );
					}

					// Allows plugins to add order item meta to shipping
					do_action( 'woocommerce_add_shipping_order_item', $order_id, $item_id, $package_key );
				}
			}

			// Store tax rows
			foreach ( array_keys( WC()->cart->taxes + WC()->cart->shipping_taxes ) as $tax_rate_id ) {
				if ( $tax_rate_id && ! $order->add_tax( $tax_rate_id, WC()->cart->get_tax_amount( $tax_rate_id ), WC()->cart->get_shipping_tax_amount( $tax_rate_id ) ) && apply_filters( 'woocommerce_cart_remove_taxes_zero_rate_id', 'zero-rated' ) !== $tax_rate_id ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 405 ) );
				}
			}

			// Store coupons
			foreach ( WC()->cart->get_coupons() as $code => $coupon ) {
				if ( ! $order->add_coupon( $code, WC()->cart->get_coupon_discount_amount( $code ), WC()->cart->get_coupon_discount_tax_amount( $code ) ) ) {
					throw new Exception( sprintf( __( 'Error %d: Unable to create order. Please try again.', 'woocommerce' ), 406 ) );
				}
			}

			// Billing address
			$billing_address = array();
			if ( $this->checkout_fields['billing'] ) {
				foreach ( array_keys( $this->checkout_fields['billing'] ) as $field ) {
					$field_name = str_replace( 'billing_', '', $field );
					$billing_address[ $field_name ] = $this->get_posted_address_data( $field_name );
				}
			}

			// Shipping address.
			$shipping_address = array();
			if ( $this->checkout_fields['shipping'] ) {
				foreach ( array_keys( $this->checkout_fields['shipping'] ) as $field ) {
					$field_name = str_replace( 'shipping_', '', $field );
					$shipping_address[ $field_name ] = $this->get_posted_address_data( $field_name, 'shipping' );
				}
			}

			$order->set_address( $billing_address, 'billing' );
			$order->set_address( $shipping_address, 'shipping' );
			$order->set_payment_method( $this->payment_method );
			$order->set_total( WC()->cart->shipping_total, 'shipping' );
			$order->set_total( WC()->cart->get_cart_discount_total(), 'cart_discount' );
			$order->set_total( WC()->cart->get_cart_discount_tax_total(), 'cart_discount_tax' );
			$order->set_total( WC()->cart->tax_total, 'tax' );
			$order->set_total( WC()->cart->shipping_tax_total, 'shipping_tax' );
			$order->set_total( WC()->cart->total );

			// Update user meta
			if ( $this->customer_id ) {
				if ( apply_filters( 'woocommerce_checkout_update_customer_data', true, $this ) ) {
					foreach ( $billing_address as $key => $value ) {
						update_user_meta( $this->customer_id, 'billing_' . $key, $value );
					}
					if ( WC()->cart->needs_shipping() ) {
						foreach ( $shipping_address as $key => $value ) {
							update_user_meta( $this->customer_id, 'shipping_' . $key, $value );
						}
					}
				}
				do_action( 'woocommerce_checkout_update_user_meta', $this->customer_id, $this->posted );
			}

			// Let plugins add meta
			do_action( 'woocommerce_checkout_update_order_meta', $order_id, $this->posted );

			// If we got here, the order was created without problems!
			$wpdb->query( 'COMMIT' );

		} catch ( Exception $e ) {
			// There was an error adding order data!
			$wpdb->query( 'ROLLBACK' );
			return new WP_Error( 'checkout-error', $e->getMessage() );
		}

		return $order_id;
	}

	/**
	 * Validate fields
	 *
	 * @param bool
	 * @param $field
	 * @param LP_Checkout instance
	 *
	 * @return bool
	 */
	function validate_fields( $validate, $field, $checkout ) {
		if ( $field['name'] == 'user_login' && empty( $_POST['user_login'] ) ) {
			$validate = false;
			learn_press_add_notice( __( 'Please enter user login', 'learn_press' ) );
		}
		if ( $field['name'] == 'user_password' && empty( $_POST['user_password'] ) ) {
			$validate = false;
			learn_press_add_notice( __( 'Please enter user password', 'learn_press' ) );
		}

		return $validate;
	}

	/**
	 * Process checkout
	 *
	 * @throws Exception
	 */
	function process_checkout() {
		try {
			if ( strtolower( $_SERVER['REQUEST_METHOD'] ) != 'post' ) {
				return;
			}

			// Prevent timeout
			@set_time_limit( 0 );

			do_action( 'learn_press_before_checkout_process' );

			$success = true;

			if ( empty( $_REQUEST['payment_method'] ) ) {
				$success = false;
				learn_press_add_notice( __( 'Please select a payment method', 'learn_press' ), 'error' );
			} else {
				$this->payment_method = $_REQUEST['payment_method'];
				if ( $this->checkout_fields ) foreach ( $this->checkout_fields as $name => $field ) {
					if ( !apply_filters( 'learn_press_checkout_validate_field', true, array( 'name' => $name, 'text' => $field ), $this ) ) {
						$success = false;
					}
				}
				if ( isset( $this->checkout_fields['user_login'] ) && isset( $this->checkout_fields['user_password'] ) ) {
					$creds                  = array();
					$creds['user_login']    = $_POST['user_login'];
					$creds['user_password'] = $_POST['user_password'];
					$creds['remember']      = true;
					$user                   = wp_signon( $creds, false );
					if ( is_wp_error( $user ) ) {
						learn_press_add_notice( $user->get_error_message(), 'error' );
						$success = false;
					}
				}
			}

			if ( $success && LP()->cart->needs_payment() ) {
				// Payment Method
				$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();

				if ( !isset( $available_gateways[$this->payment_method] ) ) {
					$this->payment_method = '';
					learn_press_add_notice( __( 'Invalid payment method.', 'learn_press' ), 'error' );
				} else {
					$this->payment_method = $available_gateways[$this->payment_method];
					$success = $this->payment_method->validate_fields();
				}
			} else {
				$available_gateways = array();
			}

			$order_id = $this->create_order();

			if( $success && $this->payment_method ){
				// TODO: checkout
				LP()->session->order_awaiting_payment = $order_id;
			}

		} catch ( Exception $e ) {
			if ( !empty( $e ) ) {
				learn_press_add_notice( $e->getMessage(), 'error' );
			}
		}
		$error_messages = '';
		if ( !$success ) {
			ob_start();
			learn_press_print_notices();
			$error_messages = ob_get_clean();
		}

		$result = array(
			'result'   => $success ? 'success' : 'fail',
			'messages' => $error_messages,
			'redirect' => ''
		);
		return $result;
	}

	/**
	 * Get unique instance for this object
	 *
	 * @return HB_Checkout
	 */
	static function instance() {
		if ( empty( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}


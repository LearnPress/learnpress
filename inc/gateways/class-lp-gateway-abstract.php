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
	 * Gateway feature constants.
	 */
	const FEATURE_ONE_TIME     = 'one-time';
	const FEATURE_SUBSCRIPTION = 'subscription';

	/**
	 * Shared subscription order meta keys.
	 */
	const META_SUBSCRIPTION_GATEWAY         = '_lp_subscription_gateway';
	const META_SUBSCRIPTION_ID              = '_lp_subscription_id';
	const META_SUBSCRIPTION_CUSTOMER_ID     = '_lp_subscription_customer_id';
	const META_SUBSCRIPTION_PRICE_ID        = '_lp_subscription_price_id';
	const META_SUBSCRIPTION_MODEL           = '_lp_subscription_model';
	const META_SUBSCRIPTION_QUANTITY        = '_lp_subscription_quantity';
	const META_SUBSCRIPTION_STATUS          = '_lp_subscription_status';
	const META_SUBSCRIPTION_PARENT_ORDER_ID = '_lp_subscription_parent_order_id';
	const META_SUBSCRIPTION_RENEWAL_ORDER_ID = '_lp_subscription_renewal_order_id';
	const META_SUBSCRIPTION_RENEWAL_KEY    = '_lp_subscription_renewal_key';
	const META_SUBSCRIPTION_LAST_EVENT_ID   = '_lp_subscription_last_event_id';
	const META_SUBSCRIPTION_EVENT_ID        = '_lp_subscription_event_id';
	const META_SUBSCRIPTION_MANAGE_URL      = '_lp_subscription_manage_url';

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

		$this->settings = LP_Settings::instance()->get_group( $this->id );
		$this->enabled  = $this->settings->get( 'enable', 'no' );

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
	 * Get feature list this gateway supports.
	 *
	 * @return array
	 */
	public function get_supported_features(): array {
		return array( self::FEATURE_ONE_TIME );
	}

	/**
	 * Check gateway support a feature.
	 *
	 * @param string $feature
	 *
	 * @return bool
	 */
	public function supports_feature( string $feature ): bool {
		$features = $this->get_supported_features();

		return in_array( $feature, $features, true );
	}

	/**
	 * Check if order should use subscription flow.
	 *
	 * @param LP_Order $order
	 *
	 * @return bool
	 */
	public function is_subscription_order( LP_Order $order ): bool {
		$is_subscription = false;
		if ( $this->supports_feature( self::FEATURE_SUBSCRIPTION ) ) {
			$is_subscription = (bool) apply_filters(
				'learn-press/gateway/subscription-order',
				false,
				$order,
				$this
			);
		}

		return $is_subscription;
	}

	/**
	 * Get subscription context for provider APIs.
	 *
	 * @param LP_Order $order
	 *
	 * @return array
	 */
	public function get_subscription_context( LP_Order $order ): array {
		$order_id = $order->get_id();

		$context = array(
			'price_id'    => get_post_meta( $order_id, self::META_SUBSCRIPTION_PRICE_ID, true ),
			'model'       => get_post_meta( $order_id, self::META_SUBSCRIPTION_MODEL, true ),
			'quantity'    => (int) get_post_meta( $order_id, self::META_SUBSCRIPTION_QUANTITY, true ),
			'success_url' => $this->get_return_url( $order ),
			'cancel_url'  => learn_press_get_page_link( 'checkout' ),
			'metadata'    => array(
				'lp_order_id'   => (string) $order_id,
				'lp_order_key'  => (string) $order->get_order_key(),
				'lp_gateway'    => $this->get_id(),
				'lp_user_id'    => (string) $order->get_user_id(),
				'lp_order_type' => 'subscription',
			),
		);

		if ( empty( $context['quantity'] ) ) {
			$context['quantity'] = 1;
		}

		return (array) apply_filters( 'learn-press/gateway/subscription-context', $context, $order, $this );
	}

	/**
	 * Validate subscription order constraints before provider checkout.
	 * Return true on success, WP_Error on failure.
	 *
	 * @param LP_Order $order
	 *
	 * @return bool|WP_Error
	 */
	public function validate_subscription_order( LP_Order $order ) {
		return apply_filters( 'learn-press/gateway/subscription/validate-order', true, $order, $this );
	}

	/**
	 * Normalize and validate the shared subscription checkout payload.
	 *
	 * This method is intentionally gateway-agnostic and is used by child gateways
	 * (e.g. Stripe/PayPal) before they build provider-specific API requests.
	 *
	 * Payload contract:
	 * - `price_id` (string, required): provider-side configured recurring price/plan id.
	 * - `model` (array): optional business context passed by integrators.
	 * - `quantity` (int): defaults to 1 when missing/invalid/zero.
	 * - `success_url` / `cancel_url` (string, required): absolute callback URLs.
	 * - `metadata` (array): optional identifiers (order/user/etc.) for reconciliation.
	 *
	 * @param array $data
	 *
	 * @return array|WP_Error Normalized payload array or validation error.
	 */
	protected function validate_subscription_payload( array $data ) {
		// Apply safe defaults to guarantee a stable input shape.
		$data = wp_parse_args(
			$data,
			array(
				'price_id'    => '',
				'model'       => array(),
				'quantity'    => 1,
				'success_url' => '',
				'cancel_url'  => '',
				'metadata'    => array(),
			)
		);

		// Scalar sanitation/coercion for fields commonly coming from request context.
		$data['price_id'] = sanitize_text_field( wp_unslash( (string) $data['price_id'] ) );
		$data['quantity'] = absint( $data['quantity'] );
		if ( empty( $data['quantity'] ) ) {
			$data['quantity'] = 1;
		}

		// URLs are stored in raw form for outbound provider API requests.
		$data['success_url'] = esc_url_raw( (string) $data['success_url'] );
		$data['cancel_url']  = esc_url_raw( (string) $data['cancel_url'] );

		// Defensive normalization for optional structured fields.
		$data['model']       = is_array( $data['model'] ) ? $data['model'] : array();
		$data['metadata']    = is_array( $data['metadata'] ) ? $data['metadata'] : array();

		// price_id is the minimum provider binding required for subscription checkout.
		if ( empty( $data['price_id'] ) ) {
			return new WP_Error( 'lp_subscription_missing_price_id', __( 'Missing subscription price id.', 'learnpress' ) );
		}

		// Redirect URLs are mandatory for provider-hosted checkout flows.
		if ( empty( $data['success_url'] ) || empty( $data['cancel_url'] ) ) {
			return new WP_Error( 'lp_subscription_missing_urls', __( 'Missing subscription return URLs.', 'learnpress' ) );
		}

		return $data;
	}

	/**
	 * Generic subscription checkout flow.
	 *
	 * @param array $data
	 *
	 * @return array|WP_Error
	 */
	public function pay_subscription( array $data ) {
		return new WP_Error(
			'lp_subscription_not_supported',
			sprintf( __( 'Gateway %s does not support subscription payment.', 'learnpress' ), $this->get_id() )
		);
	}

	/**
	 * Generic subscription webhook listener.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error
	 */
	public function listen_webhook_subscription( WP_REST_Request $request ) {
		return new WP_Error(
			'lp_subscription_webhook_not_supported',
			sprintf( __( 'Gateway %s does not support subscription webhook.', 'learnpress' ), $this->get_id() )
		);
	}

	/**
	 * Verify subscription webhook payload/signature with provider.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|WP_Error|object
	 */
	public function verify_subscription_webhook( WP_REST_Request $request ) {
		return new WP_Error(
			'lp_subscription_webhook_verify_not_supported',
			sprintf( __( 'Gateway %s does not support subscription webhook verification.', 'learnpress' ), $this->get_id() )
		);
	}

	/**
	 * Normalize provider webhook event to LP event payload.
	 *
	 * @param array|object $provider_event
	 *
	 * @return array
	 */
	public function normalize_subscription_event( $provider_event ): array {
		$event = array(
			'event_id'        => '',
			'event_type'      => 'ignored',
			'subscription_id' => '',
			'customer_id'     => '',
			'price_id'        => '',
			'parent_order_id' => 0,
			'transaction_id'  => '',
			'amount'          => 0,
			'currency'        => '',
			'status'          => '',
			'metadata'        => array(),
			'raw'             => $provider_event,
		);

		return (array) apply_filters( 'learn-press/gateway/subscription/event', $event, $provider_event, $this );
	}

	/**
	 * Get provider manage subscription URL for order.
	 *
	 * @param LP_Order $order
	 *
	 * @return string
	 */
	public function get_manage_subscription_url( LP_Order $order ): string {
		$url = get_post_meta( $order->get_id(), self::META_SUBSCRIPTION_MANAGE_URL, true );
		if ( ! is_string( $url ) ) {
			$url = '';
		}

		return (string) apply_filters( 'learn-press/gateway/subscription/manage-url', $url, $order, $this );
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

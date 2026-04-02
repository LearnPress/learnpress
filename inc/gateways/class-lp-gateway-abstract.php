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
	 * Child gateways can keep this generic and let integrations decide via
	 * filter `learn-press/gateway/subscription-order`.
	 *
	 * @param LP_Order $order
	 *
	 * @return bool
	 */
	public function is_subscription_order( LP_Order $order ): bool {
		$order_id         = $order->get_id();
		$saved_gateway_id = sanitize_key( (string) get_post_meta( $order_id, self::META_SUBSCRIPTION_GATEWAY, true ) );
		$saved_price_id   = sanitize_text_field( (string) get_post_meta( $order_id, self::META_SUBSCRIPTION_PRICE_ID, true ) );
		if ( ! empty( $saved_price_id ) && ( empty( $saved_gateway_id ) || $saved_gateway_id === $this->get_id() ) ) {
			return true;
		}

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
	 * Returns a gateway-agnostic payload that child gateways can pass to
	 * `pay_subscription()` after optional gateway-specific normalization.
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
	 * Persist subscription identifiers to order before payment execution.
	 *
	 * This allows payment methods to operate only on normalized parameters and
	 * avoid re-detecting custom integration attributes inside gateway code.
	 *
	 * @param LP_Order $order
	 * @param array $data
	 *
	 * @return void
	 */
	protected function persist_subscription_payment_identifiers( LP_Order $order, array $data ) {
		$order_id = $order->get_id();

		update_post_meta( $order_id, self::META_SUBSCRIPTION_GATEWAY, sanitize_key( (string) $this->get_id() ) );
		update_post_meta( $order_id, self::META_SUBSCRIPTION_PRICE_ID, sanitize_text_field( (string) ( $data['price_id'] ?? '' ) ) );
		update_post_meta( $order_id, self::META_SUBSCRIPTION_QUANTITY, max( 1, absint( $data['quantity'] ?? 1 ) ) );
		if ( isset( $data['model'] ) ) {
			update_post_meta( $order_id, self::META_SUBSCRIPTION_MODEL, is_array( $data['model'] ) ? $data['model'] : array() );
		}
	}

	/**
	 * Resolve normalized subscription payment params from order context.
	 *
	 * Behavior:
	 * - If order contains persisted subscription identifiers, treat it as
	 *   subscription payment.
	 * - If order is marked subscription by integration filter but has no
	 *   `price_id`, return a validation error early.
	 * - Otherwise return empty array (one-time payment flow).
	 *
	 * @param LP_Order $order
	 *
	 * @return array
	 * @throws Exception
	 */
	public function resolve_subscription_payment_data( LP_Order $order ): array {
		if ( ! $this->supports_feature( self::FEATURE_SUBSCRIPTION ) ) {
			return array();
		}

		$context = $this->get_subscription_context( $order );
		$context = wp_parse_args(
			$context,
			array(
				'price_id'    => '',
				'model'       => array(),
				'quantity'    => 1,
				'success_url' => '',
				'cancel_url'  => '',
				'metadata'    => array(),
			)
		);

		$context['price_id'] = sanitize_text_field( (string) $context['price_id'] );
		$context['quantity'] = max( 1, absint( $context['quantity'] ) );
		$context['model']    = is_array( $context['model'] ) ? $context['model'] : array();
		$context['metadata'] = is_array( $context['metadata'] ) ? $context['metadata'] : array();

		if ( ! empty( $context['price_id'] ) ) {
			$this->persist_subscription_payment_identifiers( $order, $context );
			return $context;
		}

		if ( $this->is_subscription_order( $order ) ) {
			throw new Exception( __( 'Missing subscription price id.', 'learnpress' ) );
		}

		return array();
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
	 * @return array Normalized payload array.
	 * @throws Exception
	 */
	protected function validate_subscription_payload( array $data ): array {
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
			throw new Exception( __( 'Missing subscription price id.', 'learnpress' ) );
		}

		// Redirect URLs are mandatory for provider-hosted checkout flows.
		if ( empty( $data['success_url'] ) || empty( $data['cancel_url'] ) ) {
			throw new Exception( __( 'Missing subscription return URLs.', 'learnpress' ) );
		}

		return $data;
	}

	/**
	 * Generic subscription checkout flow.
	 *
	 * Child gateways should override this method and return a payload with at
	 * least `status` and `redirect_url` on success.
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 */
	public function pay_subscription( array $data ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription payment.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Normalize and validate subscription plan creation payload.
	 *
	 * Payload contract:
	 * - `name` (string, required when `product_id` is empty)
	 * - `amount` (float, required, > 0)
	 * - `currency` (string, required)
	 * - `interval` (day|week|month|year)
	 * - `interval_count` (int, default 1)
	 * - `product_id` (string, optional)
	 * - `trial_days` (int, optional)
	 * - `description` (string, optional)
	 * - `metadata` (array, optional)
	 * - `model` (array, optional)
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function validate_subscription_plan_payload( array $data ): array {
		$data = wp_parse_args(
			$data,
			array(
				'name'           => '',
				'description'    => '',
				'amount'         => 0,
				'currency'       => learn_press_get_currency(),
				'interval'       => 'month',
				'interval_count' => 1,
				'product_id'     => '',
				'trial_days'     => 0,
				'metadata'       => array(),
				'model'          => array(),
			)
		);

		$data['name']           = sanitize_text_field( wp_unslash( (string) $data['name'] ) );
		$data['description']    = sanitize_text_field( wp_unslash( (string) $data['description'] ) );
		$data['amount']         = (float) $data['amount'];
		$data['currency']       = strtoupper( sanitize_text_field( wp_unslash( (string) $data['currency'] ) ) );
		$data['interval']       = strtolower( sanitize_key( (string) $data['interval'] ) );
		$data['interval_count'] = max( 1, absint( $data['interval_count'] ) );
		$data['product_id']     = sanitize_text_field( wp_unslash( (string) $data['product_id'] ) );
		$data['trial_days']     = absint( $data['trial_days'] );
		$data['metadata']       = is_array( $data['metadata'] ) ? $data['metadata'] : array();
		$data['model']          = is_array( $data['model'] ) ? $data['model'] : array();

		// Backward-compatible mapping when integrators pass plan fields via `model`.
		if ( ! empty( $data['model'] ) ) {
			if ( $data['amount'] <= 0 && isset( $data['model']['amount'] ) ) {
				$data['amount'] = (float) $data['model']['amount'];
			}
			if ( empty( $data['currency'] ) && ! empty( $data['model']['currency'] ) ) {
				$data['currency'] = strtoupper( sanitize_text_field( (string) $data['model']['currency'] ) );
			}
			if ( empty( $data['product_id'] ) && ! empty( $data['model']['product_id'] ) ) {
				$data['product_id'] = sanitize_text_field( (string) $data['model']['product_id'] );
			}
			if ( empty( $data['interval'] ) && ! empty( $data['model']['interval'] ) ) {
				$data['interval'] = strtolower( sanitize_key( (string) $data['model']['interval'] ) );
			}
			if ( $data['interval_count'] <= 1 && ! empty( $data['model']['interval_count'] ) ) {
				$data['interval_count'] = max( 1, absint( $data['model']['interval_count'] ) );
			}
			if ( $data['trial_days'] <= 0 && isset( $data['model']['trial_days'] ) ) {
				$data['trial_days'] = absint( $data['model']['trial_days'] );
			}
		}

		if ( empty( $data['product_id'] ) && empty( $data['name'] ) ) {
			throw new Exception( __( 'Missing subscription plan name.', 'learnpress' ) );
		}

		if ( $data['amount'] <= 0 ) {
			throw new Exception( __( 'Invalid subscription amount.', 'learnpress' ) );
		}

		if ( empty( $data['currency'] ) ) {
			throw new Exception( __( 'Missing subscription currency.', 'learnpress' ) );
		}

		$allowed_intervals = array( 'day', 'week', 'month', 'year' );
		if ( ! in_array( $data['interval'], $allowed_intervals, true ) ) {
			throw new Exception( __( 'Invalid subscription interval.', 'learnpress' ) );
		}

		return $data;
	}

	/**
	 * Generic subscription plan/price creation flow.
	 *
	 * Child gateways should override and return created provider identifiers,
	 * typically a `price_id`/`plan_id` to be used later by `pay_subscription()`.
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 */
	public function create_subscription_plan( array $data ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription plan creation.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Generic subscription webhook listener.
	 *
	 * Child gateways should override and orchestrate:
	 * verify -> normalize -> manager dispatch.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array
	 * @throws Exception
	 */
	public function listen_webhook_subscription( WP_REST_Request $request ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription webhook.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Verify subscription webhook payload/signature with provider.
	 *
	 * Child gateways should return verified provider event payload/object.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return array|object
	 * @throws Exception
	 */
	public function verify_subscription_webhook( WP_REST_Request $request ) {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription webhook verification.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Normalize provider webhook event to LP event payload.
	 *
	 * Child gateways should map provider-specific event types/fields into this
	 * canonical schema so Subscription Manager can process consistently.
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
	 * Child gateways can override when provider offers customer portal pages.
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

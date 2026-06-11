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
	 * Shared subscription order meta keys.
	 */
	const META_SUBSCRIPTION_ID                   = '_lp_subscription_id';
	const META_SUBSCRIPTION_CUSTOMER_ID          = '_lp_subscription_customer_id';
	const META_SUBSCRIPTION_PLAN_ID              = '_lp_subscription_plan_id';
	const META_SUBSCRIPTION_QUANTITY             = '_lp_subscription_quantity';
	const META_SUBSCRIPTION_STATUS               = '_lp_subscription_status';
	const META_SUBSCRIPTION_STATUS_TMP           = '_lp_subscription_status_tmp';
	const META_SUBSCRIPTION_RENEWAL_KEY          = '_lp_subscription_renewal_key';
	const META_SUBSCRIPTION_LAST_EVENT_ID        = '_lp_subscription_last_event_id';
	const META_SUBSCRIPTION_EVENT_ID             = '_lp_subscription_event_id';
	const META_SUBSCRIPTION_MANAGE_URL           = '_lp_subscription_manage_url';
	const META_SUBSCRIPTION_DATA_RECEIVER        = '_lp_subscription_data_receiver';
	const META_SUBSCRIPTION_DATA_PAYMENT_SUCCESS = '_lp_subscription_data_payment_success';

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
		/*
		if ( ! $this->admin_name ) {
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
	 * Check if order should use subscription flow.
	 *
	 * Integrations decide via filter `learn-press/gateway/subscription-order`.
	 *
	 * @param LP_Order $order
	 *
	 * @return bool
	 * @deprecated 4.3.8 Use is_data_for_payment_subscription() instead.
	 */
	/*public function is_subscription_order( LP_Order $order ): bool {

		$order_id       = $order->get_id();
		$saved_price_id = sanitize_text_field( (string) get_post_meta( $order_id, self::META_SUBSCRIPTION_PLAN_ID, true ) );
		if ( ! empty( $saved_price_id ) ) {
			return true;
		}
		$is_subscription = (bool) apply_filters(
			'learn-press/gateway/subscription-order',
			false,
			$order,
			$this
		);

		return $is_subscription;
	}*/

	/**
	 * Get subscription context for provider APIs.
	 *
	 * Returns a gateway-agnostic payload that child gateways can pass to
	 * `pay_subscription()` after optional gateway-specific normalization.
	 *
	 * @param LP_Order $order
	 *
	 * @return array
	 * @deprecated 4.3.8
	 */
	/*public function get_subscription_context( LP_Order $order ): array {
		$order_id = $order->get_id();

		$context = array(
			'price_id'    => get_post_meta( $order_id, self::META_SUBSCRIPTION_PLAN_ID, true ),
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
	}*/

	/**
	 * Persist subscription identifiers to order before payment execution.
	 *
	 * This allows payment methods to operate only on normalized parameters and
	 * avoid re-detecting custom integration attributes inside gateway code.
	 *
	 * @param LP_Order $order
	 * @param array    $data
	 *
	 * @return void
	 * @deprecated 4.3.8
	 */
	/*protected function persist_subscription_payment_identifiers( LP_Order $order, array $data ) {

		$order_id = $order->get_id();

		update_post_meta( $order_id, self::META_SUBSCRIPTION_PLAN_ID, sanitize_text_field( (string) ( $data['price_id'] ?? '' ) ) );
		update_post_meta( $order_id, self::META_SUBSCRIPTION_QUANTITY, max( 1, absint( $data['quantity'] ?? 1 ) ) );
	}*/
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
	 * @deprecated 4.3.8 Use is_data_for_payment_subscription() instead.
	 */
	/*public function resolve_subscription_payment_data( LP_Order $order ): array {
		$context = $this->get_subscription_context( $order );
		$context = wp_parse_args(
			$context,
			array(
				'price_id'    => '',
				'quantity'    => 1,
				'success_url' => '',
				'cancel_url'  => '',
				'metadata'    => array(),
			)
		);

		$context['price_id'] = sanitize_text_field( (string) $context['price_id'] );
		$context['quantity'] = max( 1, absint( $context['quantity'] ) );
		$context['metadata'] = is_array( $context['metadata'] ) ? $context['metadata'] : array();

		if ( ! empty( $context['price_id'] ) ) {
			$this->persist_subscription_payment_identifiers( $order, $context );
			return $context;
		}

		if ( $this->is_subscription_order( $order ) ) {
			throw new Exception( __( 'Missing subscription price id.', 'learnpress' ) );
		}

		return array();
	}*/

	/**
	 * Check data is type payment for subscription
	 * If LP order has data plan id, return data subscription
	 *
	 * @return false|array
	 * @since 4.3.8
	 * @version 1.0.0
	 */
	public function is_data_for_payment_subscription( LP_Order $lp_order ) {
		$data_subscription = [
			'plan_id'     => '',
			'success_url' => $this->get_return_url( $lp_order ),
			'cancel_url'  => LP_Helper::get_link_no_cache( learn_press_get_page_link( 'checkout' ) ),
		];

		// Check LP order has data plan id
		$plan_id = get_post_meta( $lp_order->get_id(), self::META_SUBSCRIPTION_PLAN_ID, true );
		if ( empty( $plan_id ) ) {
			return false;
		}

		$data_subscription['plan_id'] = $plan_id;

		return apply_filters(
			'learn-press/gateway/subscription-payment-data',
			$data_subscription,
			$lp_order,
			$this
		);
	}

	/**
	 * Normalize and validate the shared subscription checkout payload.
	 *
	 * This method is intentionally gateway-agnostic and is used by child gateways
	 * (e.g. Stripe/PayPal) before they build provider-specific API requests.
	 *
	 * Payload contract:
	 * - `price_id` (string, required): provider-side configured recurring price/plan id.
	 * - `quantity` (int): defaults to 1 when missing/invalid/zero.
	 * - `success_url` / `cancel_url` (string, required): absolute callback URLs.
	 * - `metadata` (array): optional identifiers (order/user/etc.) for reconciliation.
	 *
	 * @param array $data
	 *
	 * @return array Normalized payload array.
	 * @throws Exception
	 * @deprecated 4.3.8
	 */
	/*protected function validate_subscription_payload( array $data ): array {
		// Apply safe defaults to guarantee a stable input shape.
		$data = wp_parse_args(
			$data,
			array(
				'price_id'    => '',
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
		$data['metadata'] = is_array( $data['metadata'] ) ? $data['metadata'] : array();

		// price_id is the minimum provider binding required for subscription checkout.
		if ( empty( $data['price_id'] ) ) {
			throw new Exception( __( 'Missing subscription price id.', 'learnpress' ) );
		}

		// Redirect URLs are mandatory for provider-hosted checkout flows.
		if ( empty( $data['success_url'] ) || empty( $data['cancel_url'] ) ) {
			throw new Exception( __( 'Missing subscription return URLs.', 'learnpress' ) );
		}

		return $data;
	}*/

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
	/*public function pay_subscription( array $data ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription payment.', 'learnpress' ), $this->get_id() ) );
	}*/

	/**
	 * Generic subscription checkout flow.
	 *
	 * Child gateways should override this method and return a payload with at
	 * least `status` and `redirect_url` on success.
	 * $data required key: plan_id
	 *
	 * @param LP_Order $lp_order
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 * @since 4.3.8
	 * @version 1.0.0
	 */
	public function pay_via_subscription( LP_Order $lp_order, array $data ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription payment.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Normalize and validate shared plan-creation payload.
	 *
	 * Common payload contract:
	 * - `name` (string, required when `product_id` is empty)
	 * - `amount` (float, required, > 0)
	 * - `currency` (string, required)
	 * - `interval` (day|week|month|year)
	 * - `interval_count` (int, default 1)
	 * - `setup_fee` (float, optional, >= 0)
	 * - `product_id` (string, optional)
	 * - `metadata` (array, optional)
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 * @since 4.3.7
	 * @version 1.0.1
	 */
	protected function validate_data_plan_payload( array $data ): array {
		$data                   = wp_parse_args(
			$data,
			array(
				'name'           => '', // Name of plan and product (if product_id is empty)
				'amount'         => 0,
				'currency'       => learn_press_get_currency(),
				'interval'       => 'month',
				'interval_count' => 1,
				'setup_fee'      => 0,
				'product_id'     => '', // if empty, will create product, then create plan with product created
				'metadata'       => array(),
			)
		);
		$data['name']           = LP_Helper::sanitize_params_submitted( $data['name'] );
		$data['amount']         = (float) $data['amount'];
		$data['currency']       = LP_Helper::sanitize_params_submitted( $data['currency'], 'key' );
		$data['interval']       = LP_Helper::sanitize_params_submitted( $data['interval'], 'key' );
		$data['interval_count'] = max( 1, absint( $data['interval_count'] ) );
		$data['setup_fee']      = (float) $data['setup_fee'];
		$data['product_id']     = LP_Helper::sanitize_params_submitted( $data['product_id'] );
		$data['metadata']       = is_array( $data['metadata'] ) ? $data['metadata'] : array();

		if ( empty( $data['name'] ) ) {
			throw new Exception( __( 'Missing subscription plan name.', 'learnpress' ) );
		}

		if ( $data['amount'] <= 0 ) {
			throw new Exception( __( 'Invalid subscription amount.', 'learnpress' ) );
		}

		if ( $data['setup_fee'] < 0 ) {
			throw new Exception( __( 'Invalid subscription setup fee.', 'learnpress' ) );
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
	 * Create plan of Payment provider.
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 */
	public function create_plan( array $data ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription plan creation.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * List provider plans/prices with optional filtering/pagination args.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @throws Exception
	 */
	public function list_plans( array $args = array() ): array {

		throw new Exception( sprintf( __( 'Gateway %s does not support listing subscription plans.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Fetch provider plan/price details by plan id.
	 *
	 * Child gateways should override and return at least:
	 * - `plan`: raw provider response
	 * - `summary`: normalized fields used by integrations to compare updates
	 *   (amount/currency/interval/interval_count/setup_fee/status)
	 *
	 * @param string $plan_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function get_plan( string $plan_id ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support fetching subscription plan.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Update provider plan details by plan id.
	 *
	 * @uses LP_Gateway_Paypal::update_plan
	 *
	 * @param string $plan_id
	 * @param array  $data
	 *
	 * @return array
	 * @throws Exception
	 */
	public function update_plan( string $plan_id, array $data ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support updating subscription plan.', 'learnpress' ), $this->get_id() ) );
	}

	/**
	 * Delete/deactivate provider plan by plan id.
	 *
	 * @param string $plan_id
	 *
	 * @return array
	 * @throws Exception
	 */
	public function delete_plan( string $plan_id ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support deleting subscription plan.', 'learnpress' ), $this->get_id() ) );
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
	 * @deprecated 4.3.8 Use capture_subscription_webhook instead.
	 */
	/*public function listen_webhook_subscription( WP_REST_Request $request ): array {
		throw new Exception( sprintf( __( 'Gateway %s does not support subscription webhook.', 'learnpress' ), $this->get_id() ) );
	}*/

	/**
	 * Receive subscription webhook from provider.
	 *
	 * @throws Exception
	 *
	 * @since 4.3.7
	 * @version 1.0.0
	 */
	public function capture_subscription_webhook( WP_REST_Request $request ) {
		throw new Exception(
			sprintf(
				__( 'Gateway %s does not support subscription webhook.', 'learnpress' ),
				$this->get_id()
			)
		);
	}

	/**
	 * Verify subscription webhook payload/signature with provider.
	 *
	 * Child gateways should return verified provider event payload/object.
	 *
	 * @param array $webhook_data Generic webhook data extracted from transport layer.
	 *
	 * @return array|object
	 * @throws Exception
	 * @deprecated 4.3.8
	 */
	/*public function verify_subscription_webhook( array $webhook_data ) {

		throw new Exception( sprintf( __( 'Gateway %s does not support subscription webhook verification.', 'learnpress' ), $this->get_id() ) );
	}*/

	/**
	 * Build normalized webhook data array from transport-specific REST request.
	 *
	 * Contract:
	 * - raw_body: raw payload string (for signature verification like Stripe).
	 * - body: decoded JSON array when $decode_body is true, otherwise null.
	 * - headers: map of required header keys (lowercase) to raw header values.
	 *
	 * @param WP_REST_Request $request
	 * @param array           $required_headers
	 * @param bool            $decode_body
	 *
	 * @return array
	 * @deprecated 4.3.8
	 */
	/*protected function build_webhook_data_from_request( WP_REST_Request $request, array $required_headers = array(), bool $decode_body = true ): array {

		$raw_body = (string) $request->get_body();
		$headers  = array();

		foreach ( $required_headers as $required_header ) {
			$required_header             = strtolower( sanitize_key( (string) $required_header ) );
			$headers[ $required_header ] = (string) $request->get_header( $required_header );
		}

		$body = null;
		if ( $decode_body ) {
			$body = LP_Helper::json_decode( $raw_body, true );
		}

		return array(
			'raw_body' => $raw_body,
			'body'     => is_array( $body ) ? $body : null,
			'headers'  => $headers,
		);
	}*/

	/**
	 * Validate normalized webhook payload contract before provider verification.
	 *
	 * This centralizes fail-fast checks so each gateway does not re-implement
	 * required key/header validation and accidentally diverge key names.
	 *
	 * @param array $webhook_data
	 * @param array $required_top_level_keys Allowed: raw_body, body, headers.
	 * @param array $required_headers
	 *
	 * @return void
	 * @throws Exception
	 * @deprecated 4.3.8
	 */
	//  protected function validate_webhook_data_contract( array $webhook_data, array $required_top_level_keys = array(), array $required_headers = array() ) {
	//
	//      $missing = array();
	//
	//      foreach ( $required_top_level_keys as $required_key ) {
	//          $required_key = sanitize_key( (string) $required_key );
	//
	//          switch ( $required_key ) {
	//              case 'raw_body':
	//                  if ( empty( $webhook_data['raw_body'] ) || ! is_string( $webhook_data['raw_body'] ) ) {
	//                      $missing[] = 'raw_body';
	//                  }
	//                  break;
	//              case 'body':
	//                  if ( empty( $webhook_data['body'] ) || ! is_array( $webhook_data['body'] ) ) {
	//                      $missing[] = 'body';
	//                  }
	//                  break;
	//              case 'headers':
	//                  if ( ! isset( $webhook_data['headers'] ) || ! is_array( $webhook_data['headers'] ) ) {
	//                      $missing[] = 'headers';
	//                  }
	//                  break;
	//          }
	//      }
	//
	//      $headers_map = is_array( $webhook_data['headers'] ?? null ) ? $webhook_data['headers'] : array();
	//      foreach ( $required_headers as $required_header ) {
	//          $required_header = strtolower( sanitize_key( (string) $required_header ) );
	//          $header_value    = sanitize_text_field( (string) ( $headers_map[ $required_header ] ?? '' ) );
	//          if ( '' === $header_value ) {
	//              $missing[] = 'headers.' . $required_header;
	//          }
	//      }
	//
	//      if ( ! empty( $missing ) ) {
	//              throw new Exception(
	//                  sprintf(
	//                  /* translators: %s: comma separated required webhook fields. */
	//                      __( 'Invalid webhook request data: missing %s.', 'learnpress' ),
	//                      implode( ', ', array_unique( $missing ) )
	//                  ),
	//                  400
	//              );
	//      }
	//  }

	/**
	 * Normalize provider webhook event to LP event payload.
	 *
	 * Child gateways should map provider-specific event types/fields into this
	 * canonical schema so Subscription Manager can process consistently.
	 *
	 * @param array|object $provider_event
	 *
	 * @return array
	 * @deprecated 4.3.8 Use normalize_subscription_data instead.
	 */
	/*public function normalize_subscription_event( $provider_event ): array {
		$event = array(
			'event_id'        => '',
			'event_type'      => '',
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
	}*/

	/**
	 * Define key of system LearnPress for webhook data.
	 *
	 * @param array $webhook_data [lp_order_id, plan_id, subscription_id, subscription_status]
	 *
	 * @return void
	 */
	final public function normalize_subscription_data( array &$webhook_data = [] ) {
		$webhook_data = array_merge(
			array(
				'lp_order_id'            => 0,
				'lp_plan_id'             => '', // Plan id of payment, not membership plan id.
				'lp_subscription_id'     => '', // Subscription id of payment plan id.
				'lp_subscription_status' => '', // Subscription status of payment plan id.
			),
			$webhook_data
		);
	}

	/**
	 * Process subscription by status.
	 *
	 * Status flow:
	 * - trial/activated: triggers once on the parent LP Order (first payment).
	 * - renewed: triggers on each renewal, creating a child LP Order.
	 * - created/expired/canceled/suspended: logged, do_action only, no order state change.
	 *
	 * @param LP_Order $lp_order
	 * @param string $lp_subscription_status_set_to_handle Status you want set to handle by case
	 * @param array $webhook_data
	 *
	 * @return void
	 * @throws Exception
	 * @since 4.3.7
	 * @version 1.0.0
	 */
	final public function process_subscription_by_status(
		$lp_order,
		string $lp_subscription_status_set_to_handle,
		array $webhook_data = []
	) {
		LP_Debug::log_to_comment( 'Progress wit data: ' . json_encode( $webhook_data, JSON_UNESCAPED_UNICODE ) );

		switch ( $lp_subscription_status_set_to_handle ) {
			case LP_Subscription_Manager::STATUS_TRIAL:
				// For trial, update LP order to complete, set subscription status to trial
				$this->process_subscription_when_payment_first( $lp_order, LP_Subscription_Manager::STATUS_TRIAL, $webhook_data );

				// Set user is using plan trial
				$order_user_ids = $lp_order->get_users();
				$plan_id        = get_post_meta( $lp_order->get_id(), self::META_SUBSCRIPTION_PLAN_ID, true );
				foreach ( $order_user_ids as $user_id ) {
					update_user_meta( $user_id, 'user_plan_trial', $plan_id );
				}

				$lp_order->add_note(
					sprintf(
						'LP Order: %s %s: %s. %s. %s, %s',
						sprintf(
							'<a href="%s">%s</a>',
							$lp_order->get_edit_link(),
							$lp_order->get_order_number()
						),
						__( 'Started trialing created at', 'learnpress' ),
						$webhook_data['create_time'] ?? '',
						sprintf( '%s %s', __( 'Next billing time', 'learnpress' ), $webhook_data['next_billing_time'] ?? '' ),
						sprintf(
							__( 'Subscription ID: %s', 'learnpress' ),
							$webhook_data['lp_subscription_id']
						),
						sprintf(
							__( 'Plan ID: %s', 'learnpress' ),
							$webhook_data['lp_plan_id']
						)
					)
				);
				do_action( 'learn-press/subscription/trial', $this, $lp_order, $webhook_data );
				break;
			case LP_Subscription_Manager::STATUS_ACTIVATED:
				// For payment plan first success, update LP order to complete, set subscription status to activated
				$this->process_subscription_when_payment_first( $lp_order, $lp_subscription_status_set_to_handle, $webhook_data );
				$lp_order->add_note(
					sprintf(
						'LP Order: %s %s: %s. %s. %s, %s',
						sprintf(
							'<a href="%s">%s</a>',
							$lp_order->get_edit_link(),
							$lp_order->get_order_number()
						),
						__( 'Activated created at', 'learnpress' ),
						$webhook_data['create_time'] ?? '',
						sprintf( '%s: %s', __( 'Next billing time', 'learnpress' ), $webhook_data['next_billing_time'] ?? '' ),
						sprintf(
							__( 'Subscription ID: %s', 'learnpress' ),
							$webhook_data['lp_subscription_id']
						),
						sprintf(
							__( 'Plan ID: %s', 'learnpress' ),
							$webhook_data['lp_plan_id']
						)
					)
				);
				do_action( 'learn-press/subscription/active', $this, $lp_order, $webhook_data );
				break;
			case LP_Subscription_Manager::STATUS_RENEWED:
				// For payment plan renew success, parent order is completed and payment renew success
				$this->process_subscription_when_payment_renew_success( $lp_order, $webhook_data );
				$lp_order->add_note(
					sprintf(
						'LP Order: %s %s: %s. %s. %s, %s',
						sprintf(
							'<a href="%s">%s</a>',
							$lp_order->get_edit_link(),
							$lp_order->get_order_number()
						),
						__( 'Renew created at', 'learnpress' ),
						$webhook_data['create_time'] ?? '',
						sprintf( '%s: %s', __( 'Next billing time', 'learnpress' ), $webhook_data['next_billing_time'] ?? '' ),
						sprintf(
							__( 'Subscription ID: %s', 'learnpress' ),
							$webhook_data['lp_subscription_id']
						),
						sprintf(
							__( 'Plan ID: %s', 'learnpress' ),
							$webhook_data['lp_plan_id']
						)
					)
				);
				do_action( 'learn-press/subscription/renew', $this, $lp_order, $webhook_data );
				break;
			case LP_Subscription_Manager::STATUS_EXPIRED:
				// For payment plan expired, not impact orders
				$lp_order->add_note(
					sprintf(
						'LP Order: %s %s: %s. %s, %s',
						sprintf(
							'<a href="%s">%s</a>',
							$lp_order->get_edit_link(),
							$lp_order->get_order_number()
						),
						__( 'Expired created at', 'learnpress' ),
						$webhook_data['create_time'] ?? '',
						sprintf(
							__( 'Subscription ID: %s', 'learnpress' ),
							$webhook_data['lp_subscription_id']
						),
						sprintf(
							__( 'Plan ID: %s', 'learnpress' ),
							$webhook_data['lp_plan_id']
						)
					)
				);
				do_action( 'learn-press/subscription/expired', $this, $lp_order, $webhook_data );
				break;
			case LP_Subscription_Manager::STATUS_SUSPENDED:
				// For payment plan suspended, not impact orders
				$lp_order->add_note(
					sprintf(
						'LP Order: %s %s: %s. %s, %s',
						sprintf(
							'<a href="%s">%s</a>',
							$lp_order->get_edit_link(),
							$lp_order->get_order_number()
						),
						__( 'Suspended at', 'learnpress' ),
						$webhook_data['create_time'] ?? '',
						sprintf(
							__( 'Subscription ID: %s', 'learnpress' ),
							$webhook_data['lp_subscription_id']
						),
						sprintf(
							__( 'Plan ID: %s', 'learnpress' ),
							$webhook_data['lp_plan_id']
						)
					)
				);
				do_action( 'learn-press/subscription/suspended', $this, $lp_order, $webhook_data );
				break;
			case LP_Subscription_Manager::STATUS_CANCELLED:
				// For payment plan canceled, not impact orders
				$lp_order->add_note(
					sprintf(
						'LP Order: %s %s: %s. %s, %s',
						sprintf(
							'<a href="%s">%s</a>',
							$lp_order->get_edit_link(),
							$lp_order->get_order_number()
						),
						__( 'Canceled at', 'learnpress' ),
						$webhook_data['create_time'] ?? '',
						sprintf(
							__( 'Subscription ID: %s', 'learnpress' ),
							$webhook_data['lp_subscription_id']
						),
						sprintf(
							__( 'Plan ID: %s', 'learnpress' ),
							$webhook_data['lp_plan_id']
						)
					)
				);
				do_action( 'learn-press/subscription/cancelled', $this, $lp_order, $webhook_data );
				break;
		}

		do_action( 'learn-press/subscription/process', $this, $lp_order, $webhook_data );
	}

	/**
	 * Process order when subscription payment first.
	 * Update status of order parent to completed
	 * Save META_SUBSCRIPTION_STATUS, lp_subscription_amount, lp_subscription_currency
	 *
	 * @since 4.3.7
	 * @version 1.0.1
	 */
	private function process_subscription_when_payment_first(
		LP_Order $lp_order,
		string $lp_subscription_status_set_to_handle,
		$webhook_data
	) {
		$lp_subscription_amount   = $webhook_data['lp_subscription_amount'] ?? 0;
		$lp_subscription_currency = $webhook_data['lp_subscription_currency'] ?? '';
		$lp_order->update_status( LP_ORDER_COMPLETED );
		update_post_meta(
			$lp_order->get_id(),
			self::META_SUBSCRIPTION_STATUS,
			$lp_subscription_status_set_to_handle
		);
		update_post_meta(
			$lp_order->get_id(),
			'lp_subscription_amount',
			$lp_subscription_amount
		);
		update_post_meta(
			$lp_order->get_id(),
			'lp_subscription_currency',
			$lp_subscription_currency
		);
		update_post_meta(
			$lp_order->get_id(),
			self::META_SUBSCRIPTION_DATA_RECEIVER,
			wp_json_encode( $webhook_data, JSON_UNESCAPED_UNICODE )
		);

		do_action( 'learn-press/subscription/order/success', $lp_order, $webhook_data );
	}

	/**
	 * Process order when payment recurring success.
	 * Create new order child
	 * Save META_SUBSCRIPTION_DATA_RECEIVER, lp_subscription_amount, lp_subscription_currency
	 *
	 * @throws Exception
	 * @since 4.3.7
	 * @version 1.0.1
	 */
	private function process_subscription_when_payment_renew_success( LP_Order $lp_order_parent, $webhook_data ) {
		$lp_subscription_amount   = $webhook_data['lp_subscription_amount'] ?? 0;
		$lp_subscription_currency = $webhook_data['lp_subscription_currency'] ?? '';

		// Create new Order child
		$order_renew = new LP_Order();
		$order_renew->set_parent_id( $lp_order_parent->get_id() );
		$order_renew->set_user_id( $lp_order_parent->get_user_id() );
		$order_renew->set_checkout_email( $lp_order_parent->get_checkout_email() );
		$order_renew->set_status( LP_ORDER_COMPLETED );
		$order_renew->set_created_via( 'subscription' );
		$order_renew->set_currency( $lp_subscription_currency ?? $lp_order_parent->get_currency() );
		$order_renew->set_total( $lp_subscription_amount );
		$order_renew->set_subtotal( $lp_subscription_amount );
		$order_renew->set_data( 'payment_method', $lp_order_parent->get_data( 'payment_method' ) );
		$order_renew->set_data( 'payment_method_title', $lp_order_parent->get_payment_method_title() );
		$order_renew->save();

		//error_log( 'renew ' . json_encode( $webhook_data, JSON_UNESCAPED_UNICODE ) );
		// Add item to order renew
		foreach ( $lp_order_parent->get_all_items() as $item ) {
			$item['subtotal'] = $lp_subscription_amount;
			$item['total']    = $lp_subscription_amount;
			$order_renew->add_item( $item );
		}

		update_post_meta(
			$order_renew->get_id(),
			self::META_SUBSCRIPTION_DATA_RECEIVER,
			wp_json_encode( $webhook_data, JSON_UNESCAPED_UNICODE )
		);
		update_post_meta(
			$order_renew->get_id(),
			'lp_subscription_amount',
			$lp_subscription_amount
		);
		update_post_meta(
			$order_renew->get_id(),
			'lp_subscription_currency',
			$lp_subscription_currency
		);

		do_action( 'learn-press/subscription/order/renew-success', $order_renew, $webhook_data );
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
		return apply_filters( 'learn_press_gateway_payment_form', '', $this );
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

	/**
	 * @param string $prop
	 *
	 * @deprecated 4.3.9
	 */
	public function __get( $prop ) {
		_deprecated_function( __METHOD__, '4.3.9' );
		return false;
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
	 * @return string
	 */
	public function __toString() {
		return $this->method_title;
	}

	/**
	 * Refund payment
	 *
	 *
	 * @since 4.4.0
	 * @version 1.0.0
	 * @return void
	 * @throws Exception
	 */
	public function refund( $lp_order, float $amount = 0, string $note = '' ) {
		throw new Exception( __( 'This gateway does not support refund.', 'learnpress' ) );
	}
}

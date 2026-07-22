<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

if ( ! class_exists( '\\LP_Order', false ) ) {
	eval(
		'class LP_Order {
			private $id;
			private $order_key;
			private $user_id;
			public $data = array();

			public function __construct( $id = 0, $order_key = "", $user_id = 0 ) {
				$this->id        = (int) $id;
				$this->order_key = (string) $order_key;
				$this->user_id   = (int) $user_id;
			}

			public function get_id() {
				return $this->id;
			}

			public function get_order_key() {
				return $this->order_key;
			}

			public function get_user_id() {
				return $this->user_id;
			}

			public function get_checkout_order_received_url() {
				return "https://example.com/order-received/" . $this->id;
			}

			public function get_order_number() {
				return "ORDER-" . $this->id;
			}

			public function get_total() {
				return 12.34;
			}

			public function get_checkout_email() {
				return "student@example.com";
			}

			public function set_data( $key, $value ) {
				$this->data[ $key ] = $value;
			}
		}'
	);
}

if ( ! class_exists( '\\WP_REST_Request', false ) ) {
	eval(
		'class WP_REST_Request {
			private $body = "";
			private $headers = array();

			public function __construct( $method = "POST" ) {}

			public function set_body( $body ) {
				$this->body = (string) $body;
			}

			public function get_body() {
				return $this->body;
			}

			public function set_header( $key, $value ) {
				$this->headers[ strtolower( (string) $key ) ] = (string) $value;
			}

			public function get_header( $key ) {
				$key = strtolower( (string) $key );
				return $this->headers[ $key ] ?? "";
			}
		}'
	);
}

if ( ! class_exists( '\\LP_Stripe_Test_Session', false ) ) {
	eval(
		'class LP_Stripe_Test_Session {
			public $values = array();

			public function get( $key, $default = null ) {
				return $this->values[ $key ] ?? $default;
			}

			public function set( $key, $value ) {
				$this->values[ $key ] = $value;
			}

			public function remove( $key ) {
				unset( $this->values[ $key ] );
			}
		}'
	);
}

if ( ! class_exists( '\\LearnPress', false ) ) {
	eval(
		'class LearnPress {
			public static $instance = null;
			public $session;
			public $cart;

			public static function instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}

				return self::$instance;
			}
		}'
	);
}

if ( ! class_exists( '\\LP_Helper', false ) ) {
	eval(
		'class LP_Helper {
			public static function get_link_no_cache( $url ) {
				return (string) $url;
			}

			public static function sanitize_params_submitted( $value, $type = "text" ) {
				return "key" === $type ? strtolower( preg_replace( "/[^a-z0-9_\\-]/i", "", (string) $value ) ) : trim( (string) $value );
			}
		}'
	);
}

if ( ! class_exists( '\\LP_Settings', false ) ) {
	eval(
		'class LP_Settings {
			private static $instance = null;
			public static $options = array();

			public static function instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}

			public function get_group( $id ) {
				return new class {
					public function get( $key, $default = null ) {
						return $default;
					}
				};
			}

			public function get( $key, $default = null ) {
				return $default;
			}
		}'
	);
}

if ( ! defined( 'LP_ORDER_PROCESSING' ) ) {
	define( 'LP_ORDER_PROCESSING', 'processing' );
}

if ( ! defined( 'LP_ORDER_COMPLETED' ) ) {
	define( 'LP_ORDER_COMPLETED', 'completed' );
}

if ( ! defined( 'LP_ORDER_CPT' ) ) {
	define( 'LP_ORDER_CPT', 'lp_order' );
}

if ( ! defined( 'LP_ADDON_STRIPE_PAYMENT_FILE' ) ) {
	define( 'LP_ADDON_STRIPE_PAYMENT_FILE', dirname( __DIR__, 4 ) . '/learnpress-stripe/learnpress-stripe.php' );
}

if ( ! defined( 'LP_ADDON_STRIPE_PAYMENT_PATH' ) ) {
	define( 'LP_ADDON_STRIPE_PAYMENT_PATH', dirname( __DIR__, 4 ) . '/learnpress-stripe' );
}

if ( ! defined( 'LP_ADDON_STRIPE_PAYMENT_URL' ) ) {
	define( 'LP_ADDON_STRIPE_PAYMENT_URL', 'https://example.com/wp-content/plugins/learnpress-stripe/' );
}

if ( ! class_exists( '\\LP_Abstract_Settings', false ) ) {
	require_once dirname( __DIR__, 3 ) . '/inc/abstract-settings.php';
}

if ( ! class_exists( '\\LP_Gateway_Abstract', false ) ) {
	require_once dirname( __DIR__, 3 ) . '/inc/gateways/class-lp-gateway-abstract.php';
}

if ( ! class_exists( '\\LP_Subscription_Manager', false ) ) {
	require_once dirname( __DIR__, 3 ) . '/inc/gateways/subscriptions/class-lp-subscription-manager.php';
}

if ( ! class_exists( '\\Stripe\\Checkout\\Session', false ) ) {
	eval(
		'namespace Stripe\\Checkout;
		class Session {
			public const PAYMENT_STATUS_PAID = "paid";
		}'
	);
}

if ( ! class_exists( '\\Stripe\\Exception\\ApiErrorException', false ) ) {
	eval(
		'namespace Stripe\\Exception;
		class ApiErrorException extends \\Exception {}'
	);
}

if ( ! class_exists( '\\Stripe\\Exception\\SignatureVerificationException', false ) ) {
	eval(
		'namespace Stripe\\Exception;
		class SignatureVerificationException extends \\Exception {}'
	);
}

if ( ! class_exists( '\\Stripe\\StripeClient', false ) ) {
	eval(
		'namespace Stripe;
		class StripeClient {
			public function __construct( $api_key = null ) {}
		}'
	);
}

if ( ! class_exists( '\\Stripe\\Webhook', false ) ) {
	eval(
		'namespace Stripe;
		class Webhook {
			public static function constructEvent( $payload, $signature, $secret ) {
				throw new \\UnexpectedValueException( "Stripe webhook verification is not available in this unit stub." );
			}
		}'
	);
}

if ( ! class_exists( '\\LP_Gateway_Stripe', false ) ) {
	require_once dirname( __DIR__, 4 ) . '/learnpress-stripe/inc/class-lp-gateway-stripe.php';
}

if ( ! class_exists( '\\LP_Gateway_Stripe_Test_Double', false ) ) {
	eval(
		'class LP_Gateway_Stripe_Test_Double extends LP_Gateway_Stripe {
			public $direct_payment = false;
			public $subscription_data = false;
			public $subscription_response = array();
			public $subscription_call = array();
			public $payment_intent_updates = array();
			public $stripe_checkout_url = "https://checkout.stripe.test/session";
			public $return_url = "https://example.com/order-return";

			public function __construct() {}

			public function is_direct_pay_on_stripe_page(): bool {
				return (bool) $this->direct_payment;
			}

			public function is_data_for_payment_subscription( LP_Order $lp_order ) {
				return $this->subscription_data;
			}

			public function pay_via_subscription( LP_Order $lp_order, array $data ): array {
				$this->subscription_call = array(
					"order_id" => $lp_order->get_id(),
					"data" => $data,
				);

				return $this->subscription_response;
			}

			public function call_parent_pay_via_subscription( LP_Order $lp_order, array $data ): array {
				return parent::pay_via_subscription( $lp_order, $data );
			}

			public function get_url_payment_on_stripe_page( LP_Order $order ) {
				return $this->stripe_checkout_url;
			}

			public function update_payment_intent( string $pi, int $order_id ) {
				$this->payment_intent_updates[] = array(
					"payment_intent" => $pi,
					"order_id" => $order_id,
				);
			}

			public function get_return_url( $order = null ) {
				return $this->return_url;
			}

			public function get_stripe_currency_rules(): array {
				return array(
					"zero-decimal" => array( "JPY" ),
					"three-decimal" => array( "BHD" ),
					"special-case" => array(),
				);
			}

			public function set_secret_key_for_test( string $secret_key ): void {
				$this->secret_key = $secret_key;
			}

			public function set_webhook_secret_for_test( string $webhook_secret ): void {
				$this->webhook_secret = $webhook_secret;
			}

			public function call_build_webhook_data( LP_Order $order, string $subscription_id, string $price_id, string $status, array $data = array() ): array {
				return $this->build_webhook_data( $order, $subscription_id, $price_id, $status, $data );
			}

			public function call_map_stripe_subscription_status_to_lp( string $status ): string {
				return $this->map_stripe_subscription_status_to_lp( $status );
			}

			public function call_build_stripe_price_summary( array $price ): array {
				return $this->build_stripe_price_summary( $price );
			}

			public function call_calculate_stripe_amount_for_currency( float $amount, string $currency ) {
				return $this->calculate_stripe_amount_for_currency( $amount, $currency );
			}
		}'
	);
}

class LPGatewayStripeTest extends BrainMonkeyTestCase {

	private string $currency = 'USD';

	protected function setUp(): void {
		parent::setUp();

		$self = $this;

		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'sanitize_title' )->alias(
			function ( $value ): string {
				return strtolower( str_replace( ' ', '-', (string) $value ) );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			function ( $value ): string {
				return trim( (string) $value );
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			function ( $value ): string {
				return strtolower( preg_replace( '/[^a-zA-Z0-9_\\-]/', '', (string) $value ) );
			}
		);
		Functions\when( 'absint' )->alias(
			function ( $value ): int {
				return abs( (int) $value );
			}
		);
		Functions\when( 'learn_press_get_currency' )->alias(
			function () use ( $self ): string {
				return $self->currency;
			}
		);
		Functions\when( 'learn_press_get_page_link' )->justReturn( 'https://example.com/checkout' );
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'esc_url' )->returnArg( 1 );
		Functions\when( 'is_email' )->alias(
			function ( $email ): bool {
				return false !== filter_var( $email, FILTER_VALIDATE_EMAIL );
			}
		);
		Functions\when( 'wp_strip_all_tags' )->alias(
			function ( $value ): string {
				return strip_tags( (string) $value );
			}
		);
		Functions\when( 'wp_json_encode' )->alias(
			function ( $value ): string {
				return (string) json_encode( $value );
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			function ( $key, $value, $url = '' ): string {
				if ( is_array( $key ) ) {
					return (string) $url . '?' . http_build_query( $key );
				}

				return (string) $url . '?' . rawurlencode( (string) $key ) . '=' . rawurlencode( (string) $value );
			}
		);
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_posts' )->justReturn( array() );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'learn_press_get_order' )->justReturn( false );

		\LearnPress::$instance          = new \LearnPress();
		\LearnPress::$instance->session = new \LP_Stripe_Test_Session();

		$this->reset_stripe_singleton();
	}

	protected function tearDown(): void {
		$this->reset_stripe_singleton();
		parent::tearDown();
	}

	private function new_gateway(): \LP_Gateway_Stripe_Test_Double {
		$gateway                  = new \LP_Gateway_Stripe_Test_Double();
		$gateway->id              = 'stripe';
		$gateway->enabled         = 'yes';
		$gateway->test_mode       = 'no';
		$gateway->publish_key     = 'pk_live_123';
		$gateway->test_publish_key = 'pk_test_123';
		$gateway->test_secret_key = 'sk_test_123';
		$gateway->enable_subscriptions = 'yes';
		$gateway->set_secret_key_for_test( 'sk_live_123' );
		$gateway->set_webhook_secret_for_test( 'whsec_live_123' );

		return $gateway;
	}

	private function set_stripe_singleton( \LP_Gateway_Stripe $gateway ): void {
		$reflection = new \ReflectionClass( '\\LP_Gateway_Stripe' );
		$property   = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, $gateway );
	}

	private function reset_stripe_singleton(): void {
		if ( ! class_exists( '\\LP_Gateway_Stripe', false ) ) {
			return;
		}

		$reflection = new \ReflectionClass( '\\LP_Gateway_Stripe' );
		if ( ! $reflection->hasProperty( 'instance' ) ) {
			return;
		}

		$property = $reflection->getProperty( 'instance' );
		$property->setAccessible( true );
		$property->setValue( null, null );
	}

	public function test_stripe_available_requires_enabled_gateway_and_mode_keys(): void {
		$gateway          = $this->new_gateway();
		$gateway->enabled = 'no';
		$this->assertFalse( $gateway->stripe_available() );

		$gateway->enabled   = 'yes';
		$gateway->test_mode = 'yes';
		$gateway->test_secret_key = '';
		$this->assertFalse( $gateway->stripe_available() );

		$gateway->test_secret_key = 'sk_test_123';
		$this->assertTrue( $gateway->stripe_available() );

		$gateway->test_mode = 'no';
		$gateway->publish_key = '';
		$this->assertFalse( $gateway->stripe_available() );
	}

	public function test_process_payment_uses_subscription_checkout_when_subscription_data_exists(): void {
		$order   = new \LP_Order( 321, 'order_321', 7 );
		$gateway = $this->new_gateway();
		$gateway->subscription_data = array(
			'plan_id' => 'price_monthly_001',
		);
		$gateway->subscription_response = array(
			'id'           => 'cs_sub_001',
			'redirect_url' => 'https://checkout.stripe.test/subscription',
		);

		Functions\when( 'learn_press_get_order' )->justReturn( $order );

		$result = $gateway->process_payment( 321 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://checkout.stripe.test/subscription', $result['redirect'] );
		$this->assertSame( 321, $gateway->subscription_call['order_id'] );
		$this->assertSame( 'price_monthly_001', $gateway->subscription_call['data']['plan_id'] );
	}

	public function test_process_payment_returns_processing_for_normal_payment_intent_flow(): void {
		$order   = new \LP_Order( 322, 'order_322', 8 );
		$gateway = $this->new_gateway();
		$gateway->direct_payment    = false;
		$gateway->subscription_data = false;
		$this->set_stripe_singleton( $gateway );

		\LearnPress::instance()->session->set( 'stripe_awaiting_payment_intent', (object) array( 'id' => 'pi_123' ) );
		Functions\when( 'learn_press_get_order' )->justReturn( $order );

		$result = $gateway->process_payment( 322 );

		$this->assertSame( LP_ORDER_PROCESSING, $result['result'] );
		$this->assertSame( 'https://example.com/order-return?lp-stripe-confirm-payment=1', $result['redirect'] );
		$this->assertSame(
			array(
				array(
					'payment_intent' => 'pi_123',
					'order_id'       => 322,
				),
			),
			$gateway->payment_intent_updates
		);
	}

	public function test_process_payment_returns_redirect_for_direct_stripe_checkout_flow(): void {
		$order   = new \LP_Order( 323, 'order_323', 9 );
		$gateway = $this->new_gateway();
		$gateway->direct_payment    = true;
		$gateway->subscription_data = false;
		$gateway->stripe_checkout_url = 'https://checkout.stripe.test/direct';
		$this->set_stripe_singleton( $gateway );

		Functions\when( 'learn_press_get_order' )->justReturn( $order );

		$result = $gateway->process_payment( 323 );

		$this->assertSame( 'success', $result['result'] );
		$this->assertSame( 'https://checkout.stripe.test/direct', $result['redirect'] );
	}

	public function test_calculate_order_amount_respects_stripe_currency_rules(): void {
		$gateway = $this->new_gateway();

		$this->currency = 'USD';
		$this->assertSame( 1234.0, $gateway->calculate_order_amount( 12.34 ) );
		$this->assertSame( 12, $gateway->call_calculate_stripe_amount_for_currency( 12.34, 'JPY' ) );
		$this->assertSame( 12340.0, $gateway->call_calculate_stripe_amount_for_currency( 12.34, 'BHD' ) );
	}

	/**
	 * @dataProvider subscription_precondition_provider
	 */
	public function test_pay_via_subscription_rejects_invalid_preconditions( array $state, array $payload, string $message ): void {
		$gateway = $this->new_gateway();
		foreach ( $state as $key => $value ) {
			if ( 'secret_key' === $key ) {
				$gateway->set_secret_key_for_test( (string) $value );
			} elseif ( 'webhook_secret' === $key ) {
				$gateway->set_webhook_secret_for_test( (string) $value );
			} else {
				$gateway->{$key} = $value;
			}
		}

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( $message );

		$gateway->call_parent_pay_via_subscription( new \LP_Order( 324, 'order_324', 10 ), $payload );
	}

	public static function subscription_precondition_provider(): array {
		return array(
			'disabled subscriptions' => array(
				array( 'enable_subscriptions' => 'no' ),
				array( 'plan_id' => 'price_123' ),
				'Stripe subscriptions are disabled.',
			),
			'missing secret key'     => array(
				array( 'secret_key' => '' ),
				array( 'plan_id' => 'price_123' ),
				'Stripe secret key is missing.',
			),
			'missing webhook secret' => array(
				array( 'webhook_secret' => '' ),
				array( 'plan_id' => 'price_123' ),
				'Stripe webhook signing secret is missing.',
			),
			'invalid webhook secret' => array(
				array( 'webhook_secret' => 'bad_secret' ),
				array( 'plan_id' => 'price_123' ),
				'Stripe webhook signing secret is invalid.',
			),
			'missing plan id'        => array(
				array(),
				array(),
				'Stripe subscription price ID is invalid.',
			),
		);
	}

	public function test_build_webhook_data_contains_lp_and_stripe_subscription_fields(): void {
		$gateway = $this->new_gateway();
		$order   = new \LP_Order( 325, 'order_325', 11 );

		$result = $gateway->call_build_webhook_data(
			$order,
			'sub_123',
			'price_123',
			\LP_Subscription_Manager::STATUS_ACTIVATED,
			array(
				'amount'        => 19.99,
				'currency'      => 'USD',
				'created'       => 1700000000,
				'next_billing'  => 1702500000,
				'event_id'      => 'evt_123',
				'event_type'    => 'invoice.payment_succeeded',
				'customer_id'   => 'cus_123',
				'invoice_id'    => 'in_123',
				'renewal_key'   => 'stripe_invoice_in_123',
				'stripe_status' => 'active',
			)
		);

		$this->assertSame( 325, $result['lp_order_id'] );
		$this->assertSame( 'price_123', $result['lp_plan_id'] );
		$this->assertSame( 'sub_123', $result['lp_subscription_id'] );
		$this->assertSame( \LP_Subscription_Manager::STATUS_ACTIVATED, $result['lp_subscription_status'] );
		$this->assertSame( 'evt_123', $result['stripe_event_id'] );
		$this->assertSame( 'invoice.payment_succeeded', $result['stripe_event_type'] );
		$this->assertSame( 'cus_123', $result['stripe_customer_id'] );
		$this->assertSame( 'in_123', $result['stripe_invoice_id'] );
		$this->assertSame( 'active', $result['stripe_subscription_status'] );
		$this->assertSame( '2023-11-14T22:13:20+00:00', $result['create_time'] );
	}

	public function test_maps_stripe_subscription_statuses_to_learnpress_statuses(): void {
		$gateway = $this->new_gateway();

		$this->assertSame( \LP_Subscription_Manager::STATUS_TRIAL, $gateway->call_map_stripe_subscription_status_to_lp( 'trialing' ) );
		$this->assertSame( \LP_Subscription_Manager::STATUS_ACTIVATED, $gateway->call_map_stripe_subscription_status_to_lp( 'active' ) );
		$this->assertSame( \LP_Subscription_Manager::STATUS_CANCELLED, $gateway->call_map_stripe_subscription_status_to_lp( 'canceled' ) );
		$this->assertSame( \LP_Subscription_Manager::STATUS_EXPIRED, $gateway->call_map_stripe_subscription_status_to_lp( 'incomplete_expired' ) );
		$this->assertSame( \LP_Subscription_Manager::STATUS_SUSPENDED, $gateway->call_map_stripe_subscription_status_to_lp( 'past_due' ) );
		$this->assertSame( '', $gateway->call_map_stripe_subscription_status_to_lp( 'unknown' ) );
	}

	public function test_build_stripe_price_summary_normalizes_price_details(): void {
		$gateway = $this->new_gateway();

		$summary = $gateway->call_build_stripe_price_summary(
			array(
				'id'          => 'price_123',
				'product'     => array( 'id' => 'prod_123' ),
				'unit_amount' => 1299,
				'currency'    => 'usd',
				'active'      => true,
				'type'        => 'recurring',
				'recurring'   => array(
					'interval'          => 'month',
					'interval_count'    => 2,
					'trial_period_days' => 14,
				),
			)
		);

		$this->assertSame( 'price_123', $summary['id'] );
		$this->assertSame( 'prod_123', $summary['product_id'] );
		$this->assertSame( 12.99, $summary['amount'] );
		$this->assertSame( 'USD', $summary['currency'] );
		$this->assertSame( 'month', $summary['interval'] );
		$this->assertSame( 2, $summary['interval_count'] );
		$this->assertSame( 14, $summary['trial_period_days'] );
		$this->assertSame( 'ACTIVE', $summary['status'] );
	}

	public function test_capture_subscription_webhook_rejects_missing_secret_or_transport_data(): void {
		$gateway = $this->new_gateway();
		$gateway->set_webhook_secret_for_test( '' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Stripe webhook signing secret is missing.' );
		$gateway->capture_subscription_webhook( new \WP_REST_Request( 'POST' ) );
	}

	public function test_capture_subscription_webhook_rejects_empty_payload_or_signature(): void {
		$gateway = $this->new_gateway();
		$request = new \WP_REST_Request( 'POST' );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Invalid Stripe webhook request.' );
		$gateway->capture_subscription_webhook( $request );
	}
}

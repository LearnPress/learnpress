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
			public $updated_status = "";

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

			public function update_status( $status ) {
				$this->updated_status = (string) $status;
			}
		}'
	);
}

if ( ! class_exists( '\\LP_Helper', false ) ) {
	eval(
		'class LP_Helper {
			public static function json_decode( $value, $assoc = false ) {
				return json_decode( (string) $value, (bool) $assoc );
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
			public static function get_option( $key, $default = false ) {
				return self::$options[ $key ] ?? $default;
			}
			public static function update_option( $name, $value ) {
				self::$options[ $name ] = $value;
			}
		}'
	);
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

if ( ! class_exists( '\\LP_Subscription_Manager_Test_Gateway', false ) ) {
	eval(
		'class LP_Subscription_Manager_Test_Gateway extends LP_Gateway_Abstract {
			protected $gateway_id = "test_gateway";
			public function __construct( string $gateway_id = "test_gateway" ) {
				$this->gateway_id = $gateway_id;
			}
			public function get_id() {
				return $this->gateway_id;
			}
		}'
	);
}

if ( ! class_exists( '\\LP_Subscription_Manager_Test_Double', false ) ) {
	eval(
		'class LP_Subscription_Manager_Test_Double extends LP_Subscription_Manager {
			public $next_renewal_order = null;
			public $create_renewal_calls = array();
			public $order_notes = array();

			public function create_renewal_order( LP_Order $parent_order, array $event, string $target_status = LP_ORDER_PENDING ): LP_Order {
				$this->create_renewal_calls[] = array(
					"parent_order_id" => $parent_order->get_id(),
					"event" => $event,
					"target_status" => $target_status,
				);
				if ( $this->next_renewal_order instanceof LP_Order ) {
					return $this->next_renewal_order;
				}
				return new LP_Order( 9002, "renewal_9002", 0 );
			}

			protected function add_order_note( LP_Order $order, string $note ) {
				$this->order_notes[] = array(
					"order_id" => $order->get_id(),
					"note" => $note,
				);
			}
		}'
	);
}

if ( ! defined( 'LP_ORDER_COMPLETED' ) ) {
	define( 'LP_ORDER_COMPLETED', 'completed' );
}

if ( ! defined( 'LP_ORDER_FAILED' ) ) {
	define( 'LP_ORDER_FAILED', 'failed' );
}

if ( ! defined( 'LP_ORDER_PENDING' ) ) {
	define( 'LP_ORDER_PENDING', 'pending' );
}

if ( ! defined( 'LP_ORDER_CPT' ) ) {
	define( 'LP_ORDER_CPT', 'lp_order' );
}

class LPSubscriptionManagerWebhookTest extends BrainMonkeyTestCase {
	/**
	 * @var array<int, array<string, mixed>>
	 */
	private array $meta_store = array();

	/**
	 * @var array<int, \LP_Order>
	 */
	private array $orders = array();

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'absint' )->alias(
			function ( $value ): int {
				return abs( (int) $value );
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
		Functions\when( 'wp_json_encode' )->alias(
			function ( $value ): string {
				return (string) json_encode( $value );
			}
		);
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'get_posts' )->justReturn( array() );

		Functions\when( 'learn_press_get_order' )->alias(
			function ( $order_id ) {
				$order_id = abs( (int) $order_id );
				return $this->orders[ $order_id ] ?? false;
			}
		);

		Functions\when( 'update_post_meta' )->alias(
			function ( $post_id, $meta_key, $meta_value ) {
				$post_id                              = abs( (int) $post_id );
				$key                                  = (string) $meta_key;
				$this->meta_store[ $post_id ][ $key ] = $meta_value;
				return true;
			}
		);

		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $meta_key ) {
				$post_id = abs( (int) $post_id );
				$key     = (string) $meta_key;
				return $this->meta_store[ $post_id ][ $key ] ?? '';
			}
		);
	}

	public function test_process_webhook_event_paypal_subscription_cancelled_from_standard_payload(): void {
		$parent_order       = new \LP_Order( 1001, 'order_1001', 11 );
		$this->orders[1001] = $parent_order;

		$gateway = new \LP_Subscription_Manager_Test_Gateway( 'paypal' );
		$manager = new \LP_Subscription_Manager();
		$event   = $this->normalize_paypal_payload_to_event( $this->build_paypal_standard_payload() );

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'cancelled', $response['status'] );
		$this->assertSame( 'subscription_cancelled', $response['event_type'] );
		$this->assertSame( 1001, $response['order_id'] );
		$this->assertSame( 'cancelled', $this->meta_store[1001][ \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS ] ?? '' );
		$this->assertSame( 'I-TESTPAYPALSUB001', $this->meta_store[1001][ \LP_Gateway_Abstract::META_SUBSCRIPTION_ID ] ?? '' );
		$this->assertSame( 'P-TESTPAYPALPLAN001', $this->meta_store[1001][ \LP_Gateway_Abstract::META_SUBSCRIPTION_PLAN_ID ] ?? '' );
	}

	public function test_process_webhook_event_stripe_renewal_payment_succeeded_from_standard_payload(): void {
		$parent_order       = new \LP_Order( 1002, 'order_1002', 12 );
		$renewal_order      = new \LP_Order( 2002, 'renewal_2002', 12 );
		$this->orders[1002] = $parent_order;

		$gateway                     = new \LP_Subscription_Manager_Test_Gateway( 'stripe' );
		$manager                     = new \LP_Subscription_Manager_Test_Double();
		$manager->next_renewal_order = $renewal_order;
		$event                       = $this->normalize_stripe_payload_to_event( $this->build_stripe_standard_payload() );

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 'renewal_payment_succeeded', $response['event_type'] );
		$this->assertSame( 1002, $response['order_id'] );
		$this->assertSame( 2002, $response['renewal_order_id'] );
		$this->assertSame( 'active', $this->meta_store[1002][ \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS ] ?? '' );
		$this->assertCount( 1, $manager->create_renewal_calls );
		$this->assertSame( 'completed', $manager->create_renewal_calls[0]['target_status'] ?? '' );
		$this->assertSame( 'in_1N9WQ2A1B2C3D4E5', $manager->create_renewal_calls[0]['event']['renewal_key'] ?? '' );
	}

	/**
	 * PayPal webhook sample: BILLING.SUBSCRIPTION.CANCELLED.
	 *
	 * @return array<string, mixed>
	 */
	private function build_paypal_standard_payload(): array {
		return array(
			'id'         => 'WH-TEST-PAYPAL-EVT-001',
			'event_type' => 'BILLING.SUBSCRIPTION.CANCELLED',
			'resource'   => array(
				'id'         => 'I-TESTPAYPALSUB001',
				'status'     => 'CANCELLED',
				'plan_id'    => 'P-TESTPAYPALPLAN001',
				'custom_id'  => '1001',
				'subscriber' => array(
					'payer_id' => 'CUSTOMERPP001',
				),
			),
		);
	}

	/**
	 * Stripe webhook sample: invoice.payment_succeeded for recurring cycle.
	 *
	 * @return array<string, mixed>
	 */
	private function build_stripe_standard_payload(): array {
		return array(
			'id'   => 'evt_1N9WQ2A1B2C3D4E5',
			'type' => 'invoice.payment_succeeded',
			'data' => array(
				'object' => array(
					'id'             => 'in_1N9WQ2A1B2C3D4E5',
					'subscription'   => 'sub_1N9WQ2A1B2C3D4E5',
					'customer'       => 'cus_1N9WQ2A1B2C3D4E5',
					'amount_paid'    => 4900,
					'currency'       => 'usd',
					'payment_intent' => 'pi_1N9WQ2A1B2C3D4E5',
					'metadata'       => array(
						'lp_order_id' => '1002',
					),
					'lines'          => array(
						'data' => array(
							array(
								'price' => array(
									'id'        => 'price_1N9WQ2A1B2C3D4E5',
									'recurring' => array(
										'interval'       => 'month',
										'interval_count' => 1,
									),
								),
							),
						),
					),
				),
			),
		);
	}

	/**
	 * Map PayPal raw payload to manager normalized event shape.
	 *
	 * @param array<string, mixed> $payload
	 *
	 * @return array<string, mixed>
	 */
	private function normalize_paypal_payload_to_event( array $payload ): array {
		$resource = (array) ( $payload['resource'] ?? array() );

		return array(
			'event_id'        => (string) ( $payload['id'] ?? '' ),
			'event_type'      => 'subscription_cancelled',
			'subscription_id' => (string) ( $resource['id'] ?? '' ),
			'customer_id'     => (string) ( $resource['subscriber']['payer_id'] ?? '' ),
			'price_id'        => (string) ( $resource['plan_id'] ?? '' ),
			'status'          => strtolower( (string) ( $resource['status'] ?? '' ) ),
			'parent_order_id' => (int) ( $resource['custom_id'] ?? 0 ),
			'metadata'        => array(
				'lp_order_id' => (string) ( $resource['custom_id'] ?? '' ),
			),
		);
	}

	/**
	 * Map Stripe raw payload to manager normalized event shape.
	 *
	 * @param array<string, mixed> $payload
	 *
	 * @return array<string, mixed>
	 */
	private function normalize_stripe_payload_to_event( array $payload ): array {
		$invoice   = (array) ( $payload['data']['object'] ?? array() );
		$first_line = (array) ( $invoice['lines']['data'][0] ?? array() );
		$price     = (array) ( $first_line['price'] ?? array() );
		$metadata  = (array) ( $invoice['metadata'] ?? array() );

		return array(
			'event_id'        => (string) ( $payload['id'] ?? '' ),
			'event_type'      => 'renewal_payment_succeeded',
			'subscription_id' => (string) ( $invoice['subscription'] ?? '' ),
			'customer_id'     => (string) ( $invoice['customer'] ?? '' ),
			'price_id'        => (string) ( $price['id'] ?? '' ),
			'parent_order_id' => (int) ( $metadata['lp_order_id'] ?? 0 ),
			'transaction_id'  => (string) ( $invoice['payment_intent'] ?? '' ),
			'amount'          => (float) ( (int) ( $invoice['amount_paid'] ?? 0 ) / 100 ),
			'currency'        => strtoupper( (string) ( $invoice['currency'] ?? '' ) ),
			'renewal_key'     => (string) ( $invoice['id'] ?? '' ),
			'metadata'        => $metadata,
		);
	}
}

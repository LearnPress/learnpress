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
			public $completed = false;
			public $payment_complete_txn = "";
			public $notes = array();

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

			public function is_completed() {
				return (bool) $this->completed;
			}

			public function payment_complete( $transaction_id = "" ) {
				$this->completed = true;
				$this->payment_complete_txn = (string) $transaction_id;
			}

			public function add_note( $note ) {
				$this->notes[] = (string) $note;
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
		Functions\when( 'get_posts' )->alias(
			function ( $args ) {
				$args = is_array( $args ) ? $args : array();

				$meta_query = is_array( $args['meta_query'] ?? null ) ? $args['meta_query'] : array();
				if ( empty( $meta_query ) ) {
					return array();
				}

				$meta = is_array( $meta_query[0] ?? null ) ? $meta_query[0] : array();
				$key  = (string) ( $meta['key'] ?? '' );
				$val  = (string) ( $meta['value'] ?? '' );

				if ( \LP_Gateway_Abstract::META_SUBSCRIPTION_ID !== $key || '' === $val ) {
					return array();
				}

				foreach ( $this->meta_store as $order_id => $order_meta ) {
					$saved_subscription_id = (string) ( $order_meta[ \LP_Gateway_Abstract::META_SUBSCRIPTION_ID ] ?? '' );
					if ( $saved_subscription_id === $val ) {
						return array( (int) $order_id );
					}
				}

				return array();
			}
		);

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

	public function test_process_webhook_event_paypal_sale_completed_infers_initial_payment_when_parent_not_completed(): void {
		$parent_order       = new \LP_Order( 1001, 'order_1001', 11 );
		$this->orders[1001] = $parent_order;
		$this->meta_store[1001][ \LP_Gateway_Abstract::META_SUBSCRIPTION_ID ] = 'I-TESTPAYPALSUB001';

		$gateway = new \LP_Subscription_Manager_Test_Gateway( 'paypal' );
		$manager = new \LP_Subscription_Manager_Test_Double();
		$event   = $this->normalize_paypal_payload_to_event( $this->build_paypal_standard_payload() );

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 'renewal_payment_succeeded', $response['event_type'] );
		$this->assertSame( 1001, $response['order_id'] );
		$this->assertArrayNotHasKey( 'renewal_order_id', $response );
		$this->assertSame( 'active', $this->meta_store[1001][ \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS ] ?? '' );
		$this->assertSame( 'I-TESTPAYPALSUB001', $this->meta_store[1001][ \LP_Gateway_Abstract::META_SUBSCRIPTION_ID ] ?? '' );
		$this->assertTrue( $parent_order->is_completed() );
		$this->assertSame( '9HT12345P6789012A', $parent_order->payment_complete_txn );
		$this->assertCount( 0, $manager->create_renewal_calls );
	}

	public function test_process_webhook_event_paypal_sale_completed_creates_renewal_when_parent_already_completed(): void {
		$parent_order            = new \LP_Order( 1003, 'order_1003', 13 );
		$parent_order->completed = true;
		$this->orders[1003]      = $parent_order;
		$this->meta_store[1003][ \LP_Gateway_Abstract::META_SUBSCRIPTION_ID ] = 'I-TESTPAYPALSUB003';

		$gateway = new \LP_Subscription_Manager_Test_Gateway( 'paypal' );
		$manager = new \LP_Subscription_Manager_Test_Double();
		$event   = $this->normalize_paypal_payload_to_event( $this->build_paypal_standard_payload() );
		$event['metadata']['lp_order_id'] = '1003';
		$event['parent_order_id']         = 1003;
		$event['subscription_id']         = 'I-TESTPAYPALSUB003';

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 1003, $response['order_id'] );
		$this->assertSame( 9002, $response['renewal_order_id'] );
		$this->assertCount( 1, $manager->create_renewal_calls );
		$this->assertSame( 'completed', $manager->create_renewal_calls[0]['target_status'] ?? '' );
		$this->assertSame( 'paypal_sale_9HT12345P6789012A', $manager->create_renewal_calls[0]['event']['renewal_key'] ?? '' );
	}

	public function test_process_webhook_event_subscription_activated_marks_parent_completed(): void {
		$parent_order       = new \LP_Order( 1101, 'order_1101', 21 );
		$this->orders[1101] = $parent_order;

		$gateway = new \LP_Subscription_Manager_Test_Gateway( 'paypal' );
		$manager = new \LP_Subscription_Manager_Test_Double();
		$event   = array(
			'event_id'        => 'evt_sub_activated_1101',
			'event_type'      => 'subscription_activated',
			'parent_order_id' => 1101,
			'subscription_id' => 'I-PAYPALSUB1101',
			'metadata'        => array(),
		);

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 1101, $response['order_id'] );
		$this->assertSame( 'active', $this->meta_store[1101][ \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS ] ?? '' );
		$this->assertTrue( $parent_order->is_completed() );
		$this->assertSame( '', $parent_order->payment_complete_txn );
	}

	public function test_process_webhook_event_initial_payment_succeeded_completes_parent_order(): void {
		$parent_order       = new \LP_Order( 1201, 'order_1201', 22 );
		$this->orders[1201] = $parent_order;

		$gateway = new \LP_Subscription_Manager_Test_Gateway( 'stripe' );
		$manager = new \LP_Subscription_Manager_Test_Double();
		$event   = array(
			'event_id'        => 'evt_initial_paid_1201',
			'event_type'      => 'initial_payment_succeeded',
			'parent_order_id' => 1201,
			'subscription_id' => 'sub_STRIPE1201',
			'transaction_id'  => 'pi_STRIPE1201',
			'metadata'        => array(),
		);

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'success', $response['status'] );
		$this->assertSame( 1201, $response['order_id'] );
		$this->assertSame( 'active', $this->meta_store[1201][ \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS ] ?? '' );
		$this->assertTrue( $parent_order->is_completed() );
		$this->assertSame( 'pi_STRIPE1201', $parent_order->payment_complete_txn );
	}

	public function test_process_webhook_event_subscription_suspended_updates_status(): void {
		$order_subscription       = new \LP_Order( 1301, 'order_1301', 31 );
		$this->orders[1301] = $order_subscription;

		$gateway = new \LP_Subscription_Manager_Test_Gateway( 'paypal' );
		$manager = new \LP_Subscription_Manager_Test_Double();
		$event   = array(
			'event_id'        => 'evt_sub_suspended_1301',
			'event_type'      => 'subscription_suspended',
			'parent_order_id' => 1301,
			'subscription_id' => 'I-PAYPALSUB1301',
			'metadata'        => array(),
		);

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'suspended', $response['status'] );
		$this->assertSame( 1301, $response['order_id'] );
		$this->assertSame( 'suspended', $this->meta_store[1301][ \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS ] ?? '' );
	}

	public function test_process_webhook_event_subscription_expired_updates_status(): void {
		$order_subscription       = new \LP_Order( 1401, 'order_1401', 41 );
		$this->orders[1401] = $order_subscription;

		$gateway = new \LP_Subscription_Manager_Test_Gateway( 'paypal' );
		$manager = new \LP_Subscription_Manager_Test_Double();
		$event   = array(
			'event_id'        => 'evt_sub_expired_1401',
			'event_type'      => 'subscription_expired',
			'parent_order_id' => 1401,
			'subscription_id' => 'I-PAYPALSUB1401',
			'metadata'        => array(),
		);

		$response = $manager->process_webhook_event( $gateway, $event );

		$this->assertSame( 'expired', $response['status'] );
		$this->assertSame( 1401, $response['order_id'] );
		$this->assertSame( 'expired', $this->meta_store[1401][ \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS ] ?? '' );
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
	 * PayPal webhook sample based on official subscriptions webhook docs.
	 * Source: https://developer.paypal.com/docs/subscriptions/reference/webhooks/
	 *
	 * Event used here:
	 * - PAYMENT.SALE.COMPLETED
	 *
	 * @return array<string, mixed>
	 */
	private function build_paypal_standard_payload(): array {
		return array(
			'id'               => 'WH-TEST-PAYPAL-EVT-001',
			'event_version'    => '1.0',
			'create_time'      => '2026-04-07T10:11:12Z',
			'resource_type'    => 'sale',
			'event_type'       => 'PAYMENT.SALE.COMPLETED',
			'summary'          => 'Payment completed for $29.00 USD',
			'resource_version' => '1.0',
			'resource'         => array(
				'id'                   => '9HT12345P6789012A',
				'state'                => 'completed',
				'amount'               => array(
					'total'    => '29.00',
					'currency' => 'USD',
				),
				'payment_mode'         => 'INSTANT_TRANSFER',
				'update_time'          => '2026-04-07T10:11:12Z',
				'create_time'          => '2026-04-07T10:11:10Z',
				'billing_agreement_id' => 'I-TESTPAYPALSUB001',
				'parent_payment'       => 'PAY-TESTPARENTPAYMENT001',
			),
			'links'            => array(
				array(
					'href'   => 'https://api-m.sandbox.paypal.com/v1/payments/sale/9HT12345P6789012A',
					'rel'    => 'self',
					'method' => 'GET',
				),
			),
		);
	}

	/**
	 * Stripe webhook sample based on official API object shape.
	 * Source: https://docs.stripe.com/api/events/object
	 *
	 * Event used here:
	 * - invoice.payment_succeeded
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
					'charge'         => null,
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
			'event_type'      => 'renewal_payment_succeeded',
			'subscription_id' => (string) ( $resource['billing_agreement_id'] ?? '' ),
			'customer_id'     => '',
			'price_id'        => '',
			'parent_order_id' => 0,
			'transaction_id'  => (string) ( $resource['id'] ?? '' ),
			'amount'          => (float) ( $resource['amount']['total'] ?? 0 ),
			'currency'        => strtoupper( (string) ( $resource['amount']['currency'] ?? '' ) ),
			'renewal_key'     => ! empty( $resource['id'] ) ? 'paypal_sale_' . (string) $resource['id'] : '',
			'metadata'        => array(),
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
		$invoice    = (array) ( $payload['data']['object'] ?? array() );
		$first_line = (array) ( $invoice['lines']['data'][0] ?? array() );
		$price      = (array) ( $first_line['price'] ?? array() );
		$metadata   = (array) ( $invoice['metadata'] ?? array() );

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

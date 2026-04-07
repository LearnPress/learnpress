<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

class LPGatewayAbstractSubscriptionTest extends BrainMonkeyTestCase {

	private function load_gateway_dependencies(): void {
		if ( ! class_exists( '\\LP_Order', false ) ) {
			eval(
				'class LP_Order {
					private $id;
					private $order_key;
					private $user_id;
					public $updated_status = "";

					public function __construct( $id = 0, $order_key = "", $user_id = 0 ) {
						$this->id = (int) $id;
						$this->order_key = (string) $order_key;
						$this->user_id = (int) $user_id;
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

		if ( ! class_exists( '\\LP_Gateway_Subscription_Test_Double', false ) ) {
			eval(
				'class LP_Gateway_Subscription_Test_Double extends LP_Gateway_Abstract {
					public $id = "subscription_test_gateway";
					public $title = "Subscription Test Gateway";

					public function call_validate_subscription_payload( array $data ) {
						return $this->validate_subscription_payload( $data );
					}

					public function call_validate_data_plan_payload( array $data ) {
						return $this->validate_data_plan_payload( $data );
					}

					public function call_build_webhook_data_from_request( WP_REST_Request $request, array $required_headers = array(), bool $decode_body = true ): array {
						return $this->build_webhook_data_from_request( $request, $required_headers, $decode_body );
					}

					public function call_validate_webhook_data_contract( array $webhook_data, array $required_top_level_keys = array(), array $required_headers = array() ) {
						$this->validate_webhook_data_contract( $webhook_data, $required_top_level_keys, $required_headers );
					}
				}'
			);
		}

		if ( ! class_exists( '\\LP_Gateway_OneTime_Test_Double', false ) ) {
			eval(
				'class LP_Gateway_OneTime_Test_Double extends LP_Gateway_Abstract {
					public $id = "one_time_test_gateway";
					public $title = "One-time Test Gateway";

					public function call_validate_subscription_payload( array $data ) {
						return $this->validate_subscription_payload( $data );
					}

					public function call_validate_data_plan_payload( array $data ) {
						return $this->validate_data_plan_payload( $data );
					}
				}'
			);
		}
	}

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'update_option' )->justReturn( true );
		Functions\when( 'sanitize_title' )->alias(
			function ( string $value ): string {
				return strtolower( str_replace( ' ', '-', $value ) );
			}
		);
		Functions\when( 'learn_press_get_currency' )->justReturn( 'USD' );
		Functions\when( 'wp_parse_args' )->alias(
			function ( array $args, array $defaults ): array {
				return array_merge( $defaults, $args );
			}
		);
		Functions\when( 'wp_unslash' )->returnArg( 1 );
		Functions\when( 'sanitize_text_field' )->returnArg( 1 );
		Functions\when( 'sanitize_key' )->alias(
			function ( string $value ): string {
				return strtolower( preg_replace( '/[^a-z0-9_\\-]/', '', $value ) );
			}
		);
		Functions\when( 'absint' )->alias(
			function ( $value ): int {
				return abs( (int) $value );
			}
		);
		Functions\when( 'esc_url_raw' )->returnArg( 1 );
		Functions\when( 'learn_press_get_page_link' )->justReturn( 'https://example.com/checkout' );
		Functions\when( 'learn_press_get_endpoint_url' )->justReturn( 'https://example.com/checkout/order-received' );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				return $value;
			}
		);

		$this->load_gateway_dependencies();
	}

	public function test_is_subscription_order_returns_false_when_gateway_does_not_support_subscription(): void {
		$gateway = new \LP_Gateway_OneTime_Test_Double();
		$order   = new \LP_Order( 100, 'order_100', 9 );

		$this->assertFalse( $gateway->is_subscription_order( $order ) );
	}

	public function test_is_subscription_order_uses_filter_when_subscription_feature_exists(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				if ( 'learn-press/gateway/subscription-order' === $hook ) {
					return true;
				}

				return $value;
			}
		);

		$gateway = new \LP_Gateway_Subscription_Test_Double();
		$order   = new \LP_Order( 101, 'order_101', 10 );

		$this->assertTrue( $gateway->is_subscription_order( $order ) );
	}

	public function test_is_subscription_order_returns_true_when_order_has_saved_price_id(): void {
		Functions\when( 'get_post_meta' )->alias(
			function ( int $order_id, string $key ) {
				if ( \LP_Gateway_Abstract::META_SUBSCRIPTION_PLAN_ID === $key ) {
					return 'price_saved_01';
				}

				return '';
			}
		);

		$gateway = new \LP_Gateway_Subscription_Test_Double();
		$order   = new \LP_Order( 102, 'order_102', 11 );

		$this->assertTrue( $gateway->is_subscription_order( $order ) );
	}

	public function test_resolve_subscription_payment_data_returns_empty_for_one_time_flow(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();
		$order   = new \LP_Order( 103, 'order_103', 12 );

		$result = $gateway->resolve_subscription_payment_data( $order );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result );
	}

	public function test_resolve_subscription_payment_data_throws_exception_when_marked_subscription_without_price_id(): void {
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				if ( 'learn-press/gateway/subscription-order' === $hook ) {
					return true;
				}

				return $value;
			}
		);

		$gateway = new \LP_Gateway_Subscription_Test_Double();
		$order   = new \LP_Order( 104, 'order_104', 13 );

		$this->expectException( \Exception::class );
		$gateway->resolve_subscription_payment_data( $order );
	}

	public function test_resolve_subscription_payment_data_persists_identifiers_when_price_id_exists(): void {
		Functions\when( 'get_post_meta' )->alias(
			function ( int $order_id, string $key ) {
				$values = array(
					\LP_Gateway_Abstract::META_SUBSCRIPTION_PLAN_ID => 'price_quarterly_99',
					\LP_Gateway_Abstract::META_SUBSCRIPTION_QUANTITY => 2,
				);

				return $values[ $key ] ?? '';
			}
		);

		$updates = array();
		Functions\when( 'update_post_meta' )->alias(
			function ( int $order_id, string $key, $value ) use ( &$updates ) {
				$updates[ $key ] = $value;
				return true;
			}
		);

		$gateway = new \LP_Gateway_Subscription_Test_Double();
		$order   = new \LP_Order( 105, 'order_105', 14 );
		$result  = $gateway->resolve_subscription_payment_data( $order );

		$this->assertIsArray( $result );
		$this->assertSame( 'price_quarterly_99', $result['price_id'] );
		$this->assertSame( 'price_quarterly_99', $updates[ \LP_Gateway_Abstract::META_SUBSCRIPTION_PLAN_ID ] ?? '' );
		$this->assertSame( 2, $updates[ \LP_Gateway_Abstract::META_SUBSCRIPTION_QUANTITY ] ?? 0 );
	}

	public function test_validate_subscription_payload_requires_price_id(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->call_validate_subscription_payload(
			array(
				'success_url' => 'https://example.com/success',
				'cancel_url'  => 'https://example.com/cancel',
			)
		);
	}

	public function test_validate_subscription_payload_requires_success_and_cancel_urls(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->call_validate_subscription_payload(
			array(
				'price_id' => 'price_monthly_01',
			)
		);
	}

	public function test_validate_subscription_payload_normalizes_quantity_and_metadata(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$result = $gateway->call_validate_subscription_payload(
			array(
				'price_id'    => 'price_monthly_01',
				'quantity'    => 0,
				'metadata'    => 'not-array',
				'success_url' => 'https://example.com/success',
				'cancel_url'  => 'https://example.com/cancel',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'price_monthly_01', $result['price_id'] );
		$this->assertSame( 1, $result['quantity'] );
		$this->assertSame( array(), $result['metadata'] );
		$this->assertSame( 'https://example.com/success', $result['success_url'] );
		$this->assertSame( 'https://example.com/cancel', $result['cancel_url'] );
	}

	public function test_create_plan_throws_not_supported_exception_on_base_gateway(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->create_plan(
			array(
				'name'     => 'Monthly Plan',
				'amount'   => 12,
				'currency' => 'USD',
				'interval' => 'month',
			)
		);
	}

	public function test_validate_data_plan_payload_requires_name_if_product_id_missing(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->call_validate_data_plan_payload(
			array(
				'amount'   => 10,
				'currency' => 'USD',
				'interval' => 'month',
			)
		);
	}

	public function test_validate_data_plan_payload_requires_positive_amount(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->call_validate_data_plan_payload(
			array(
				'name'     => 'Monthly Plan',
				'amount'   => 0,
				'currency' => 'USD',
				'interval' => 'month',
			)
		);
	}

	public function test_validate_data_plan_payload_accepts_product_id_without_name(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$result = $gateway->call_validate_data_plan_payload(
			array(
				'product_id' => 'prod_external_001',
				'amount'     => 11.5,
				'currency'   => 'usd',
				'interval'   => 'year',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'prod_external_001', $result['product_id'] );
		$this->assertSame( 'USD', $result['currency'] );
		$this->assertSame( 'year', $result['interval'] );
	}

	public function test_validate_data_plan_payload_rejects_invalid_interval(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->call_validate_data_plan_payload(
			array(
				'name'     => 'Monthly Plan',
				'amount'   => 10,
				'currency' => 'USD',
				'interval' => 'quarter',
			)
		);
	}

	public function test_validate_data_plan_payload_normalizes_defaults_and_arrays(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$result = $gateway->call_validate_data_plan_payload(
			array(
				'name'           => 'Monthly Plan',
				'amount'         => 20,
				'currency'       => 'usd',
				'interval'       => 'month',
				'interval_count' => 0,
				'metadata'       => 'invalid',
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['interval_count'] );
		$this->assertSame( 0.0, $result['setup_fee'] );
		$this->assertSame( array(), $result['metadata'] );
	}

	public function test_validate_data_plan_payload_rejects_negative_setup_fee(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->call_validate_data_plan_payload(
			array(
				'name'      => 'Monthly Plan',
				'amount'    => 20,
				'currency'  => 'usd',
				'interval'  => 'month',
				'setup_fee' => -1,
			)
		);
	}

	public function test_validate_data_plan_payload_rejects_missing_amount_even_with_extra_fields(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$gateway->call_validate_data_plan_payload(
			array(
				'name'   => 'Yearly Plan',
				'amount' => 0,
				'extra'  => array(
					'amount'         => 59.9,
					'currency'       => 'eur',
					'interval'       => 'year',
					'interval_count' => 2,
				),
			)
		);
	}

	public function test_get_subscription_context_builds_expected_metadata_and_defaults_quantity_to_one(): void {
		Functions\when( 'get_post_meta' )->alias(
			function ( int $order_id, string $key ) {
				$values = array(
					\LP_Gateway_Abstract::META_SUBSCRIPTION_PLAN_ID => 'price_quarterly_01',
					\LP_Gateway_Abstract::META_SUBSCRIPTION_QUANTITY => 0,
				);

				return $values[ $key ] ?? '';
			}
		);

		$gateway = new \LP_Gateway_Subscription_Test_Double();
		$order   = new \LP_Order( 222, 'order_key_222', 33 );
		$context = $gateway->get_subscription_context( $order );

		$this->assertSame( 'price_quarterly_01', $context['price_id'] );
		$this->assertSame( 1, $context['quantity'] );
		$this->assertSame( 'https://example.com/order-received/222', $context['success_url'] );
		$this->assertSame( 'https://example.com/checkout', $context['cancel_url'] );
		$this->assertSame( '222', $context['metadata']['lp_order_id'] );
		$this->assertSame( 'order_key_222', $context['metadata']['lp_order_key'] );
		$this->assertSame( 'subscription_test_gateway', $context['metadata']['lp_gateway'] );
		$this->assertSame( '33', $context['metadata']['lp_user_id'] );
		$this->assertSame( 'subscription', $context['metadata']['lp_order_type'] );
	}

	public function test_build_webhook_data_from_request_extracts_required_headers_and_decoded_body(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();
		$request = new \WP_REST_Request( 'POST' );
		$request->set_body( wp_json_encode( array( 'id' => 'evt_001' ) ) );
		$request->set_header( 'stripe-signature', 'sig_test_123' );

		$result = $gateway->call_build_webhook_data_from_request(
			$request,
			array( 'stripe-signature' ),
			true
		);

		$this->assertSame( 'sig_test_123', $result['headers']['stripe-signature'] );
		$this->assertSame( 'evt_001', $result['body']['id'] ?? '' );
		$this->assertNotEmpty( $result['raw_body'] );
	}

	public function test_validate_webhook_data_contract_throws_when_required_header_is_missing(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$this->expectException( \Exception::class );
		$this->expectExceptionCode( 400 );

		$gateway->call_validate_webhook_data_contract(
			array(
				'raw_body' => '{"id":"evt_001"}',
				'body'     => array( 'id' => 'evt_001' ),
				'headers'  => array(),
			),
			array( 'raw_body', 'body', 'headers' ),
			array( 'stripe-signature' )
		);
	}

	public function test_validate_webhook_data_contract_passes_for_complete_payload(): void {
		$gateway = new \LP_Gateway_Subscription_Test_Double();

		$gateway->call_validate_webhook_data_contract(
			array(
				'raw_body' => '{"id":"evt_002"}',
				'body'     => array( 'id' => 'evt_002' ),
				'headers'  => array(
					'stripe-signature' => 'sig_valid_123',
				),
			),
			array( 'raw_body', 'body', 'headers' ),
			array( 'stripe-signature' )
		);

		$this->assertTrue( true );
	}
}

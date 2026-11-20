<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * PayPal capture must:
 * - resolve the LP order from the PayPal order id bound server-side (not from custom_id),
 * - only complete the LP order when custom_id, amount and currency match it.
 *
 * Run in separate processes: LP_Order, LP_Settings are stubbed by eval.
 * PostDB runs for real against a fake wpdb.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class LPGatewayPaypalCaptureTest extends BrainMonkeyTestCase {
	/**
	 * @var object Fake wpdb: records queries, returns $results.
	 */
	private $wpdb;

	private function load_gateway_dependencies(): void {
		if ( ! trait_exists( '\\LearnPress\\Helpers\\Singleton', false ) ) {
			eval( 'namespace LearnPress\\Helpers; trait Singleton {}' );
		}

		if ( ! class_exists( '\\LearnPress\\Helpers\\Config', false ) ) {
			eval(
				'namespace LearnPress\\Helpers;
				class Config {
					public static function instance() {
						return new self();
					}

					public function get( $id, $path ) {
						return array();
					}
				}'
			);
		}

		if ( ! class_exists( '\\WP_Error', false ) ) {
			eval(
				'class WP_Error {
					public $message = "";
					public function __construct( $code = "", $message = "" ) {
						$this->message = (string) $message;
					}
					public function get_error_message() {
						return $this->message;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LP_Gateway_Abstract', false ) ) {
			eval(
				'class LP_Gateway_Abstract {
					public function __construct() {}
					public function get_manage_subscription_url( LP_Order $order ): string {
						return "";
					}
				}'
			);
		}

		if ( ! class_exists( '\\LP_Settings', false ) ) {
			eval(
				'class LP_Settings {
					public static $options = array();
					public static function get_option( $key, $default_value = "" ) {
						return self::$options[ $key ] ?? $default_value;
					}
				}'
			);
		}

		if ( ! class_exists( '\\LP_Helper', false ) ) {
			require_once LP_INC_PATH . 'class-lp-helper.php';
		}

		if ( ! class_exists( '\\LP_Order', false ) ) {
			eval(
				'class LP_Order {
					public $id = 0;
					public $total = 0;
					public $currency = "USD";
					public $status = "pending";
					public $transaction_id = "";
					public $completed_with = null;
					public $notes = array();
					public function __construct( $id = 0 ) {
						$this->id = (int) $id;
					}
					public function get_id() {
						return $this->id;
					}
					public function get_total() {
						return $this->total;
					}
					public function get_currency() {
						return $this->currency;
					}
					public function has_status( $status ) {
						return in_array( $this->status, (array) $status, true );
					}
					public function get_transaction_id(): string {
						return $this->transaction_id;
					}
					public function payment_complete( $transaction_id = "" ): bool {
						$this->completed_with = (string) $transaction_id;
						$this->status         = "completed";
						return true;
					}
					public function add_note( $note = null ) {
						$this->notes[] = (string) $note;
					}
					public static function get_order_statuses(): array {
						return array(
							"lp-completed" => "completed",
							"lp-pending"   => "pending",
						);
					}
				}'
			);
		}

		if ( ! defined( 'LP_ORDER_COMPLETED' ) ) {
			define( 'LP_ORDER_COMPLETED', 'completed' );
		}

		if ( ! defined( 'LP_ORDER_CPT' ) ) {
			define( 'LP_ORDER_CPT', 'lp_order' );
		}

		if ( ! class_exists( '\\LP_Gateway_Paypal', false ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/gateways/paypal/class-lp-gateway-paypal.php';
		}
	}

	private function new_gateway( ?\LP_Gateway_Paypal $gateway = null ): \LP_Gateway_Paypal {
		$reflection = new \ReflectionClass( '\\LP_Gateway_Paypal' );
		if ( null === $gateway ) {
			$gateway = $reflection->newInstanceWithoutConstructor();
		}

		$props = array(
			'api_url'       => 'https://api-m.paypal.com/',
			'client_id'     => 'client',
			'client_secret' => 'secret',
		);
		foreach ( $props as $name => $value ) {
			$prop = $reflection->getProperty( $name );
			if ( PHP_VERSION_ID < 80100 ) {
				$prop->setAccessible( true );
			}
			$prop->setValue( $gateway, $value );
		}

		return $gateway;
	}

	private function new_order( int $id = 29, $total = 100, string $currency = 'USD' ): \LP_Order {
		$order           = new \LP_Order( $id );
		$order->total    = $total;
		$order->currency = $currency;
		$order->status   = 'pending';

		return $order;
	}

	private function capture( $custom_id = 29, string $value = '100.00', string $currency = 'USD', string $status = 'COMPLETED' ) {
		return json_decode(
			json_encode(
				array(
					'id'        => 'CAPTURE1',
					'status'    => $status,
					'custom_id' => (string) $custom_id,
					'amount'    => array(
						'currency_code' => $currency,
						'value'         => $value,
					),
				)
			)
		);
	}

	/**
	 * Bind PayPal order id to $order: DB query returns its ID, learn_press_get_order returns it.
	 */
	private function bind_order( \LP_Order $order ): void {
		$this->wpdb->results = array( (object) array( 'ID' => (string) $order->get_id() ) );
		Functions\when( 'learn_press_get_order' )->justReturn( $order );
	}

	private function paypal_returns_capture( $capture ): void {
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				array(
					'body' => json_encode(
						array(
							'status'         => 'COMPLETED',
							'purchase_units' => array(
								array( 'payments' => array( 'captures' => array( $capture ) ) ),
							),
						)
					),
				)
			);
	}

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'absint' )->alias(
			function ( $value ): int {
				return abs( (int) $value );
			}
		);
		Functions\when( 'is_wp_error' )->alias(
			function ( $value ): bool {
				return $value instanceof \WP_Error;
			}
		);
		Functions\when( 'wp_remote_retrieve_body' )->alias(
			function ( $response ): string {
				return is_array( $response ) ? (string) ( $response['body'] ?? '' ) : '';
			}
		);
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'wp_date' )->justReturn( '2026-11-20 00:00:00' );
		Functions\when( 'wp_timezone_string' )->justReturn( 'UTC' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'learn_press_get_order' )->justReturn( false );

		$this->wpdb = new class() {
			public $prefix             = 'wp_';
			public $users              = 'wp_users';
			public $posts              = 'wp_posts';
			public $postmeta           = 'wp_postmeta';
			public $options            = 'wp_options';
			public $terms              = 'wp_terms';
			public $term_relationships = 'wp_term_relationships';
			public $term_taxonomy      = 'wp_term_taxonomy';
			public $last_error         = '';
			public $queries            = array();
			public $results            = array();

			public function hide_errors() {}

			public function has_cap( $cap ) {
				return false;
			}

			public function prepare( $query, ...$args ) {
				if ( isset( $args[0] ) && is_array( $args[0] ) ) {
					$args = $args[0];
				}

				return vsprintf( str_replace( '%s', "'%s'", $query ), $args );
			}

			public function get_results( $query ) {
				$this->queries[] = preg_replace( '/\s+/', ' ', $query );

				return $this->results;
			}
		};

		$GLOBALS['wpdb'] = $this->wpdb;

		$this->load_gateway_dependencies();

		\LP_Settings::$options = array(
			'paypal_token' => json_encode(
				array(
					'access_token' => 'token_123',
					'token_type'   => 'Bearer',
					'lp_time_end'  => time() + 3600,
				)
			),
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wpdb'] );
		parent::tearDown();
	}

	public function test_get_lp_order_by_paypal_order_id_rejects_malformed_id(): void {
		$gateway = $this->new_gateway();

		foreach ( array( '', '../../v1/oauth2', 'abc123', 'ABC?x=1' ) as $id ) {
			$this->assertNull( $gateway->get_lp_order_by_paypal_order_id( $id ) );
		}
		$this->assertSame( array(), $this->wpdb->queries );
	}

	public function test_create_paypal_order_binds_returned_id_to_lp_order(): void {
		$gateway = $this->new_gateway(
			new class() extends \LP_Gateway_Paypal {
				public function __construct() {}

				public function get_order_args( \LP_Order $order ): array {
					return array();
				}
			}
		);

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( array( 'body' => json_encode( array( 'id' => 'NEWPAYPALID' ) ) ) );
		Functions\expect( 'add_post_meta' )
			->once()
			->with( 29, \LP_Gateway_Paypal::META_PAYPAL_ORDER_ID, 'NEWPAYPALID' );

		$result = $gateway->create_paypal_order( $this->new_order() );

		$this->assertSame( 'NEWPAYPALID', $result->id );
	}

	public function test_get_lp_order_by_paypal_order_id_returns_null_for_unknown_id(): void {
		$this->assertNull( $this->new_gateway()->get_lp_order_by_paypal_order_id( 'UNKNOWN' ) );
	}

	public function test_get_lp_order_by_paypal_order_id_returns_null_when_bound_to_many_orders(): void {
		$this->wpdb->results = array( (object) array( 'ID' => '29' ), (object) array( 'ID' => '30' ) );

		$this->assertNull( $this->new_gateway()->get_lp_order_by_paypal_order_id( 'PAYPALORDER1' ) );
	}

	public function test_get_lp_order_by_paypal_order_id_returns_bound_order(): void {
		$order = $this->new_order();
		$this->bind_order( $order );

		$this->assertSame( $order, $this->new_gateway()->get_lp_order_by_paypal_order_id( 'PAYPALORDER1' ) );

		$sql = $this->wpdb->queries[0];
		$this->assertStringContainsString( "p.post_type = 'lp_order'", $sql );
		$this->assertStringContainsString( "pm.meta_key = '_lp_paypal_order_id' AND pm.meta_value = 'PAYPALORDER1'", $sql );
		$this->assertStringContainsString( "p.post_status IN ('lp-completed','lp-pending')", $sql );
		$this->assertStringContainsString( 'LIMIT 0, 2', $sql );
	}

	public function test_validate_capture_accepts_full_payment(): void {
		$this->new_gateway()->validate_capture_for_order( $this->new_order( 29, '100' ), $this->capture( 29, '100.00' ) );

		$this->addToAssertionCount( 1 );
	}

	public function test_validate_capture_rejects_partial_amount(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'does not match order total' );

		$this->new_gateway()->validate_capture_for_order( $this->new_order(), $this->capture( 29, '0.01' ) );
	}

	public function test_validate_capture_rejects_other_currency(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'does not match order total' );

		$this->new_gateway()->validate_capture_for_order( $this->new_order(), $this->capture( 29, '100.00', 'JPY' ) );
	}

	public function test_validate_capture_rejects_other_custom_id(): void {
		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'does not belong to this order' );

		$this->new_gateway()->validate_capture_for_order( $this->new_order(), $this->capture( 30 ) );
	}

	public function test_validate_capture_rejects_not_completed_status(): void {
		$this->expectException( \Exception::class );

		$this->new_gateway()->validate_capture_for_order( $this->new_order(), $this->capture( 29, '100.00', 'USD', 'PENDING' ) );
	}

	public function test_capture_does_not_call_paypal_for_unbound_paypal_order(): void {
		Functions\expect( 'wp_remote_post' )->never();

		$this->assertFalse( $this->new_gateway()->capture_payment_for_order( 'ATTACKERORDER' ) );
	}

	public function test_capture_does_not_call_paypal_for_malformed_id(): void {
		Functions\expect( 'wp_remote_post' )->never();

		$this->assertFalse( $this->new_gateway()->capture_payment_for_order( 'X/../../v1' ) );
		$this->assertSame( array(), $this->wpdb->queries );
	}

	public function test_capture_rejects_underpaid_capture_and_keeps_order_pending(): void {
		$order = $this->new_order();
		$this->bind_order( $order );
		$this->paypal_returns_capture( $this->capture( 29, '0.01' ) );

		try {
			$this->new_gateway()->capture_payment_for_order( 'PAYPALORDER1' );
			$this->fail( 'Expected exception for underpaid capture.' );
		} catch ( \Exception $e ) {
			$this->assertStringContainsString( 'does not match order total', $e->getMessage() );
		}

		$this->assertNull( $order->completed_with );
		$this->assertSame( 'pending', $order->status );
		$this->assertCount( 1, $order->notes );
	}

	public function test_capture_completes_order_on_matching_capture(): void {
		$order = $this->new_order();
		$this->bind_order( $order );
		$this->paypal_returns_capture( $this->capture( 29, '100.00' ) );

		$this->assertTrue( $this->new_gateway()->capture_payment_for_order( 'PAYPALORDER1' ) );
		$this->assertSame( 'CAPTURE1', $order->completed_with );
	}

	public function test_capture_skips_paypal_when_order_already_completed(): void {
		$order         = $this->new_order();
		$order->status = 'completed';
		$this->bind_order( $order );
		Functions\expect( 'wp_remote_post' )->never();

		$this->assertTrue( $this->new_gateway()->capture_payment_for_order( 'PAYPALORDER1' ) );
		$this->assertNull( $order->completed_with );
	}
}

<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for LP_Gateway_Paypal::capture_payment_for_order().
 * Run in separate processes: LP_Order, LP_Settings, LP_Helper are stubbed by eval.
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class LPGatewayPaypalCaptureTest extends BrainMonkeyTestCase {
	private string $paypal_token_option = '';

	private function set_paypal_token_option( string $value ): void {
		$this->paypal_token_option = $value;

		if ( class_exists( '\\LP_Settings', false ) && property_exists( '\\LP_Settings', 'options' ) ) {
			\LP_Settings::$options = array(
				'paypal_token' => $value,
			);
		}
	}

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
					public $code = "";
					public $message = "";
					public function __construct( $code = "", $message = "" ) {
						$this->code = (string) $code;
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
					public static function get_option( $key ) {
						return self::$options[ $key ] ?? "";
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

		if ( ! class_exists( '\\LP_Order', false ) ) {
			eval(
				'class LP_Order {
					public $transaction_id = "";
					public $completed_with = "";
					public $notes = array();
					public function get_transaction_id() {
						return $this->transaction_id;
					}
					public function payment_complete( $transaction_id = "" ) {
						$this->completed_with = (string) $transaction_id;
					}
					public function add_note( $note ) {
						$this->notes[] = (string) $note;
					}
				}'
			);
		}

		if ( ! defined( 'LP_ORDER_COMPLETED' ) ) {
			define( 'LP_ORDER_COMPLETED', 'completed' );
		}

		if ( ! class_exists( '\\LP_Gateway_Paypal', false ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/gateways/paypal/class-lp-gateway-paypal.php';
		}
	}

	private function new_gateway_without_constructor( bool $has_client = true ): \LP_Gateway_Paypal {
		$reflection = new \ReflectionClass( '\\LP_Gateway_Paypal' );
		$gateway    = $reflection->newInstanceWithoutConstructor();

		if ( $has_client ) {
			foreach ( array( 'client_id' => 'client_123', 'client_secret' => 'secret_123' ) as $property => $value ) {
				$prop = $reflection->getProperty( $property );
				if ( PHP_VERSION_ID < 80100 ) {
					$prop->setAccessible( true );
				}
				$prop->setValue( $gateway, $value );
			}
		}

		$api_url = $reflection->getProperty( 'api_url' );
		// setAccessible() is needed only for PHP < 8.1, deprecated since PHP 8.5.
		if ( PHP_VERSION_ID < 80100 ) {
			$api_url->setAccessible( true );
		}
		$api_url->setValue( $gateway, 'https://api-m.paypal.com/' );

		return $gateway;
	}

	protected function setUp(): void {
		parent::setUp();
		$self = $this;

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
				if ( is_array( $response ) && isset( $response['body'] ) ) {
					return (string) $response['body'];
				}

				return '';
			}
		);
		Functions\when( 'get_option' )->alias(
			function ( $option, $default = false ) use ( $self ) {
				if ( 'learn_press_paypal_token' === $option ) {
					if ( '' !== $self->paypal_token_option ) {
						return $self->paypal_token_option;
					}
				}

				return $default;
			}
		);
		Functions\when( 'learn_press_get_order' )->justReturn( false );

		$this->load_gateway_dependencies();
	}

	/**
	 * Save a not expired token, so get_access_token() not request PayPal.
	 */
	private function set_valid_token(): void {
		$this->set_paypal_token_option(
			json_encode(
				array(
					'access_token' => 'token_123',
					'token_type'   => 'Bearer',
					'lp_time_end'  => time() + 3600,
				)
			)
		);
	}

	private function capture_response( array $capture ): array {
		return array(
			'response' => array( 'code' => 201 ),
			'body'     => json_encode(
				array(
					'status'         => 'COMPLETED',
					'purchase_units' => array(
						array( 'payments' => array( 'captures' => array( $capture ) ) ),
					),
				)
			),
		);
	}

	public function test_capture_payment_throws_when_client_id_is_missing(): void {
		Functions\expect( 'wp_remote_post' )->never();

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Paypal Client id is required.' );

		$this->new_gateway_without_constructor( false )->capture_payment_for_order( 'PAYPAL_ORDER_1' );
	}

	public function test_capture_payment_throws_when_remote_request_fails(): void {
		$this->set_valid_token();
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( new \WP_Error( 'http_error', 'request failed' ) );

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'request failed' );

		$this->new_gateway_without_constructor()->capture_payment_for_order( 'PAYPAL_ORDER_2' );
	}

	public function test_capture_payment_throws_paypal_error_description(): void {
		$this->set_valid_token();
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				array(
					'response' => array( 'code' => 422 ),
					'body'     => json_encode(
						array(
							'debug_id' => 'abc',
							'details'  => array( array( 'description' => 'Order already captured.' ) ),
						)
					),
				)
			);

		$this->expectException( \Exception::class );
		$this->expectExceptionMessage( 'Order already captured.' );

		$this->new_gateway_without_constructor()->capture_payment_for_order( 'PAYPAL_ORDER_3' );
	}

	public function test_capture_payment_returns_false_when_response_has_no_capture(): void {
		$this->set_valid_token();
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				array(
					'response' => array( 'code' => 201 ),
					'body'     => '{}',
				)
			);

		$this->assertFalse( $this->new_gateway_without_constructor()->capture_payment_for_order( 'PAYPAL_ORDER_4' ) );
	}

	public function test_capture_payment_returns_false_when_capture_has_no_order_id(): void {
		$this->set_valid_token();
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( $this->capture_response( array( 'id' => 'CAPTURE_1', 'status' => 'COMPLETED' ) ) );

		$this->assertFalse( $this->new_gateway_without_constructor()->capture_payment_for_order( 'PAYPAL_ORDER_5' ) );
	}

	public function test_capture_payment_completes_order_when_capture_completed(): void {
		$this->set_valid_token();
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				$this->capture_response(
					array(
						'id'        => 'CAPTURE_1',
						'custom_id' => 777,
						'status'    => 'COMPLETED',
					)
				)
			);
		Functions\when( 'wp_date' )->justReturn( '2026-10-28 10:00:00' );
		Functions\when( 'wp_timezone_string' )->justReturn( 'UTC' );

		$order = new \LP_Order();
		// setUp() stubbed learn_press_get_order with when(), override it the same way.
		Functions\when( 'learn_press_get_order' )->alias(
			static function ( $order_id ) use ( $order ) {
				return 777 === $order_id ? $order : false;
			}
		);

		$result = $this->new_gateway_without_constructor()->capture_payment_for_order( 'PAYPAL_ORDER_6' );

		$this->assertTrue( $result );
		$this->assertSame( 'CAPTURE_1', $order->completed_with );
		$this->assertCount( 1, $order->notes );
		$this->assertStringContainsString( 'CAPTURE_1', $order->notes[0] );
	}

	public function test_capture_payment_not_complete_again_when_transaction_id_same(): void {
		$this->set_valid_token();
		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				$this->capture_response(
					array(
						'id'        => 'CAPTURE_1',
						'custom_id' => 777,
						'status'    => 'COMPLETED',
					)
				)
			);

		$order                 = new \LP_Order();
		$order->transaction_id = 'CAPTURE_1';
		Functions\when( 'learn_press_get_order' )->justReturn( $order );

		$this->assertTrue( $this->new_gateway_without_constructor()->capture_payment_for_order( 'PAYPAL_ORDER_7' ) );
		$this->assertSame( '', $order->completed_with );
		$this->assertSame( array(), $order->notes );
	}
}

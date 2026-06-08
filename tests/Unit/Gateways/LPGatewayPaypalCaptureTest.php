<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

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
					public $updated_status = "";
					public function update_status( $status ) {
						$this->updated_status = (string) $status;
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

	private function new_gateway_without_constructor(): \LP_Gateway_Paypal {
		$reflection = new \ReflectionClass( '\\LP_Gateway_Paypal' );
		$gateway    = $reflection->newInstanceWithoutConstructor();

		$api_url = $reflection->getProperty( 'api_url' );
		$api_url->setAccessible( true );
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

	public function test_capture_payment_returns_false_when_token_is_missing(): void {
		$this->set_paypal_token_option( '' );

		Functions\expect( 'wp_remote_post' )->never();

		$gateway = $this->new_gateway_without_constructor();
		$result  = $gateway->capture_payment_for_order( 'PAYPAL_ORDER_1' );

		$this->assertFalse( $result );
	}

	public function test_capture_payment_returns_false_when_remote_request_fails(): void {
		$this->set_paypal_token_option(
			wp_json_encode(
				array(
					'access_token' => 'token_123',
					'token_type'   => 'Bearer',
				)
			)
		);

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn( new \WP_Error( 'http_error', 'request failed' ) );

		$gateway = $this->new_gateway_without_constructor();
		$result  = $gateway->capture_payment_for_order( 'PAYPAL_ORDER_2' );

		$this->assertFalse( $result );
	}

	public function test_capture_payment_returns_false_when_http_code_is_not_201(): void {
		$this->set_paypal_token_option(
			wp_json_encode(
				array(
					'access_token' => 'token_123',
					'token_type'   => 'Bearer',
				)
			)
		);

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				array(
					'response' => array(
						'code' => 422,
					),
					'body'     => '{}',
				)
			);

		$gateway = $this->new_gateway_without_constructor();
		$result  = $gateway->capture_payment_for_order( 'PAYPAL_ORDER_3' );

		$this->assertFalse( $result );
	}

	public function test_capture_payment_returns_true_and_updates_order_status_when_completed(): void {
		$this->set_paypal_token_option(
			wp_json_encode(
				array(
					'access_token' => 'token_123',
					'token_type'   => 'Bearer',
				)
			)
		);

		Functions\expect( 'wp_remote_post' )
			->once()
			->andReturn(
				array(
					'response' => array(
						'code' => 201,
					),
					'body'     => wp_json_encode(
						array(
							'status'         => 'COMPLETED',
							'purchase_units' => array(
								array(
									'payments' => array(
										'captures' => array(
											array(
												'custom_id' => 777,
											),
										),
									),
								),
							),
						)
					),
				)
			);

		$order = new \LP_Order();
		Functions\when( 'learn_press_get_order' )->justReturn( $order );

		$gateway = $this->new_gateway_without_constructor();
		$result  = $gateway->capture_payment_for_order( 'PAYPAL_ORDER_4' );

		$this->assertTrue( $result );
		$this->assertSame( LP_ORDER_COMPLETED, $order->updated_status );
	}
}

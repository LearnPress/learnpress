<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\RestApi;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

/**
 * Tests for LP_REST_Gateway_Webhook_Controller::listen_subscription_webhook().
 *
 * Providers (PayPal, Stripe) re-send a webhook only on a non-2xx answer, so the
 * HTTP status must reflect what happened:
 * - processed / intentionally ignored (exception code 2xx) → 200
 * - 4xx exception (bad signature, unknown gateway) → that code
 * - any other error (timeout, DB error) → 500, without internal details in the body
 */
class GatewayWebhookControllerTest extends BrainMonkeyTestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! class_exists( 'WP_REST_Controller' ) ) {
			eval( 'class WP_REST_Controller {}' );
		}
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			eval( 'class WP_REST_Server { const ALLMETHODS = "GET, POST, PUT, PATCH, DELETE"; }' );
		}
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			eval(
				'class WP_REST_Request {
					private $params = array();
					public function get_param( $key ) { return $this->params[ $key ] ?? null; }
					public function set_param( $key, $val ) { $this->params[ $key ] = $val; }
					public function get_body() { return ""; }
					public function get_header( $key ) { return null; }
				}'
			);
		}
		if ( ! class_exists( 'WP_REST_Response' ) ) {
			eval(
				'class WP_REST_Response {
					public $data;
					public $status;
					public function __construct( $data = null, $status = 200 ) {
						$this->data   = $data;
						$this->status = $status;
					}
					public function get_data() { return $this->data; }
					public function get_status() { return $this->status; }
				}'
			);
		}
		if ( ! class_exists( 'LP_Debug' ) ) {
			eval(
				'class LP_Debug {
					public static function log_to_comment( $content ) {}
					public static function error_log( $e ) {}
				}'
			);
		}
		if ( ! class_exists( 'LP_Abstract_Settings' ) ) {
			eval( 'abstract class LP_Abstract_Settings {}' );
		}
		if ( ! class_exists( 'LP_Gateway_Abstract' ) ) {
			require LP_INC_PATH . 'gateways/class-lp-gateway-abstract.php';
		}
		if ( ! class_exists( 'LP_Gateways' ) ) {
			eval(
				'class LP_Gateways {
					public static $gateway = null;
					public static function instance() { return new self(); }
					public function get_gateway( $id ) { return self::$gateway; }
				}'
			);
		}
		if ( ! class_exists( 'LP_Abstract_REST_Controller' ) ) {
			require LP_INC_PATH . 'abstracts/abstract-rest-controller.php';
		}
		if ( ! class_exists( 'LP_REST_Gateway_Webhook_Controller' ) ) {
			require LP_INC_PATH . 'rest-api/v1/frontend/class-lp-rest-gateway-webhook-controller.php';
		}
	}

	protected function setUp(): void {
		parent::setUp();

		\LP_Gateways::$gateway = null;
		Functions\when( 'sanitize_key' )->returnArg();
	}

	private function make_request( string $gateway = 'paypal' ): \WP_REST_Request {
		$request = new \WP_REST_Request();
		$request->set_param( 'gateway', $gateway );

		return $request;
	}

	private function make_gateway( bool $enabled = true ): Mockery\MockInterface {
		$gateway = Mockery::mock( \LP_Gateway_Abstract::class );
		$gateway->shouldReceive( 'is_enabled' )->andReturn( $enabled );

		return $gateway;
	}

	private function call_controller( string $gateway_id = 'paypal' ): \WP_REST_Response {
		return ( new \LP_REST_Gateway_Webhook_Controller() )
			->listen_subscription_webhook( $this->make_request( $gateway_id ) );
	}

	public function test_listen_subscription_webhook_returns_400_when_gateway_is_empty(): void {
		$response = $this->call_controller( '' );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'error', $response->get_data()->status );
		$this->assertSame( 'Gateway is required.', $response->get_data()->message );
	}

	public function test_listen_subscription_webhook_returns_404_when_gateway_not_found(): void {
		$response = $this->call_controller( 'unknown' );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'Gateway not found.', $response->get_data()->message );
	}

	public function test_listen_subscription_webhook_returns_404_when_gateway_is_disabled(): void {
		$gateway = $this->make_gateway( false );
		$gateway->shouldNotReceive( 'capture_subscription_webhook' );
		\LP_Gateways::$gateway = $gateway;

		$response = $this->call_controller();

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_listen_subscription_webhook_returns_200_on_success(): void {
		$gateway = $this->make_gateway();
		$gateway->shouldReceive( 'capture_subscription_webhook' )->once();
		\LP_Gateways::$gateway = $gateway;

		$response = $this->call_controller();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response->get_data()->status );
	}

	public function test_listen_subscription_webhook_returns_200_when_event_is_ignored(): void {
		$gateway = $this->make_gateway();
		$gateway->shouldReceive( 'capture_subscription_webhook' )
			->andThrow( new Exception( 'PayPal webhook event already processed.', 200 ) );
		\LP_Gateways::$gateway = $gateway;

		$response = $this->call_controller();

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response->get_data()->status );
		$this->assertSame( 'PayPal webhook event already processed.', $response->get_data()->message );
	}

	public function test_listen_subscription_webhook_returns_500_without_details_on_transient_error(): void {
		$gateway = $this->make_gateway();
		// wp_remote_post timeout → Exception without code.
		$gateway->shouldReceive( 'capture_subscription_webhook' )
			->andThrow( new Exception( 'cURL error 28: Operation timed out after 60001 milliseconds' ) );
		\LP_Gateways::$gateway = $gateway;

		$response = $this->call_controller();

		$this->assertSame( 500, $response->get_status() );
		$this->assertSame( 'error', $response->get_data()->status );
		$this->assertStringNotContainsString( 'cURL', $response->get_data()->message );
	}

	public function test_listen_subscription_webhook_returns_500_on_php_error(): void {
		$gateway = $this->make_gateway();
		$gateway->shouldReceive( 'capture_subscription_webhook' )->andThrow( new \TypeError( 'bad type' ) );
		\LP_Gateways::$gateway = $gateway;

		$response = $this->call_controller();

		$this->assertSame( 500, $response->get_status() );
	}

	public function test_listen_subscription_webhook_returns_exception_4xx_code(): void {
		$gateway = $this->make_gateway();
		$gateway->shouldReceive( 'capture_subscription_webhook' )
			->andThrow( new Exception( 'PayPal webhook verification failed.', 400 ) );
		\LP_Gateways::$gateway = $gateway;

		$response = $this->call_controller();

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'PayPal webhook verification failed.', $response->get_data()->message );
	}

	public function test_get_http_status_from_throwable_keeps_2xx(): void {
		$this->assertSame( 200, \LP_REST_Gateway_Webhook_Controller::get_http_status_from_throwable( new Exception( '', 200 ) ) );
	}

	public function test_get_http_status_from_throwable_keeps_4xx(): void {
		$this->assertSame( 422, \LP_REST_Gateway_Webhook_Controller::get_http_status_from_throwable( new Exception( '', 422 ) ) );
		$this->assertSame( 499, \LP_REST_Gateway_Webhook_Controller::get_http_status_from_throwable( new Exception( '', 499 ) ) );
	}

	public function test_get_http_status_from_throwable_maps_other_codes_to_500(): void {
		foreach ( array( 0, 1, 199, 302, 399, 500, 503, 1045 ) as $code ) {
			$this->assertSame(
				500,
				\LP_REST_Gateway_Webhook_Controller::get_http_status_from_throwable( new Exception( '', $code ) ),
				"code $code"
			);
		}
	}

	public function test_get_http_status_from_throwable_handles_non_numeric_code(): void {
		// PDOException::getCode() returns the SQLSTATE string, e.g. "HY000".
		$e    = new \PDOException( 'db error' );
		$prop = new \ReflectionProperty( \Exception::class, 'code' );
		if ( PHP_VERSION_ID < 80100 ) {
			$prop->setAccessible( true );
		}
		$prop->setValue( $e, 'HY000' );

		$this->assertSame( 500, \LP_REST_Gateway_Webhook_Controller::get_http_status_from_throwable( $e ) );
	}
}

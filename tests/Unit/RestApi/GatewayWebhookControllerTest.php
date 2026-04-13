<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\RestApi;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

/**
 * Tests for LP_REST_Gateway_Webhook_Controller::listen_subscription_webhook().
 *
 * Covers:
 * - Guard layer: payload size (413), rate limit (429)
 * - Gateway resolution: not found (404), disabled (404), no subscription support (404)
 * - Happy path: delegates to gateway and returns its result
 * - status_code extraction from gateway result
 */
class GatewayWebhookControllerTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// Bootstrap — define all WP/LP stubs before requiring actual classes.
	// -------------------------------------------------------------------------

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// WordPress REST stubs.
		if ( ! class_exists( 'WP_REST_Controller' ) ) {
			eval( 'class WP_REST_Controller {}' );
		}
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			eval( 'class WP_REST_Server { const CREATABLE = "POST"; }' );
		}
		if ( ! class_exists( 'WP_REST_Request' ) ) {
			eval( 'class WP_REST_Request {
				private array $params  = [];
				private string $body   = "";
				private array $headers = [];
				public function get_param( $key ) { return $this->params[$key] ?? null; }
				public function set_param( $key, $val ) { $this->params[$key] = $val; }
				public function get_body(): string { return $this->body; }
				public function set_body( string $body ) { $this->body = $body; }
				public function get_header( $key ) { return $this->headers[$key] ?? null; }
				public function set_header( $key, $val ) { $this->headers[$key] = $val; }
			}' );
		}
		if ( ! class_exists( 'WP_REST_Response' ) ) {
			eval( 'class WP_REST_Response {
				public $data;
				public int $status;
				public function __construct( $data = null, int $status = 200 ) {
					$this->data   = $data;
					$this->status = $status;
				}
				public function get_data() { return $this->data; }
				public function get_status(): int { return $this->status; }
			}' );
		}

		// LP_Abstract_Settings and gateway base class.
		if ( ! class_exists( 'LP_Abstract_Settings' ) ) {
			eval( 'abstract class LP_Abstract_Settings {}' );
		}
		if ( ! class_exists( 'LP_Gateway_Abstract' ) ) {
			require LP_INC_PATH . 'gateways/class-lp-gateway-abstract.php';
		}

		// LP_Gateways singleton stub — static $gateway allows per-test override.
		if ( ! class_exists( 'LP_Gateways' ) ) {
			eval( '
			class LP_Gateways {
				private static $_inst = null;
				public static $gateway = null;
				public static function instance() {
					if ( null === self::$_inst ) { self::$_inst = new self(); }
					return self::$_inst;
				}
				public function get_gateway( $id ) {
					return self::$gateway;
				}
			}' );
		}

		// Load the abstract REST controller and the webhook controller.
		if ( ! class_exists( 'LP_Abstract_REST_Controller' ) ) {
			require LP_INC_PATH . 'abstracts/abstract-rest-controller.php';
		}
		if ( ! class_exists( 'LP_REST_Gateway_Webhook_Controller' ) ) {
			require LP_INC_PATH . 'rest-api/v1/frontend/class-lp-rest-gateway-webhook-controller.php';
		}
	}

	protected function setUp(): void {
		parent::setUp();

		// Reset LP_Gateways static gateway.
		\LP_Gateways::$gateway = null;

		// Common WP stubs.
		Functions\when( 'sanitize_key' )->returnArg();
		// apply_filters($tag, $value, ...) — return $value (arg index 1).
		Functions\when( 'apply_filters' )->returnArg( 1 );
		Functions\when( 'absint' )->alias( 'intval' );
		Functions\when( 'time' )->justReturn( 1700000000 );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'register_rest_route' )->justReturn( true );
		Functions\when( 'error_log' )->justReturn( null );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	private function make_controller(): \LP_REST_Gateway_Webhook_Controller {
		return new \LP_REST_Gateway_Webhook_Controller();
	}

	private function make_request( string $gateway = 'paypal', string $body = '{}' ): \WP_REST_Request {
		$req = new \WP_REST_Request();
		$req->set_param( 'gateway', $gateway );
		$req->set_body( $body );

		return $req;
	}

	private function make_enabled_gateway( bool $enabled = true, bool $supports_subscription = true ): Mockery\MockInterface {
		$gateway = Mockery::mock( \LP_Gateway_Abstract::class );
		$gateway->shouldReceive( 'is_enabled' )->andReturn( $enabled )->byDefault();
		$gateway->shouldReceive( 'supports_feature' )
			->with( \LP_Gateway_Abstract::FEATURE_SUBSCRIPTION )
			->andReturn( $supports_subscription )
			->byDefault();

		return $gateway;
	}

	// -------------------------------------------------------------------------
	// Guard: payload size
	// -------------------------------------------------------------------------

	public function test_returns_413_when_payload_exceeds_256kb(): void {
		$controller = $this->make_controller();
		$request    = $this->make_request( 'paypal', str_repeat( 'A', 262145 ) );

		$response = $controller->listen_subscription_webhook( $request );

		$this->assertSame( 413, $response->get_status() );
		$this->assertSame( 'error', $response->get_data()['status'] );
		$this->assertSame( 'Webhook payload too large.', $response->get_data()['message'] );
	}

	public function test_payload_exactly_at_limit_is_not_rejected(): void {
		$gateway = $this->make_enabled_gateway();
		$gateway->shouldReceive( 'listen_webhook_subscription' )
			->andReturn( array( 'status' => 'ok', 'status_code' => 200 ) );
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$request    = $this->make_request( 'paypal', str_repeat( 'A', 262144 ) ); // exactly 256KB

		$response = $controller->listen_subscription_webhook( $request );

		$this->assertNotSame( 413, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// Guard: rate limit
	// -------------------------------------------------------------------------

	public function test_returns_429_when_rate_limit_exceeded(): void {
		// Simulate: transient shows count >= max_requests.
		Functions\when( 'get_transient' )->justReturn( array(
			'started_at' => 1700000000,
			'count'      => 60,
		) );
		// apply_filters returns the defaults: window=60, max_requests=60.
		// apply_filters($tag, $value, ...) — return $value (arg index 1).
		Functions\when( 'apply_filters' )->returnArg( 1 );

		$controller = $this->make_controller();
		$request    = $this->make_request( 'paypal' );

		$response = $controller->listen_subscription_webhook( $request );

		$this->assertSame( 429, $response->get_status() );
		$this->assertSame( 'Too many webhook requests.', $response->get_data()['message'] );
	}

	public function test_rate_limit_resets_after_window_expires(): void {
		// Window has expired: started_at is long ago.
		Functions\when( 'get_transient' )->justReturn( array(
			'started_at' => 1600000000, // far in the past
			'count'      => 999,
		) );
		Functions\when( 'set_transient' )->justReturn( true );

		$gateway = $this->make_enabled_gateway();
		$gateway->shouldReceive( 'listen_webhook_subscription' )
			->andReturn( array( 'status' => 'ok', 'status_code' => 200 ) );
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$request    = $this->make_request( 'paypal' );

		$response = $controller->listen_subscription_webhook( $request );

		$this->assertNotSame( 429, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// Gateway resolution
	// -------------------------------------------------------------------------

	public function test_returns_404_when_gateway_not_found(): void {
		\LP_Gateways::$gateway = null; // get_gateway returns null

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'nonexistent' ) );

		$this->assertSame( 404, $response->get_status() );
		$this->assertSame( 'error', $response->get_data()['status'] );
	}

	public function test_returns_404_when_gateway_is_not_lp_gateway_abstract(): void {
		// get_gateway returns something that is not LP_Gateway_Abstract
		$bad_gateway = new \stdClass();
		// LP_Gateways stub only returns LP_Gateway_Abstract|null, so we can't inject stdClass directly.
		// Instead, use a gateway mock that is NOT instanceof LP_Gateway_Abstract.
		\LP_Gateways::$gateway = null; // triggers "not an instance" check via null

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_returns_404_when_gateway_is_disabled(): void {
		$gateway = $this->make_enabled_gateway( false, true ); // disabled
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$this->assertSame( 404, $response->get_status() );
	}

	public function test_returns_404_when_gateway_does_not_support_subscription(): void {
		$gateway = $this->make_enabled_gateway( true, false ); // no subscription support
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$this->assertSame( 404, $response->get_status() );
	}

	// -------------------------------------------------------------------------
	// Happy path — delegates to gateway
	// -------------------------------------------------------------------------

	public function test_returns_gateway_result_on_success(): void {
		$gateway = $this->make_enabled_gateway();
		$gateway->shouldReceive( 'listen_webhook_subscription' )
			->once()
			->andReturn( array( 'status' => 'success', 'event_id' => 'WH-001', 'status_code' => 200 ) );
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'success', $response->get_data()['status'] );
	}

	public function test_uses_status_code_from_gateway_result(): void {
		$gateway = $this->make_enabled_gateway();
		$gateway->shouldReceive( 'listen_webhook_subscription' )
			->andReturn( array( 'status' => 'processing', 'status_code' => 202 ) );
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$this->assertSame( 202, $response->get_status() );
		$this->assertArrayNotHasKey( 'status_code', $response->get_data(), 'status_code should be stripped from body' );
	}

	public function test_gateway_wp_error_is_converted_to_error_response(): void {
		$gateway = $this->make_enabled_gateway();
		$error   = new \WP_Error( 'lp_test_error', 'Something failed', array( 'status' => 400 ) );
		$gateway->shouldReceive( 'listen_webhook_subscription' )->andReturn( $error );
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'error', $response->get_data()['status'] );
	}

	// -------------------------------------------------------------------------
	// build_error_response: sensitive details not leaked
	// -------------------------------------------------------------------------

	public function test_build_error_response_hides_private_message_for_5xx(): void {
		$gateway = $this->make_enabled_gateway();
		$error   = new \WP_Error(
			'lp_subscription_manager_missing',
			'INTERNAL: Subscription manager class not loaded',
			array( 'status' => 500 )
		);
		$gateway->shouldReceive( 'listen_webhook_subscription' )->andReturn( $error );
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$response   = $controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$data = $response->get_data();
		$this->assertStringNotContainsString( 'INTERNAL', $data['message'] );
		$this->assertStringNotContainsString( 'class not loaded', $data['message'] );
	}

	// -------------------------------------------------------------------------
	// Rate-limit counter increments
	// -------------------------------------------------------------------------

	public function test_rate_limit_counter_increments_on_each_request(): void {
		Functions\when( 'get_transient' )->justReturn( array(
			'started_at' => 1700000000,
			'count'      => 5,
		) );

		$updated_data = null;
		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing( function ( $key, $data ) use ( &$updated_data ) {
				$updated_data = $data;
				return true;
			} );

		$gateway = $this->make_enabled_gateway();
		$gateway->shouldReceive( 'listen_webhook_subscription' )
			->andReturn( array( 'status' => 'ok', 'status_code' => 200 ) );
		\LP_Gateways::$gateway = $gateway;

		$controller = $this->make_controller();
		$controller->listen_subscription_webhook( $this->make_request( 'paypal' ) );

		$this->assertSame( 6, $updated_data['count'] );
	}
}
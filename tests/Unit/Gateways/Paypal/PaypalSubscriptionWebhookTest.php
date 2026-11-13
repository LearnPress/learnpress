<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways\Paypal;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use RuntimeException;

/**
 * Tests for PayPal subscription webhook re-delivery handling in LP_Gateway_Paypal:
 * - processed event ids stored as a JSON array (get_webhook_events_processed / is_webhook_event_processed)
 * - capture_subscription_webhook(): duplicate events ignored, events saved only on success
 * - events that must be acknowledged (200), not retried, throw an Exception with code 200
 */
class PaypalSubscriptionWebhookTest extends BrainMonkeyTestCase {

	const ORDER_ID        = 10;
	const SUBSCRIPTION_ID = 'I-SUB1';
	const META_PROCESSED  = '_lp_paypal_webhook_event_processed';

	/**
	 * Raw value returned by get_post_meta( order, META_PROCESSED, true ).
	 *
	 * @var mixed
	 */
	private $processed_meta = '';

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		if ( ! class_exists( 'WP_REST_Request' ) ) {
			eval(
				'class WP_REST_Request {
					private $body = "";
					public function get_param( $key ) { return null; }
					public function set_body( $body ) { $this->body = $body; }
					public function get_body() { return $this->body; }
					public function get_header( $key ) { return ""; }
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
		if ( ! class_exists( 'LP_Helper' ) ) {
			eval(
				'class LP_Helper {
					public static function json_decode( $value, $assoc = false ) {
						return json_decode( (string) $value, (bool) $assoc );
					}
				}'
			);
		}
		if ( ! class_exists( 'LP_Order' ) ) {
			eval(
				'class LP_Order {
					public function get_id() { return 0; }
					public function is_completed() { return false; }
					public function get_meta( $key ) { return ""; }
					public function add_note( $note ) {}
					public function get_edit_link() { return ""; }
					public function get_order_number() { return ""; }
				}'
			);
		}
		if ( ! class_exists( 'LP_Abstract_Settings' ) ) {
			eval( 'abstract class LP_Abstract_Settings {}' );
		}
		if ( ! class_exists( 'LP_Gateway_Abstract' ) ) {
			require LP_INC_PATH . 'gateways/class-lp-gateway-abstract.php';
		}
		if ( ! class_exists( 'LP_Subscription_Manager' ) ) {
			require LP_INC_PATH . 'gateways/subscriptions/class-lp-subscription-manager.php';
		}
		if ( ! class_exists( 'LP_Gateway_Paypal' ) ) {
			require LP_INC_PATH . 'gateways/paypal/class-lp-gateway-paypal.php';
		}
	}

	protected function setUp(): void {
		parent::setUp();

		if ( ! method_exists( \LP_Gateway_Paypal::class, 'get_webhook_events_processed' ) ) {
			$this->markTestSkipped( 'A stub LP_Gateway_Paypal was loaded by another test.' );
		}

		$this->processed_meta = '';
		$self                 = $this;

		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_post_meta' )->alias(
			function ( $post_id, $key = '', $single = false ) use ( $self ) {
				if ( self::META_PROCESSED === $key ) {
					return $self->processed_meta;
				}
				if ( \LP_Gateway_Abstract::META_SUBSCRIPTION_ID === $key ) {
					return self::SUBSCRIPTION_ID;
				}

				return $single ? '' : array();
			}
		);
	}

	// ---------------------------------------------------------------------
	// Helpers
	// ---------------------------------------------------------------------

	private function gateway(): \LP_Gateway_Paypal {
		return ( new \ReflectionClass( \LP_Gateway_Paypal::class ) )->newInstanceWithoutConstructor();
	}

	/**
	 * Gateway whose PayPal signature verification passes.
	 *
	 * @return Mockery\MockInterface|\LP_Gateway_Paypal
	 */
	private function verified_gateway() {
		$gateway = Mockery::mock( \LP_Gateway_Paypal::class )->makePartial();
		$gateway->shouldReceive( 'verify_data_from_webhook_subscription' )->andReturnNull();

		return $gateway;
	}

	/**
	 * @return Mockery\MockInterface|\LP_Order
	 */
	private function order( bool $completed, string $subscription_status = '' ) {
		$order = Mockery::mock( \LP_Order::class );
		$order->shouldReceive( 'get_id' )->andReturn( self::ORDER_ID );
		$order->shouldReceive( 'is_completed' )->andReturn( $completed );
		$order->shouldReceive( 'get_meta' )->andReturnUsing(
			function ( $key ) use ( $subscription_status ) {
				return \LP_Gateway_Abstract::META_SUBSCRIPTION_STATUS === $key ? $subscription_status : '';
			}
		);
		$order->shouldReceive( 'get_edit_link' )->andReturn( '' );
		$order->shouldReceive( 'get_order_number' )->andReturn( '#10' );
		$order->shouldReceive( 'get_users' )->andReturn( array() );

		Functions\when( 'learn_press_get_order' )->justReturn( $order );

		return $order;
	}

	private function request( array $event ): \WP_REST_Request {
		$request = new \WP_REST_Request();
		$request->set_body( json_encode( $event ) );

		return $request;
	}

	private function renewal_event(): array {
		return array(
			'id'            => 'WH-RENEW-1',
			'event_type'    => 'PAYMENT.SALE.COMPLETED',
			'resource_type' => 'sale',
			'resource'      => array(
				'custom'               => (string) self::ORDER_ID,
				'billing_agreement_id' => self::SUBSCRIPTION_ID,
				'amount'               => array(
					'total'    => '10.00',
					'currency' => 'USD',
				),
			),
		);
	}

	private function cancelled_event(): array {
		return array(
			'id'            => 'WH-CANCEL-1',
			'event_type'    => 'BILLING.SUBSCRIPTION.CANCELLED',
			'resource_type' => 'subscription',
			'resource'      => array(
				'custom_id' => (string) self::ORDER_ID,
				'id'        => self::SUBSCRIPTION_ID,
			),
		);
	}

	/**
	 * Expect update_post_meta() calls on META_PROCESSED; returns the saved values.
	 *
	 * @return \ArrayObject
	 */
	private function capture_saved_processed_meta(): \ArrayObject {
		$saved = new \ArrayObject();
		Functions\when( 'update_post_meta' )->alias(
			function ( $post_id, $key, $value ) use ( $saved ) {
				if ( self::META_PROCESSED === $key ) {
					$saved[] = array( $post_id, $value );
				}

				return true;
			}
		);

		return $saved;
	}

	// ---------------------------------------------------------------------
	// get_webhook_events_processed / is_webhook_event_processed
	// ---------------------------------------------------------------------

	public function test_get_webhook_events_processed_returns_empty_when_meta_missing(): void {
		$this->processed_meta = '';

		$this->assertSame( array(), $this->gateway()->get_webhook_events_processed( self::ORDER_ID ) );
	}

	public function test_get_webhook_events_processed_returns_empty_on_invalid_json(): void {
		$this->processed_meta = '{not json';

		$this->assertSame( array(), $this->gateway()->get_webhook_events_processed( self::ORDER_ID ) );
	}

	public function test_get_webhook_events_processed_decodes_json_array(): void {
		$this->processed_meta = '["WH-1","WH-2"]';

		$this->assertSame( array( 'WH-1', 'WH-2' ), $this->gateway()->get_webhook_events_processed( self::ORDER_ID ) );
	}

	public function test_is_webhook_event_processed_returns_false_for_empty_event_id(): void {
		$this->processed_meta = '[""]';

		$this->assertFalse( $this->gateway()->is_webhook_event_processed( self::ORDER_ID, '' ) );
	}

	public function test_is_webhook_event_processed_returns_true_when_event_is_stored(): void {
		$this->processed_meta = '["WH-1","WH-2"]';

		$this->assertTrue( $this->gateway()->is_webhook_event_processed( self::ORDER_ID, 'WH-2' ) );
		$this->assertFalse( $this->gateway()->is_webhook_event_processed( self::ORDER_ID, 'WH-3' ) );
	}

	// ---------------------------------------------------------------------
	// capture_subscription_webhook
	// ---------------------------------------------------------------------

	public function test_capture_subscription_webhook_ignores_already_processed_event(): void {
		$this->processed_meta = '["WH-RENEW-1"]';
		$order                = $this->order( true, 'activated' );
		$order->shouldNotReceive( 'add_note' );
		$saved = $this->capture_saved_processed_meta();

		try {
			$this->verified_gateway()->capture_subscription_webhook( $this->request( $this->renewal_event() ) );
			$this->fail( 'Expected exception with code 200.' );
		} catch ( \Exception $e ) {
			$this->assertSame( 200, $e->getCode() );
		}
		$this->assertCount( 0, $saved );
	}

	public function test_capture_subscription_webhook_saves_event_as_json_after_success(): void {
		$this->processed_meta = '["WH-OLD"]';
		$order                = $this->order( false );
		$order->shouldReceive( 'add_note' )->once();
		$saved = $this->capture_saved_processed_meta();

		$this->verified_gateway()->capture_subscription_webhook( $this->request( $this->cancelled_event() ) );

		$this->assertSame( array( array( self::ORDER_ID, '["WH-OLD","WH-CANCEL-1"]' ) ), $saved->getArrayCopy() );
	}

	public function test_capture_subscription_webhook_saves_first_event_when_meta_missing(): void {
		$order = $this->order( false );
		$order->shouldReceive( 'add_note' )->once();
		$saved = $this->capture_saved_processed_meta();

		$this->verified_gateway()->capture_subscription_webhook( $this->request( $this->cancelled_event() ) );

		$this->assertSame( array( array( self::ORDER_ID, '["WH-CANCEL-1"]' ) ), $saved->getArrayCopy() );
	}

	public function test_capture_subscription_webhook_does_not_save_failed_event(): void {
		$order = $this->order( false );
		$order->shouldReceive( 'add_note' )->andThrow( new RuntimeException( 'DB error' ) );
		$saved = $this->capture_saved_processed_meta();

		try {
			$this->verified_gateway()->capture_subscription_webhook( $this->request( $this->cancelled_event() ) );
			$this->fail( 'Expected RuntimeException.' );
		} catch ( RuntimeException $e ) {
			$this->assertSame( 'DB error', $e->getMessage() );
		}
		$this->assertCount( 0, $saved );
	}

	public function test_capture_subscription_webhook_does_not_save_event_without_id(): void {
		$order = $this->order( false );
		$order->shouldReceive( 'add_note' )->once();
		$saved = $this->capture_saved_processed_meta();
		$event = $this->cancelled_event();
		unset( $event['id'] );

		$this->verified_gateway()->capture_subscription_webhook( $this->request( $event ) );

		$this->assertCount( 0, $saved );
	}

	// ---------------------------------------------------------------------
	// Events acknowledged without processing
	// ---------------------------------------------------------------------

	public function test_capture_subscription_data_ignores_subscription_event_on_completed_order(): void {
		$this->order( true, 'activated' );
		$data = $this->cancelled_event() + array( 'lp_order_id' => self::ORDER_ID );

		$this->expectException( \Exception::class );
		$this->expectExceptionCode( 200 );

		$this->gateway()->capture_subscription_data( $data );
	}

	public function test_capture_payment_setup_fee_or_renewal_ignores_other_subscription(): void {
		$order = $this->order( true, 'activated' );
		$data  = $this->renewal_event();

		$data['resource']['billing_agreement_id'] = 'I-OTHER';

		$this->expectException( \Exception::class );
		$this->expectExceptionCode( 200 );

		$this->gateway()->capture_payment_setup_fee_or_renewal( $order, $data );
	}
}

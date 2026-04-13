<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways\Subscriptions;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;
use ReflectionClass;
use ReflectionProperty;

/**
 * Tests for LP_Subscription_Manager::process_webhook_event().
 *
 * Strategy: inject a mocked LP_Subscription_Event_Store via reflection,
 * mock LP_Order and LP_Gateway_Abstract, and stub all WP functions.
 */
class SubscriptionManagerTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Minimal stub for LP_Abstract_Settings (parent chain required before LP_Gateway_Abstract).
		if ( ! class_exists( 'LP_Abstract_Settings' ) ) {
			eval( 'abstract class LP_Abstract_Settings {}' );
		}

		// LP order status constants used by LP_Subscription_Manager.
		defined( 'LP_ORDER_CPT' )       || define( 'LP_ORDER_CPT', 'lp_order' );
		defined( 'LP_ORDER_COMPLETED' ) || define( 'LP_ORDER_COMPLETED', 'completed' );
		defined( 'LP_ORDER_FAILED' )    || define( 'LP_ORDER_FAILED', 'failed' );
		defined( 'LP_ORDER_PENDING' )   || define( 'LP_ORDER_PENDING', 'pending' );

		if ( ! class_exists( 'LP_Gateway_Abstract' ) ) {
			require LP_INC_PATH . 'gateways/class-lp-gateway-abstract.php';
		}
		if ( ! class_exists( 'LP_Subscription_Event_Store' ) ) {
			require LP_INC_PATH . 'gateways/subscriptions/class-lp-subscription-event-store.php';
		}
		if ( ! class_exists( 'LP_Subscription_Manager' ) ) {
			require LP_INC_PATH . 'gateways/subscriptions/class-lp-subscription-manager.php';
		}
	}

	protected function setUp(): void {
		parent::setUp();

		// Reset LP_Subscription_Manager singleton.
		$ref = new ReflectionProperty( \LP_Subscription_Manager::class, '_instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );

		// Common WP function stubs.
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		// apply_filters($tag, $value, ...) — return $value (arg index 1).
		Functions\when( 'apply_filters' )->returnArg( 1 );
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'absint' )->alias( 'intval' );
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Build a manager with a mock event store injected.
	 *
	 * @return array{ manager: \LP_Subscription_Manager, store: \Mockery\MockInterface }
	 */
	private function make_manager_with_mock_store(): array {
		$store_mock = Mockery::mock( \LP_Subscription_Event_Store::class );

		$manager = new \LP_Subscription_Manager();

		$ref = new ReflectionProperty( \LP_Subscription_Manager::class, 'event_store' );
		$ref->setAccessible( true );
		$ref->setValue( $manager, $store_mock );

		return array(
			'manager' => $manager,
			'store'   => $store_mock,
		);
	}

	/**
	 * Build a mock gateway with a given ID.
	 */
	private function make_gateway( string $id = 'paypal' ): \Mockery\MockInterface {
		$gateway = Mockery::mock( \LP_Gateway_Abstract::class );
		$gateway->shouldReceive( 'get_id' )->andReturn( $id );

		return $gateway;
	}

	/**
	 * Build a mock LP_Order.
	 */
	private function make_order( int $id = 100 ): \Mockery\MockInterface {
		if ( ! class_exists( 'LP_Order' ) ) {
			eval( 'class LP_Order {}' );
		}
		$order = Mockery::mock( 'LP_Order' );
		$order->shouldReceive( 'get_id' )->andReturn( $id );
		$order->shouldReceive( 'is_completed' )->andReturn( false )->byDefault();
		$order->shouldReceive( 'payment_complete' )->andReturn( null )->byDefault();

		return $order;
	}

	// -------------------------------------------------------------------------
	// Duplicate detection
	// -------------------------------------------------------------------------

	public function test_returns_duplicate_response_when_already_processed(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )
			->with( 'paypal', 'WH-001' )
			->andReturn( true );

		$event = array(
			'event_id'   => 'WH-001',
			'event_type' => 'subscription_activated',
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'duplicate', $result['status'] );
		$this->assertSame( 200, $result['status_code'] );
		$this->assertSame( 'WH-001', $result['event_id'] );
	}

	// -------------------------------------------------------------------------
	// Locking
	// -------------------------------------------------------------------------

	public function test_returns_processing_response_when_lock_not_acquired(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )->andReturn( false );
		$store->shouldReceive( 'acquire_lock' )->with( 'paypal', 'WH-002' )->andReturn( false );

		$event = array(
			'event_id'   => 'WH-002',
			'event_type' => 'subscription_activated',
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'processing', $result['status'] );
		$this->assertSame( 202, $result['status_code'] );
	}

	// -------------------------------------------------------------------------
	// subscription_activated
	// -------------------------------------------------------------------------

	public function test_subscription_activated_marks_parent_payment_completed(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )->andReturn( false );
		$store->shouldReceive( 'acquire_lock' )->andReturn( true );
		$store->shouldReceive( 'mark_processed' )->once();
		$store->shouldReceive( 'release_lock' )->once();

		$order = $this->make_order( 42 );
		$order->shouldReceive( 'payment_complete' )->with( '' )->once();
		$order->shouldReceive( 'add_note' )->andReturn( null )->byDefault();

		Functions\when( 'learn_press_get_order' )->justReturn( $order );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_posts' )->justReturn( array() );

		$event = array(
			'event_id'        => 'WH-ACT-001',
			'event_type'      => 'subscription_activated',
			'parent_order_id' => 42,
			'subscription_id' => 'I-SUB123',
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'success', $result['status'] );
		$this->assertSame( 42, $result['order_id'] );
	}

	public function test_subscription_activated_throws_when_no_parent_order(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )->andReturn( false );
		$store->shouldReceive( 'acquire_lock' )->andReturn( true );
		// Exception path: mark_processed is inside the try block and is skipped.
		$store->shouldReceive( 'mark_processed' )->never();
		$store->shouldReceive( 'release_lock' )->once();

		Functions\when( 'learn_press_get_order' )->justReturn( false );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_posts' )->justReturn( array() );

		$event = array(
			'event_id'   => 'WH-ACT-002',
			'event_type' => 'subscription_activated',
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'error', $result['status'] );
		$this->assertSame( 400, $result['status_code'] );
	}

	// -------------------------------------------------------------------------
	// subscription_cancelled / subscription_expired
	// -------------------------------------------------------------------------

	public function test_subscription_cancelled_updates_status(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )->andReturn( false );
		$store->shouldReceive( 'acquire_lock' )->andReturn( true );
		$store->shouldReceive( 'mark_processed' )->once();
		$store->shouldReceive( 'release_lock' )->once();

		$order = $this->make_order( 55 );
		Functions\when( 'learn_press_get_order' )->justReturn( $order );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_posts' )->justReturn( array() );

		$event = array(
			'event_id'        => 'WH-CANCEL-001',
			'event_type'      => 'subscription_cancelled',
			'parent_order_id' => 55,
			'subscription_id' => 'I-SUB456',
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'cancelled', $result['status'] );
		$this->assertSame( 55, $result['order_id'] );
	}

	public function test_subscription_expired_updates_status(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )->andReturn( false );
		$store->shouldReceive( 'acquire_lock' )->andReturn( true );
		$store->shouldReceive( 'mark_processed' )->once();
		$store->shouldReceive( 'release_lock' )->once();

		$order = $this->make_order( 77 );
		Functions\when( 'learn_press_get_order' )->justReturn( $order );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_posts' )->justReturn( array() );

		$event = array(
			'event_id'        => 'WH-EXP-001',
			'event_type'      => 'subscription_expired',
			'parent_order_id' => 77,
			'subscription_id' => 'I-SUB789',
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'expired', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Unknown event type
	// -------------------------------------------------------------------------

	public function test_unknown_event_type_returns_ignored(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )->andReturn( false );
		$store->shouldReceive( 'acquire_lock' )->andReturn( true );
		$store->shouldReceive( 'mark_processed' )->once();
		$store->shouldReceive( 'release_lock' )->once();

		Functions\when( 'learn_press_get_order' )->justReturn( false );
		Functions\when( 'update_post_meta' )->justReturn( true );
		Functions\when( 'get_posts' )->justReturn( array() );

		$event = array(
			'event_id'   => 'WH-UNK-001',
			'event_type' => 'completely_unknown_event',
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'ignored', $result['status'] );
	}

	// -------------------------------------------------------------------------
	// Event ID fallback
	// -------------------------------------------------------------------------

	public function test_empty_event_id_falls_back_to_md5_of_payload(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$captured_event_id = null;
		$store->shouldReceive( 'is_processed' )
			->andReturnUsing( function ( $gw_id, $event_id ) use ( &$captured_event_id ) {
				$captured_event_id = $event_id;
				return true; // Return duplicate so we don't need to set up full processing
			} );

		$event = array(
			'event_id'   => '', // empty — should generate MD5
			'event_type' => 'subscription_activated',
		);

		$manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertNotEmpty( $captured_event_id );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{32}$/', $captured_event_id );
	}

	// -------------------------------------------------------------------------
	// resolve_parent_order_id
	// -------------------------------------------------------------------------

	public function test_resolve_parent_order_id_uses_event_parent_order_id_first(): void {
		[ 'manager' => $manager, 'store' => $store ] = $this->make_manager_with_mock_store();

		$store->shouldReceive( 'is_processed' )->andReturn( false );
		$store->shouldReceive( 'acquire_lock' )->andReturn( true );
		$store->shouldReceive( 'mark_processed' )->once();
		$store->shouldReceive( 'release_lock' )->once();

		$order = $this->make_order( 99 );
		$order->shouldReceive( 'payment_complete' )->once();

		// learn_press_get_order should be called with 99 (from parent_order_id).
		Functions\expect( 'learn_press_get_order' )
			->with( 99 )
			->andReturn( $order );
		Functions\when( 'update_post_meta' )->justReturn( true );

		$event = array(
			'event_id'        => 'WH-RES-001',
			'event_type'      => 'subscription_activated',
			'parent_order_id' => 99,
		);

		$result = $manager->process_webhook_event( $this->make_gateway(), $event );

		$this->assertSame( 'success', $result['status'] );
	}
}
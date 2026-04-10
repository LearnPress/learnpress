<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Gateways\Subscriptions;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use ReflectionClass;
use ReflectionProperty;

/**
 * Tests for LP_Subscription_Event_Store.
 *
 * Tests the idempotency storage layer:
 * - is_processed / mark_processed (wp_options)
 * - acquire_lock / release_lock (transients)
 * - key derivation is deterministic
 */
class SubscriptionEventStoreTest extends BrainMonkeyTestCase {

	// -------------------------------------------------------------------------
	// Bootstrap
	// -------------------------------------------------------------------------

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		if ( ! class_exists( 'LP_Subscription_Event_Store' ) ) {
			require LP_INC_PATH . 'gateways/subscriptions/class-lp-subscription-event-store.php';
		}
	}

	protected function setUp(): void {
		parent::setUp();

		// Reset singleton so each test gets a fresh instance.
		$ref = new ReflectionProperty( \LP_Subscription_Event_Store::class, '_instance' );
		$ref->setAccessible( true );
		$ref->setValue( null, null );
	}

	private function make_store(): \LP_Subscription_Event_Store {
		return new \LP_Subscription_Event_Store();
	}

	// -------------------------------------------------------------------------
	// is_processed
	// -------------------------------------------------------------------------

	public function test_is_processed_returns_false_when_option_not_set(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\expect( 'get_option' )->once()->andReturn( false );

		$store = $this->make_store();
		$this->assertFalse( $store->is_processed( 'paypal', 'WH-001' ) );
	}

	public function test_is_processed_returns_true_when_option_exists(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\expect( 'get_option' )->once()->andReturn( '{"time":1700000000}' );

		$store = $this->make_store();
		$this->assertTrue( $store->is_processed( 'paypal', 'WH-001' ) );
	}

	// -------------------------------------------------------------------------
	// mark_processed
	// -------------------------------------------------------------------------

	public function test_mark_processed_calls_add_option_and_returns_true(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );
		Functions\expect( 'add_option' )
			->once()
			->with(
				\Mockery::pattern( '/^lp_subscription_event_[a-f0-9]{32}$/' ),
				\Mockery::any(),
				'',
				'no'
			)
			->andReturn( true );

		$store = $this->make_store();
		$result = $store->mark_processed( 'paypal', 'WH-001', array( 'status' => 'success' ) );

		$this->assertTrue( $result );
	}

	public function test_mark_processed_encodes_payload_as_json(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$captured_value = null;
		Functions\expect( 'add_option' )
			->once()
			->andReturnUsing( function ( $key, $value ) use ( &$captured_value ) {
				$captured_value = $value;
				return true;
			} );

		$store = $this->make_store();
		$store->mark_processed( 'paypal', 'WH-XYZ', array( 'status' => 'duplicate' ) );

		$decoded = json_decode( $captured_value, true );
		$this->assertSame( 'paypal', $decoded['gateway'] );
		$this->assertSame( 'WH-XYZ', $decoded['event'] );
		$this->assertSame( 'duplicate', $decoded['payload']['status'] );
	}

	// -------------------------------------------------------------------------
	// acquire_lock
	// -------------------------------------------------------------------------

	public function test_acquire_lock_returns_false_when_transient_already_exists(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\expect( 'get_transient' )->once()->andReturn( 1 );

		$store = $this->make_store();
		$this->assertFalse( $store->acquire_lock( 'paypal', 'WH-001' ) );
	}

	public function test_acquire_lock_sets_transient_and_returns_true_when_free(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\expect( 'get_transient' )->once()->andReturn( false );
		Functions\expect( 'set_transient' )
			->once()
			->with( \Mockery::any(), 1, 300 )
			->andReturn( true );

		$store = $this->make_store();
		$this->assertTrue( $store->acquire_lock( 'paypal', 'WH-001' ) );
	}

	// -------------------------------------------------------------------------
	// release_lock
	// -------------------------------------------------------------------------

	public function test_release_lock_deletes_transient(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		Functions\expect( 'delete_transient' )
			->once()
			->with( \Mockery::pattern( '/^lp_subscription_event_lock_[a-f0-9]{32}$/' ) );

		$store = $this->make_store();
		$store->release_lock( 'paypal', 'WH-001' );

		$this->assertTrue( true ); // Mockery assertion above is the real check
	}

	// -------------------------------------------------------------------------
	// Key determinism
	// -------------------------------------------------------------------------

	public function test_same_inputs_produce_same_option_key(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		// Capture two key values from get_option calls.
		$keys = array();
		Functions\expect( 'get_option' )
			->twice()
			->andReturnUsing( function ( $key ) use ( &$keys ) {
				$keys[] = $key;
				return false;
			} );

		$store = $this->make_store();
		$store->is_processed( 'paypal', 'WH-SAME' );
		$store->is_processed( 'paypal', 'WH-SAME' );

		$this->assertCount( 2, $keys );
		$this->assertSame( $keys[0], $keys[1] );
	}

	public function test_different_event_ids_produce_different_keys(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		$keys = array();
		Functions\expect( 'get_option' )
			->twice()
			->andReturnUsing( function ( $key ) use ( &$keys ) {
				$keys[] = $key;
				return false;
			} );

		$store = $this->make_store();
		$store->is_processed( 'paypal', 'WH-AAA' );
		$store->is_processed( 'paypal', 'WH-BBB' );

		$this->assertNotSame( $keys[0], $keys[1] );
	}

	public function test_different_gateways_produce_different_keys(): void {
		Functions\when( 'sanitize_key' )->returnArg();
		$keys = array();
		Functions\expect( 'get_option' )
			->twice()
			->andReturnUsing( function ( $key ) use ( &$keys ) {
				$keys[] = $key;
				return false;
			} );

		$store = $this->make_store();
		$store->is_processed( 'paypal', 'WH-001' );
		$store->is_processed( 'stripe', 'WH-001' );

		$this->assertNotSame( $keys[0], $keys[1] );
	}
}
<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Subscriptions;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use Mockery;

class LPSubscriptionEventStoreTest extends BrainMonkeyTestCase {

	private function load_store_class(): void {
		if ( ! class_exists( '\\LP_Subscription_Event_Store', false ) ) {
			require_once dirname( __DIR__, 3 ) . '/inc/gateways/subscriptions/class-lp-subscription-event-store.php';
		}
	}

	private function sanitize_gateway_key( string $gateway_id ): string {
		return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( $gateway_id ) );
	}

	private function build_option_key( string $gateway_id, string $event_id ): string {
		return 'lp_subscription_event_' . md5( $this->sanitize_gateway_key( $gateway_id ) . '|' . $event_id );
	}

	private function build_lock_key( string $gateway_id, string $event_id ): string {
		return 'lp_subscription_event_lock_' . md5( $this->sanitize_gateway_key( $gateway_id ) . '|' . $event_id );
	}

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'sanitize_key' )->alias(
			function ( string $key ): string {
				return preg_replace( '/[^a-z0-9_\\-]/', '', strtolower( $key ) );
			}
		);
		Functions\when( 'wp_json_encode' )->alias(
			function ( $value ): string {
				return (string) json_encode( $value );
			}
		);

		$this->load_store_class();
	}

	public function test_instance_returns_singleton(): void {
		$store_1 = \LP_Subscription_Event_Store::instance();
		$store_2 = \LP_Subscription_Event_Store::instance();

		$this->assertSame( $store_1, $store_2 );
	}

	public function test_is_processed_returns_true_when_event_marker_exists(): void {
		$gateway_id = 'PayPal Gateway';
		$event_id   = 'evt_123';

		Functions\expect( 'get_option' )
			->once()
			->with( $this->build_option_key( $gateway_id, $event_id ), false )
			->andReturn( '1' );

		$store = new \LP_Subscription_Event_Store();

		$this->assertTrue( $store->is_processed( $gateway_id, $event_id ) );
	}

	public function test_mark_processed_stores_non_autoloaded_marker_with_payload(): void {
		$gateway_id = 'Stripe Main';
		$event_id   = 'evt_mark_1';
		$payload    = array(
			'status'     => 'success',
			'order_id'   => 100,
			'event_type' => 'renewal_payment_succeeded',
		);

		Functions\expect( 'add_option' )
			->once()
			->with(
				$this->build_option_key( $gateway_id, $event_id ),
				Mockery::on(
					function ( string $encoded ) use ( $gateway_id, $event_id, $payload ): bool {
						$decoded = json_decode( $encoded, true );
						if ( ! is_array( $decoded ) ) {
							return false;
						}

						return isset( $decoded['time'] ) &&
							$decoded['gateway'] === $gateway_id &&
							$decoded['event'] === $event_id &&
							$decoded['payload'] === $payload;
					}
				),
				'',
				'no'
			)
			->andReturn( true );

		$store = new \LP_Subscription_Event_Store();

		$this->assertTrue( $store->mark_processed( $gateway_id, $event_id, $payload ) );
	}

	public function test_acquire_lock_returns_false_when_event_is_locked(): void {
		$gateway_id = 'Stripe Main';
		$event_id   = 'evt_lock_1';
		$lock_key   = $this->build_lock_key( $gateway_id, $event_id );

		Functions\expect( 'get_transient' )
			->once()
			->with( $lock_key )
			->andReturn( 1 );

		Functions\expect( 'set_transient' )->never();

		$store = new \LP_Subscription_Event_Store();

		$this->assertFalse( $store->acquire_lock( $gateway_id, $event_id ) );
	}

	public function test_acquire_lock_sets_transient_with_default_ttl_when_unlocked(): void {
		$gateway_id = 'PayPal Main';
		$event_id   = 'evt_lock_2';
		$lock_key   = $this->build_lock_key( $gateway_id, $event_id );

		Functions\expect( 'get_transient' )
			->once()
			->with( $lock_key )
			->andReturn( false );

		Functions\expect( 'set_transient' )
			->once()
			->with( $lock_key, 1, 300 )
			->andReturn( true );

		$store = new \LP_Subscription_Event_Store();

		$this->assertTrue( $store->acquire_lock( $gateway_id, $event_id ) );
	}

	public function test_release_lock_deletes_transient_key(): void {
		$gateway_id = 'PayPal Main';
		$event_id   = 'evt_lock_release';
		$lock_key   = $this->build_lock_key( $gateway_id, $event_id );

		Functions\expect( 'delete_transient' )
			->once()
			->with( $lock_key )
			->andReturn( true );

		$store = new \LP_Subscription_Event_Store();
		$store->release_lock( $gateway_id, $event_id );

		$this->assertTrue( true );
	}
}


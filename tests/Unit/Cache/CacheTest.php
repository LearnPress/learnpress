<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Cache;

use Brain\Monkey\Functions;
use LearnPress\Cache\LPCache;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

class CacheTest extends BrainMonkeyTestCase {
	public function test_class_is_psr4_autoloadable(): void {
		$this->assertTrue( class_exists( LPCache::class ) );
	}

	public function test_set_cache_writes_to_wordpress_cache(): void {
		Functions\expect( 'wp_cache_set' )
			->once()
			->with( 'key', array( 'value' ), 'learn_press', 60 )
			->andReturn( true );

		$cache = new LPCache();

		$this->assertTrue( $cache->set_cache( 'key', array( 'value' ), 60 ) );
	}

	public function test_get_cache_returns_wordpress_cache_hit(): void {
		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'key', 'learn_press' )
			->andReturn( 'value' );

		$cache = new LPCache();

		$this->assertSame( 'value', $cache->get_cache( 'key' ) );
	}

	public function test_get_cache_returns_false_on_wordpress_cache_miss_without_persistence(): void {
		Functions\expect( 'wp_cache_get' )
			->once()
			->with( 'missing', 'learn_press' )
			->andReturn( false );

		$cache = new LPCache();

		$this->assertFalse( $cache->get_cache( 'missing' ) );
	}

	public function test_clear_deletes_from_wordpress_cache(): void {
		Functions\expect( 'wp_cache_delete' )
			->once()
			->with( 'key', 'learn_press' )
			->andReturn( true );

		$cache = new LPCache();

		$this->assertTrue( $cache->clear( 'key' ) );
	}

	public function test_remember_not_persistent_computes_once_and_uses_cache(): void {
		Functions\expect( 'wp_cache_add_non_persistent_groups' )
			->once()
			->with( array( 'lp_cache_not_persistent' ) );

		Functions\expect( 'wp_cache_get' )
			->twice()
			->with( 'cart/total', 'lp_cache_not_persistent' )
			->andReturnValues( array( false, 'value' ) );

		Functions\expect( 'wp_cache_set' )
			->once()
			->with( 'cart/total', 'value', 'lp_cache_not_persistent' )
			->andReturn( true );

		$cache   = new LPCache();
		$counter = 0;

		$callback = function () use ( &$counter ) {
			++$counter;
			return 'value';
		};

		$first  = $cache->remember_not_persistent( 'cart/total', $callback );
		$second = $cache->remember_not_persistent( 'cart/total', $callback );

		$this->assertSame( 'value', $first );
		$this->assertSame( 'value', $second );
		$this->assertSame( 1, $counter );
	}
}

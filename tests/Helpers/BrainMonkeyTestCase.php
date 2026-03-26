<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Helpers;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Base class for all LearnPress unit tests.
 *
 * - Boots/tears down Brain Monkey per test so WP function stubs are isolated.
 * - Integrates Mockery assertions into PHPUnit lifecycle via the trait.
 *
 * Usage:
 *   class MyTest extends BrainMonkeyTestCase { ... }
 */
abstract class BrainMonkeyTestCase extends TestCase {

	use MockeryPHPUnitIntegration;

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Stub the most common WP functions so tests don't need to re-stub them.
		// Override per-test with Monkey\Functions\when() as needed.
		Monkey\Functions\stubTranslationFunctions(); // __(), _e(), esc_html__(), …
		Monkey\Functions\stubEscapeFunctions();      // esc_attr(), esc_html(), esc_url(), …
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}
}
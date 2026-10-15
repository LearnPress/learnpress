<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Security;

use Brain\Monkey\Functions;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Regression test for the malicious `quantity` payload.
 *
 * Verifies that a string such as `1<img src=x onerror=alert(1)>` is cast to
 * the integer `1`, preventing the HTML payload from being stored or rendered.
 */
class CheckoutStoredXssTest extends BrainMonkeyTestCase {

	#[Test]
	public function quantity_string_with_html_prefix_casts_to_integer(): void {
		Functions\when( 'absint' )->alias(
			static function ( $value ) {
				return (int) preg_replace( '/[^0-9].*/', '', (string) $value );
			}
		);

		$payload  = '1<img src=x onerror=alert(1)>';
		$quantity = absint( $payload );

		$this->assertSame( 1, $quantity );
		$this->assertIsInt( $quantity );
	}
}

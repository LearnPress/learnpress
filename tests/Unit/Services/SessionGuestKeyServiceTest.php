<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Services;

use Brain\Monkey\Functions;
use LearnPress\Services\SessionGuestKeyService;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Regression tests for the forged `lp_session_guest` cookie (fix-sec-b).
 */
class SessionGuestKeyServiceTest extends BrainMonkeyTestCase {

	/** @var SessionGuestKeyService */
	private $service;

	protected function setUp(): void {
		parent::setUp();
		Functions\when( 'wp_salt' )->justReturn( 'test-salt' );
		Functions\when( 'wp_generate_password' )->alias(
			static function ( $length ) {
				return substr( str_repeat( 'aB3', $length ), 0, $length );
			}
		);
		$this->service = SessionGuestKeyService::instance();
	}

	private function hmac( string $value, string $salt = 'test-salt' ): string {
		return hash_hmac( 'sha256', $value, $salt );
	}

	public function test_generate_guest_key_has_guest_prefix(): void {
		$key = $this->service->generate_guest_key();

		$this->assertStringStartsWith( 'g-', $key );
		// Column session_key is char(32).
		$this->assertSame( 32, strlen( $key ) );
	}

	public function test_verify_round_trip_returns_key(): void {
		$key = $this->service->generate_guest_key();

		$this->assertSame( $key, $this->service->verify( $this->service->sign( $key ) ) );
	}

	public function test_verify_filtered_key_with_other_format_round_trips(): void {
		$key = 'guest_abc.12';

		$this->assertSame( $key, $this->service->verify( $this->service->sign( $key ) ) );
	}

	public function test_verify_rejects_unsigned_guest_key(): void {
		$this->assertSame( '', $this->service->verify( 'g-abc' ) );
	}

	public function test_verify_rejects_bare_integer(): void {
		$this->assertSame( '', $this->service->verify( '2' ) );
	}

	public function test_verify_rejects_signed_numeric_key(): void {
		$this->assertSame( '', $this->service->verify( '2|' . $this->hmac( '2' ) ) );
	}

	public function test_verify_rejects_tampered_key(): void {
		$signed   = $this->service->sign( 'g-abc' );
		$tampered = 'g-abd' . substr( $signed, strlen( 'g-abc' ) );

		$this->assertSame( '', $this->service->verify( $tampered ) );
	}

	public function test_verify_rejects_tampered_signature(): void {
		$signed   = $this->service->sign( 'g-abc' );
		$last     = substr( $signed, -1 );
		$tampered = substr( $signed, 0, -1 ) . ( 'a' === $last ? 'b' : 'a' );

		$this->assertSame( '', $this->service->verify( $tampered ) );
	}

	public function test_verify_rejects_signature_from_other_secret(): void {
		$this->assertSame( '', $this->service->verify( 'g-abc|' . $this->hmac( 'g-abc', 'other-salt' ) ) );
	}

	public function test_verify_rejects_non_hex_signature(): void {
		$this->assertSame( '', $this->service->verify( 'g-abc|' . str_repeat( 'z', 64 ) ) );
	}

	public function test_verify_rejects_empty_and_non_string(): void {
		$this->assertSame( '', $this->service->verify( '' ) );
		$this->assertSame( '', $this->service->verify( null ) );
		$this->assertSame( '', $this->service->verify( [ 'g-abc' ] ) );
		$this->assertSame( '', $this->service->verify( 2 ) );
	}

	public function test_verify_rejects_key_containing_separator(): void {
		$key = 'g-a|b';

		$this->assertSame( '', $this->service->verify( $key . '|' . $this->hmac( $key ) ) );
	}


	public function test_ip_key_does_not_contain_raw_ip(): void {
		$ip  = '192.168.1.10';
		$key = $this->service->ip_key( $ip );

		$this->assertStringStartsWith( 'gip-', $key );
		$this->assertSame( 32, strlen( $key ) );
		$this->assertStringNotContainsString( $ip, $key );
		$this->assertSame( $key, $this->service->ip_key( $ip ) );
		$this->assertNotSame( $key, $this->service->ip_key( '192.168.1.11' ) );
	}
}

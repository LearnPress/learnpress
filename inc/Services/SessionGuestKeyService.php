<?php

namespace LearnPress\Services;

use LearnPress\Helpers\Singleton;

/**
 * Class SessionGuestKeyService
 *
 * Generate, sign and verify the session key of user guest.
 * Cookie value format: "<key>|<hmac-sha256>", only keys signed by server are accepted.
 *
 * @since 4.4.12
 * @version 1.0.0
 */
class SessionGuestKeyService {
	use Singleton;

	/**
	 * Max length of key, column session_key of table learnpress_sessions is char(32).
	 */
	const KEY_MAX_LENGTH = 32;

	public function init(): void {}

	/**
	 * Generate new random key for user guest.
	 *
	 * @return string
	 */
	public function generate_guest_key(): string {
		return sprintf(
			'g-%s',
			wp_generate_password( self::KEY_MAX_LENGTH - 2, false )
		);
	}

	/**
	 * Sign key to set on cookie.
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function sign( string $key ): string {
		return $key . '|' . $this->hmac( $key );
	}

	/**
	 * Verify cookie value, return key if valid, empty string if invalid.
	 *
	 * @param mixed $cookie_value
	 *
	 * @return string
	 */
	public function verify( $cookie_value ): string {
		if ( ! is_string( $cookie_value )
			|| ! preg_match(
				'/^([A-Za-z0-9._-]{1,' . self::KEY_MAX_LENGTH . '})\|([a-f0-9]{64})$/D',
				$cookie_value,
				$matches
			)
			|| ctype_digit( $matches[1] )
			|| ! hash_equals( $this->hmac( $matches[1] ), $matches[2] ) ) {
			return '';
		}

		return $matches[1];
	}

	/**
	 * Get session key of guest from client IP, do not store raw IP.
	 *
	 * @param string $ip
	 *
	 * @return string
	 */
	public function ip_key( string $ip ): string {
		return sprintf(
			'gip-%s',
			substr( $this->hmac( 'gip|' . $ip ), 0, self::KEY_MAX_LENGTH - 4 )
		);
	}

	/**
	 * HMAC value with site secret.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function hmac( string $value ): string {
		return hash_hmac( 'sha256', $value, wp_salt() );
	}
}

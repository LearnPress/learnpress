<?php

namespace LearnPress\MCP\Auth;

use LP_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Request-scoped authentication context for MCP API key auth.
 */
class AuthContext {
	/**
	 * @var bool
	 */
	protected static $is_api_key_auth = false;

	/**
	 * @var int
	 */
	protected static $key_id = 0;

	/**
	 * @var int
	 */
	protected static $user_id = 0;

	/**
	 * @var string
	 */
	protected static $permissions = '';

	/**
	 * @var bool
	 */
	protected static $usage_touched = false;

	/**
	 * Reset state for current request.
	 *
	 * @return void
	 */
	public static function reset(): void {

		self::$is_api_key_auth = false;
		self::$key_id          = 0;
		self::$user_id         = 0;
		self::$permissions     = '';
		self::$usage_touched   = false;
	}

	/**
	 * Store successful API key auth context.
	 *
	 * @param int    $key_id      API key ID.
	 * @param int    $user_id     Authenticated user ID.
	 * @param string $permissions Granted permissions scope.
	 *
	 * @return void
	 */
	public static function set_api_key_auth( int $key_id, int $user_id, string $permissions ): void {

		self::$is_api_key_auth = true;
		self::$key_id          = absint( $key_id );
		self::$user_id         = absint( $user_id );
		self::$permissions     = LP_Helper::sanitize_params_submitted( $permissions, 'key', false );
	}

	/**
	 * Whether request is authenticated by MCP API key.
	 *
	 * @return bool
	 */
	public static function is_api_key_auth(): bool {

		return self::$is_api_key_auth;
	}

	/**
	 * Get API key ID from context.
	 *
	 * @return int
	 */
	public static function get_key_id(): int {

		return self::$key_id;
	}

	/**
	 * Get user ID from context.
	 *
	 * @return int
	 */
	public static function get_user_id(): int {

		return self::$user_id;
	}

	/**
	 * Get granted key permissions.
	 *
	 * @return string
	 */
	public static function get_permissions(): string {

		return self::$permissions;
	}

	/**
	 * Mark usage metrics as already touched.
	 *
	 * @return void
	 */
	public static function mark_usage_touched(): void {

		self::$usage_touched = true;
	}

	/**
	 * Whether usage metrics were already touched in this request.
	 *
	 * @return bool
	 */
	public static function is_usage_touched(): bool {

		return self::$usage_touched;
	}
}

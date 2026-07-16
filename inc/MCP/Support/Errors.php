<?php

namespace LearnPress\MCP\Support;

use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Single MCP error factory.
 *
 * The only place LearnPress MCP builds WP_Error responses. Transport auth
 * (ApiKeyAuthenticator), the ability permission gate (Abilities), the Phase 1
 * Concerns executors, and the Phase 2 domain executors all call these builders
 * so error codes, statuses, and messages have one source of truth
 * (400/401/403/404/500).
 */
class Errors {

	/**
	 * Build a WP_Error with an HTTP-style status code.
	 *
	 * @param string $code    Error code.
	 * @param string $message Human-readable message.
	 * @param int    $status  HTTP status.
	 *
	 * @return WP_Error
	 */
	public static function make( string $code, string $message, int $status ): WP_Error {
		return new WP_Error( $code, $message, array( 'status' => $status ) );
	}

	/**
	 * 400 invalid input / invalid state transition.
	 *
	 * @param string $message Error message.
	 *
	 * @return WP_Error
	 */
	public static function invalid( string $message ): WP_Error {
		return self::make( 'lp_mcp_invalid_input', $message, 400 );
	}

	/**
	 * 404 missing target entity.
	 *
	 * @param string $message Error message.
	 *
	 * @return WP_Error
	 */
	public static function not_found( string $message ): WP_Error {
		return self::make( 'lp_mcp_not_found', $message, 404 );
	}

	/**
	 * 403 insufficient capability for a specific entity/action.
	 *
	 * @param string $message Optional message.
	 *
	 * @return WP_Error
	 */
	public static function forbidden( string $message = '' ): WP_Error {
		if ( '' === $message ) {
			$message = __( 'You do not have permission to perform this action.', 'learnpress' );
		}

		return self::make( 'lp_mcp_forbidden', $message, 403 );
	}

	/**
	 * 500 generic LearnPress MCP failure.
	 *
	 * @return WP_Error
	 */
	public static function internal(): WP_Error {
		return self::make(
			'lp_mcp_internal_error',
			__( 'An internal LearnPress MCP error occurred.', 'learnpress' ),
			500
		);
	}

	/**
	 * Convert a caught throwable into a generic MCP 500 error.
	 *
	 * Logs the original message for debugging but never leaks it to clients.
	 *
	 * @param Throwable $e Caught throwable.
	 *
	 * @return WP_Error
	 */
	public static function from_throwable( Throwable $e ): WP_Error {
		error_log( 'LearnPress MCP: ' . $e->getMessage() );

		return self::internal();
	}

	/**
	 * 401 missing/invalid MCP authentication (ability permission gate).
	 *
	 * @param string $message Optional message.
	 *
	 * @return WP_Error
	 */
	public static function missing_auth( string $message = '' ): WP_Error {
		if ( '' === $message ) {
			$message = __( 'Missing or invalid MCP authentication.', 'learnpress' );
		}

		return self::make( 'learnpress_mcp_missing_auth', $message, 401 );
	}

	/**
	 * 403 current user lacks the required base capability.
	 *
	 * @param string $capability Required capability name.
	 *
	 * @return WP_Error
	 */
	public static function missing_capability( string $capability ): WP_Error {
		return self::make(
			'learnpress_mcp_missing_base_capability',
			sprintf(
				/* translators: %s: capability. */
				__( 'Current user does not have required base capability: %s.', 'learnpress' ),
				$capability
			),
			403
		);
	}

	/**
	 * 403 API key scope is insufficient for the ability.
	 *
	 * @param string $required_scope Required scope for the ability.
	 * @param string $granted_scope  Scope granted by the authenticated API key.
	 *
	 * @return WP_Error
	 */
	public static function insufficient_scope( string $required_scope, string $granted_scope ): WP_Error {
		return self::make(
			'learnpress_mcp_insufficient_scope',
			sprintf(
				/* translators: 1: required scope, 2: granted scope. */
				__( 'API key scope is insufficient. Required: %1$s. Granted: %2$s.', 'learnpress' ),
				$required_scope,
				$granted_scope
			),
			403
		);
	}

	/**
	 * 401 MCP API key authentication is required (transport layer).
	 *
	 * @return WP_Error
	 */
	public static function api_key_required(): WP_Error {
		return self::make(
			'learnpress_mcp_api_key_required',
			__( 'MCP API key authentication is required.', 'learnpress' ),
			401
		);
	}

	/**
	 * 401 invalid MCP API key credentials (transport layer).
	 *
	 * @return WP_Error
	 */
	public static function invalid_api_credentials(): WP_Error {
		return self::make(
			'learnpress_mcp_invalid_api_key_credentials',
			__( 'Invalid MCP API credentials.', 'learnpress' ),
			401
		);
	}
}

<?php

namespace LearnPress\MCP\Auth;

use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Authenticates MCP HTTP transport requests using LearnPress API keys.
 */
class ApiKeyAuthenticator {
	/**
	 * MCP REST route path.
	 */
	public const MCP_ROUTE = '/mcp/mcp-adapter-default-server';

	/**
	 * @var self|null
	 */
	protected static $instance;

	/**
	 * @var ApiKeysRepository
	 */
	protected $keys_repository;

	/**
	 * @var WP_Error|null
	 */
	protected $auth_error;

	/**
	 * @var bool
	 */
	protected $api_key_present = false;

	/**
	 * @var bool
	 */
	protected $legacy_auth_used = false;

	/**
	 * @var bool
	 */
	protected $is_target_rest_request = false;

	/**
	 * Bootstrap singleton.
	 */
	public static function init(): void {
		if ( self::$instance ) {
			return;
		}

		self::$instance = new self();
	}

	protected function __construct() {
		$this->keys_repository = new ApiKeysRepository();

		add_filter( 'determine_current_user', array( $this, 'determine_current_user' ), 15 );
		add_filter( 'rest_authentication_errors', array( $this, 'rest_authentication_errors' ), 15 );
		add_filter( 'rest_post_dispatch', array( $this, 'rest_post_dispatch' ), 10, 3 );
	}

	/**
	 * Determine current user for MCP route using API key credentials.
	 *
	 * @param int|false $user_id Previously resolved user ID.
	 *
	 * @return int|false
	 */
	public function determine_current_user( $user_id ) {
		$this->auth_error        = null;
		$this->api_key_present   = false;
		$this->legacy_auth_used  = false;
		$this->is_target_rest_request = $this->is_target_rest_request();

		if ( ! $this->is_target_rest_request ) {
			return $user_id;
		}

		AuthContext::reset();

		$credentials = $this->parse_credentials();
		if ( ! $credentials['present'] ) {
			$this->legacy_auth_used = $this->is_legacy_auth_allowed();
			return $user_id;
		}

		$this->api_key_present = true;

		$consumer_key    = $credentials['consumer_key'];
		$consumer_secret = $credentials['consumer_secret'];

		if ( '' === $consumer_key || '' === $consumer_secret ) {
			$this->auth_error = $this->invalid_credentials_error();
			return 0;
		}

		$key = $this->keys_repository->find_by_consumer_key( $consumer_key );
		if ( ! $key || empty( $key->consumer_secret ) || ! $this->keys_repository->verify_secret_hash( (string) $key->consumer_secret, $consumer_secret ) ) {
			$this->auth_error = $this->invalid_credentials_error();
			return 0;
		}

		$resolved_user_id = absint( $key->user_id );
		if ( $resolved_user_id <= 0 || ! get_user_by( 'id', $resolved_user_id ) ) {
			$this->auth_error = $this->invalid_credentials_error();
			return 0;
		}

		AuthContext::set_api_key_auth(
			absint( $key->key_id ),
			$resolved_user_id,
			(string) $key->permissions
		);

		$this->legacy_auth_used = false;

		return $resolved_user_id;
	}

	/**
	 * Normalize auth errors for invalid API key attempts.
	 *
	 * @param WP_Error|null|bool $error Existing error from other authenticators.
	 *
	 * @return WP_Error|null|bool
	 */
	public function rest_authentication_errors( $error ) {
		if ( ! $this->is_target_rest_request && ! $this->is_target_rest_request() ) {
			return $error;
		}

		if ( ! empty( $error ) ) {
			return $error;
		}

		if ( $this->auth_error instanceof WP_Error ) {
			return $this->auth_error;
		}

		if ( ! $this->is_legacy_auth_allowed() && ! AuthContext::is_api_key_auth() ) {
			return new WP_Error(
				'learnpress_mcp_api_key_required',
				__( 'MCP API key authentication is required.', 'learnpress' ),
				array( 'status' => 401 )
			);
		}

		return $error;
	}

	/**
	 * Post-dispatch behavior: usage metrics + legacy deprecation header.
	 *
	 * @param mixed           $result  REST response object.
	 * @param mixed           $server  REST server instance.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return mixed
	 */
	public function rest_post_dispatch( $result, $server, $request ) {
		unset( $server );

		if ( ! ( $request instanceof WP_REST_Request ) ) {
			return $result;
		}

		if ( ! $this->is_target_route_from_request( $request ) ) {
			return $result;
		}

		if ( AuthContext::is_api_key_auth() && ! AuthContext::is_usage_touched() ) {
			$key_id = AuthContext::get_key_id();
			if ( $key_id > 0 ) {
				$this->keys_repository->touch_usage( $key_id );
				AuthContext::mark_usage_touched();
			}
		}

		if ( $this->legacy_auth_used && $this->is_legacy_auth_allowed() && get_current_user_id() > 0 && method_exists( $result, 'header' ) ) {
			$result->header( 'X-LearnPress-MCP-Auth-Deprecated', 'use_api_keys' );
		}

		return $result;
	}

	/**
	 * Parse API key credentials from query params or Basic auth.
	 *
	 * @return array<string, mixed>
	 */
	protected function parse_credentials(): array {
		$consumer_key_present    = isset( $_GET['consumer_key'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$consumer_secret_present = isset( $_GET['consumer_secret'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$consumer_key    = $consumer_key_present ? sanitize_text_field( wp_unslash( $_GET['consumer_key'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$consumer_secret = $consumer_secret_present ? sanitize_text_field( wp_unslash( $_GET['consumer_secret'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( $consumer_key_present || $consumer_secret_present ) {
			return array(
				'present'         => true,
				'consumer_key'    => $consumer_key,
				'consumer_secret' => $consumer_secret,
			);
		}

		$has_php_auth_user = isset( $_SERVER['PHP_AUTH_USER'] );
		$has_php_auth_pw   = isset( $_SERVER['PHP_AUTH_PW'] );

		if ( $has_php_auth_user || $has_php_auth_pw ) {
			$basic_user = sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_USER'] ?? '' ) );
			if ( ! $this->looks_like_consumer_key( $basic_user ) ) {
				return array(
					'present'         => false,
					'consumer_key'    => '',
					'consumer_secret' => '',
				);
			}

			return array(
				'present'         => true,
				'consumer_key'    => $basic_user,
				'consumer_secret' => sanitize_text_field( wp_unslash( $_SERVER['PHP_AUTH_PW'] ?? '' ) ),
			);
		}

		$authorization = $this->get_authorization_header();
		if ( stripos( $authorization, 'Basic ' ) !== 0 ) {
			return array(
				'present'         => false,
				'consumer_key'    => '',
				'consumer_secret' => '',
			);
		}

		$decoded = base64_decode( trim( substr( $authorization, 6 ) ), true );
		if ( false === $decoded || strpos( $decoded, ':' ) === false ) {
			return array(
				'present'         => true,
				'consumer_key'    => '',
				'consumer_secret' => '',
			);
		}

		list( $consumer_key, $consumer_secret ) = explode( ':', $decoded, 2 );
		$consumer_key = sanitize_text_field( $consumer_key );
		if ( ! $this->looks_like_consumer_key( $consumer_key ) ) {
			return array(
				'present'         => false,
				'consumer_key'    => '',
				'consumer_secret' => '',
			);
		}

		return array(
			'present'         => true,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => sanitize_text_field( $consumer_secret ),
		);
	}

	/**
	 * Read Authorization header from server/global headers.
	 */
	protected function get_authorization_header(): string {
		if ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
			return (string) wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
		}

		if ( function_exists( 'getallheaders' ) ) {
			$headers = getallheaders();
			if ( is_array( $headers ) ) {
				foreach ( $headers as $key => $value ) {
					if ( 'authorization' === strtolower( (string) $key ) ) {
						return (string) $value;
					}
				}
			}
		}

		return '';
	}

	/**
	 * Whether current request targets the MCP default route.
	 */
	protected function is_target_rest_request(): bool {
		if ( ! defined( 'REST_REQUEST' ) || ! REST_REQUEST ) {
			return false;
		}

		$rest_route = isset( $_GET['rest_route'] ) ? sanitize_text_field( wp_unslash( $_GET['rest_route'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $rest_route && strpos( $rest_route, self::MCP_ROUTE ) === 0 ) {
			return (bool) apply_filters( 'learn-press/mcp/api-keys/is-target-rest-request', true, $rest_route, self::MCP_ROUTE );
		}

		$request_uri = '';
		if ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		if ( '' === $request_uri ) {
			return false;
		}

		$rest_prefix   = trailingslashit( rest_get_url_prefix() );
		$target_path   = $rest_prefix . ltrim( self::MCP_ROUTE, '/' );
		$is_mcp_target = false !== strpos( $request_uri, $target_path );

		return (bool) apply_filters( 'learn-press/mcp/api-keys/is-target-rest-request', $is_mcp_target, $request_uri, self::MCP_ROUTE );
	}

	/**
	 * Whether a WP_REST_Request route is the MCP endpoint.
	 */
	protected function is_target_route_from_request( WP_REST_Request $request ): bool {
		$route = (string) $request->get_route();

		return 0 === strpos( $route, self::MCP_ROUTE );
	}

	/**
	 * Whether temporary legacy auth mode is enabled.
	 */
	protected function is_legacy_auth_allowed(): bool {
		return 'yes' === \LP_Settings::get_option( 'mcp_allow_legacy_auth', 'yes' );
	}

	/**
	 * Standardized invalid credentials error.
	 */
	protected function invalid_credentials_error(): WP_Error {
		return new WP_Error(
			'learnpress_mcp_invalid_api_key_credentials',
			__( 'Invalid MCP API credentials.', 'learnpress' ),
			array( 'status' => 401 )
		);
	}

	/**
	 * Validate expected consumer key format.
	 */
	protected function looks_like_consumer_key( string $consumer_key ): bool {
		return 1 === preg_match( '/^ck_[a-f0-9]{40}$/', $consumer_key );
	}
}

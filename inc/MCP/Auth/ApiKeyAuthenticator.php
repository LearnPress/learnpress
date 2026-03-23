<?php

namespace LearnPress\MCP\Auth;

use LP_Helper;
use WP_Error;
use WP_REST_Request;

defined( 'ABSPATH' ) || exit;

/**
 * Authenticates MCP HTTP transport requests using LearnPress API keys.
 */
class ApiKeyAuthenticator {
	/**
	 * Core MCP REST route path.
	 */
	public const MCP_ROUTE = '/mcp/mcp-adapter-default-server';

	/**
	 * LearnPress MCP alias route.
	 */
	public const MCP_ALIAS_ROUTE = '/lp/v1/mcp';
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
	protected $is_target_rest_request = false;
	/**
	 * Bootstrap singleton.
	 *
	 * @return void
	 */
	public static function init(): void {

		if ( self::$instance ) {
			return;
		}

		self::$instance = new self();
	}

	/**
	 * Register repository and auth lifecycle hooks.
	 *
	 * @return void
	 */
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
		return $this->authenticate_request( $user_id );
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

		if ( ! AuthContext::is_api_key_auth() && ! ( $this->auth_error instanceof WP_Error ) ) {
			$resolved_user_id = $this->authenticate_request( 0 );
			if ( is_numeric( $resolved_user_id ) && (int) $resolved_user_id > 0 ) {
				wp_set_current_user( (int) $resolved_user_id );
			}
		}

		if ( $this->auth_error instanceof WP_Error ) {
			return $this->auth_error;
		}

		if ( ! AuthContext::is_api_key_auth() ) {
			return new WP_Error(
				'learnpress_mcp_api_key_required',
				__( 'MCP API key authentication is required.', 'learnpress' ),
				array( 'status' => 401 )
			);
		}

		return $error;
	}

	/**
	 * Attempt API-key authentication for current MCP request.
	 *
	 * @param int|false $user_id Previously resolved user ID.
	 *
	 * @return int|false
	 */
	protected function authenticate_request( $user_id ) {

		$this->auth_error             = null;
		$this->api_key_present        = false;
		$this->is_target_rest_request = $this->is_target_rest_request();
		if ( ! $this->is_target_rest_request ) {
			return $user_id;
		}

		AuthContext::reset();

		$credentials = $this->parse_credentials();
		if ( ! $credentials['present'] ) {
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

		return $resolved_user_id;
	}

	/**
	 * Post-dispatch behavior: update usage metrics for API-key-authenticated requests.
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

		$consumer_key    = $consumer_key_present ? LP_Helper::sanitize_params_submitted( $_GET['consumer_key'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$consumer_secret = $consumer_secret_present ? LP_Helper::sanitize_params_submitted( $_GET['consumer_secret'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

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
			$basic_user = LP_Helper::sanitize_params_submitted( $_SERVER['PHP_AUTH_USER'] ?? '' );
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
				'consumer_secret' => LP_Helper::sanitize_params_submitted( $_SERVER['PHP_AUTH_PW'] ?? '' ),
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
		$consumer_key                           = LP_Helper::sanitize_params_submitted( $consumer_key, 'text', false );
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
			'consumer_secret' => LP_Helper::sanitize_params_submitted( $consumer_secret, 'text', false ),
		);
	}

	/**
	 * Read Authorization header from server/global headers.
	 *
	 * @return string
	 */
	protected function get_authorization_header(): string {

		$server_header_candidates = array(
			'HTTP_AUTHORIZATION',
			'REDIRECT_HTTP_AUTHORIZATION',
			'REDIRECT_REDIRECT_HTTP_AUTHORIZATION',
		);
		foreach ( $server_header_candidates as $server_key ) {
			if ( ! empty( $_SERVER[ $server_key ] ) ) {
				return (string) wp_unslash( $_SERVER[ $server_key ] );
			}
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

		if ( function_exists( 'apache_request_headers' ) ) {
			$headers = apache_request_headers();
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
	 *
	 * @return bool
	 */
	protected function is_target_rest_request(): bool {
		$rest_route = isset( $_GET['rest_route'] ) ? LP_Helper::sanitize_params_submitted( $_GET['rest_route'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $rest_route && $this->route_matches_mcp_target( $rest_route ) ) {
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
		$is_mcp_target = false;
		foreach ( $this->get_target_routes() as $route ) {
			$target_path = $rest_prefix . ltrim( $route, '/' );
			if ( false !== strpos( $request_uri, $target_path ) ) {
				$is_mcp_target = true;
				break;
			}
		}

		return (bool) apply_filters( 'learn-press/mcp/api-keys/is-target-rest-request', $is_mcp_target, $request_uri, self::MCP_ROUTE );
	}
	/**
	 * Whether a WP_REST_Request route is the MCP endpoint.
	 *
	 * @param WP_REST_Request $request Current REST request object.
	 *
	 * @return bool
	 */
	protected function is_target_route_from_request( WP_REST_Request $request ): bool {

		$route = (string) $request->get_route();

		return $this->route_matches_mcp_target( $route );
	}

	/**
	 * Target routes that should use LearnPress MCP auth behavior.
	 *
	 * @return array<int, string>
	 */
	protected function get_target_routes(): array {

		return array(
			self::MCP_ROUTE,
			self::MCP_ALIAS_ROUTE,
		);
	}

	/**
	 * Whether a route path matches one of MCP target routes.
	 *
	 * @param string $route Route path from request.
	 *
	 * @return bool
	 */
	protected function route_matches_mcp_target( string $route ): bool {

		foreach ( $this->get_target_routes() as $target_route ) {
			if ( 0 === strpos( $route, $target_route ) ) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Standardized invalid credentials error.
	 *
	 * @return WP_Error
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
	 *
	 * @param string $consumer_key Plaintext consumer key.
	 *
	 * @return bool
	 */
	protected function looks_like_consumer_key( string $consumer_key ): bool {

		return 1 === preg_match( '/^ck_[a-f0-9]{40}$/', $consumer_key );
	}
}

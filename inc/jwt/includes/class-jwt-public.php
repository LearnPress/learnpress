<?php
/**
 * REST API: LP_Jwt_Public
 *
 * @package LPJWTAuth
 * @since 1.0.0
 * @author Nhamdv <daonham95@gmail.com>
 */

use \Firebase\JWT\JWT;

class LP_Jwt_Public {
	private $name;

	private $version;

	private $namespace;

	private $jwt_error;

	private $secret_key;

	public function __construct( $name, $version ) {
		$this->name      = $name;
		$this->version   = $version;
		$this->namespace = $this->name . '/' . $this->version;

		if ( ! defined( 'SECURE_AUTH_KEY' ) && ! defined( 'LP_SECURE_AUTH_KEY' ) ) {
			return;
		}

		$this->secret_key = defined( 'LP_SECURE_AUTH_KEY' ) ? LP_SECURE_AUTH_KEY : SECURE_AUTH_KEY;
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'token',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'generate_token' ),
				'args'                => array(
					'username' => array(
						'description'       => esc_html__( 'The username of the user.', 'learnpress' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
					),
					'password' => array(
						'description'       => esc_html__( 'The password of the user.', 'learnpress' ),
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
						'validate_callback' => 'rest_validate_request_arg',
					),
				),
				'schema'              => array( $this, 'get_item_schema' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/validate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/register',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'register' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => esc_html__( 'JSON Web Token', 'learnpress' ),
			'type'       => 'object',
			'properties' => array(
				'token'      => array(
					'description' => esc_html__( 'JSON Web Token.', 'learnpress' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'user_id'    => array(
					'description' => esc_html__( 'The ID of the user.', 'learnpress' ),
					'type'        => 'integer',
					'readonly'    => true,
				),
				'user_login' => array(
					'description' => esc_html__( 'The username of the user', 'learnpress' ),
					'type'        => 'string',
					'readonly'    => true,
				),
				'user_email' => array(
					'description' => esc_html__( 'The email address of the user.', 'learnpress' ),
					'type'        => 'string',
					'readonly'    => true,
				),
			),
		);

		return apply_filters( 'lp_rest_authentication_token_schema', $schema );
	}

	/**
	 * Add CORs support to the request.
	 */
	public function add_cors_support() {
		$enable_cors = defined( 'LP_JWT_AUTH_CORS_ENABLE' ) ? LP_JWT_AUTH_CORS_ENABLE : false;

		if ( $enable_cors ) {
			$headers = apply_filters( 'lp_jwt_auth_cors_allow_headers', 'Access-Control-Allow-Headers, Content-Type, Authorization' );
			header( sprintf( 'Access-Control-Allow-Headers: %s', $headers ) );
		}
	}

	public function register( WP_REST_Request $request ) {
		$username         = $request->get_param( 'username' );
		$password         = $request->get_param( 'password' );
		$confirm_password = $request->get_param( 'confirm_password' );
		$email            = $request->get_param( 'email' );

		$customer_id = LP_Forms_Handler::learnpress_create_new_customer( $email, $username, $password, $confirm_password );

		if ( is_wp_error( $customer_id ) ) {
			return new WP_Error(
				$customer_id->get_error_code(),
				$customer_id->get_error_message(),
				array(
					'status' => 403,
				)
			);
		}

		return $this->generate_token( $request );
	}

	public function generate_token( WP_REST_Request $request ) {
		$secret_key = $this->secret_key;
		$username   = $request->get_param( 'username' );
		$password   = $request->get_param( 'password' );

		if ( ! $secret_key ) {
			return new WP_Error(
				'lp_jwt_auth_bad_config',
				esc_html__( 'LearnPress JWT is not configurated properly, please contact the admin', 'learnpress' ),
				array(
					'status' => 403,
				)
			);
		}

		/** Try to authenticate the user with the passed credentials*/
		$user = wp_authenticate( $username, $password );

		/** If the authentication fails return a error*/
		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();

			return new WP_Error(
				'[lp_jwt_auth] ' . $error_code,
				$user->get_error_message( $error_code ),
				array(
					'status' => 403,
				)
			);
		}

		/** Valid credentials, the user exists create the according Token */
		$issued_at  = time();
		$not_before = apply_filters( 'lp_jwt_auth_not_before', $issued_at, $issued_at );
		$expire     = apply_filters( 'lp_jwt_auth_expire', $issued_at + WEEK_IN_SECONDS, $issued_at );

		$token = array(
			'iss'  => get_bloginfo( 'url' ),
			'iat'  => $issued_at,
			'nbf'  => $not_before,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id' => $user->data->ID,
				),
			),
		);

		/** Let the user modify the token data before the sign. */
		$token = JWT::encode( apply_filters( 'lp_jwt_auth_token_before_sign', $token, $user ), $secret_key );

		/** The token is signed, now create the object with no sensible user data to the client*/
		$data = array(
			'token'             => $token,
			'user_id'           => $user->data->ID,
			'user_login'        => $user->data->user_login,
			'user_email'        => $user->data->user_email,
			'user_display_name' => $user->data->display_name,
		);

		return apply_filters( 'lp_jwt_auth_token_before_dispatch', $data, $user );
	}

	/**
	 * This is our Middleware to try to authenticate the user according to the
	 * token send.
	 *
	 * @param (int|bool) $user Logged User ID
	 *
	 * @return (int|bool)
	 */
	public function determine_current_user( $user_id ) {
		$rest_prefix   = trailingslashit( rest_get_url_prefix() );
		$request_uri   = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$valid_api_uri = strpos( $request_uri, $rest_prefix . $this->name . '/' );

		/**
		 * Only check when rest url has wp-json/learnpress/.
		 */
		if ( ! empty( $user_id ) || $valid_api_uri === false ) {
			return $user_id;
		}

		/*
		 * if the request URI is for validate the token don't do anything,
		 * this avoid double calls to the validate_token function.
		 */
		$validate_token = strpos( $request_uri, '/token' );

		/** All course is public so donot need token */
		$is_rest_courses = strpos( $request_uri, '/courses' ) || strpos( $request_uri, '/reset-password' ) || strpos( $request_uri, '/course_category' ) || strpos( $request_uri, '/sections/' ) || strpos( $request_uri, '/section-items/' ) || strpos( $request_uri, '/users' );

		if ( $validate_token > 0 ) {
			return $user_id;
		}

		$token = $this->validate_token( false );

		if ( is_wp_error( $token ) ) {
			if ( ! $is_rest_courses ) {
				$this->jwt_error = $token;
			}

			return $user_id;
		}

		return $token->data->user->id;
	}

	public function validate_token( $output = true ) {
		/*
		 * Looking for the HTTP_AUTHORIZATION header, if not present just
		 * return the user.
		 */
		$auth = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['HTTP_AUTHORIZATION'] ) : false;

		/* Double check for different auth header string (server dependent) */
		if ( ! $auth ) {
			$auth = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? sanitize_text_field( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) : false;
		}

		if ( ! $auth ) {
			return new WP_Error(
				'lp_jwt_auth_no_auth_header',
				esc_html__( 'Authorization header not found.', 'learnpress' ),
				array(
					'status' => 401,
				)
			);
		}

		/*
		 * The HTTP_AUTHORIZATION is present verify the format
		 * if the format is wrong return the user.
		 */
		list( $token ) = sscanf( $auth, 'Bearer %s' );

		if ( ! $token ) {
			return new WP_Error(
				'lp_jwt_auth_bad_auth_header',
				esc_html__( 'Authentication token is missing.', 'learnpress' ),
				array(
					'status' => 401,
				)
			);
		}

		/** Get the Secret Key */
		$secret_key = $this->secret_key;

		if ( ! $secret_key ) {
			return new WP_Error(
				'lp_jwt_auth_bad_config',
				esc_html__( 'LearnPress JWT is not configurated properly, please contact the admin', 'learnpress' ),
				array(
					'status' => 401,
				)
			);
		}

		/** Try to decode the token */
		try {
			$token = JWT::decode( $token, $secret_key, array( 'HS256' ) );

			/** The Token is decoded now validate the iss */
			if ( $token->iss != get_bloginfo( 'url' ) ) {
				return new WP_Error(
					'lp_jwt_auth_bad_iss',
					esc_html__( 'The iss do not match with this server', 'learnpress' ),
					array(
						'status' => 401,
					)
				);
			}

			/** So far so good, validate the user id in the token */
			if ( ! isset( $token->data->user->id ) ) {
				return new WP_Error(
					'lp_jwt_auth_bad_request',
					esc_html__( 'User ID not found in the token', 'learnpress' ),
					array(
						'status' => 401,
					)
				);
			}

			if ( ! isset( $token->exp ) ) {
				return new WP_Error(
					'rest_authentication_missing_token_expiration',
					esc_html__( 'The token must have an expiration date.', 'learnpress' ),
					array(
						'status' => 401,
					)
				);
			}

			if ( time() > $token->exp ) {
				return new WP_Error(
					'rest_authentication_token_expired',
					esc_html__( 'The token has expired.', 'learnpress' ),
					array(
						'status' => 401,
					)
				);
			}

			/** Everything looks good return the decoded token if the $output is false */
			if ( ! $output ) {
				return $token;
			}

			/** If the output is true return an answer to the request to show it */
			return array(
				'code'    => 'lp_jwt_auth_valid_token',
				'message' => esc_html__( 'Valid access token.', 'learnpress' ),
				'data'    => array(
					'status' => 200,
					'exp'    => $token->exp - time(),
				),
			);
		} catch ( Exception $e ) {
			return new WP_Error(
				'lp_jwt_auth_invalid_token',
				$e->getMessage(),
				array(
					'status' => 401,
				)
			);
		}
	}

	/**
	 * Filter to hook the rest_pre_dispatch, if the is an error in the request
	 * send it, if there is no error just continue with the current request.
	 *
	 * @param $request
	 */
	public function rest_pre_dispatch( $request ) {
		if ( is_wp_error( $this->jwt_error ) ) {
			return $this->jwt_error;
		}

		return $request;
	}
}

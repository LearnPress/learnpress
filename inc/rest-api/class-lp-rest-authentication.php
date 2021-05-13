<?php

/**
 * Class LP_REST_Authentication
 *
 * @since 3.3.0
 */
class LP_REST_Authentication {

	/**
	 * @var string
	 */
	protected static $wp_rest_nonce = '';

	/**
	 * @var int
	 */
	protected static $wp_current_user_id = 0;

	public function __construct() {
		add_action( 'rest_authentication_errors', array( $this, 'rest_cookie_check_errors' ), 0 );
		// add_filter( 'determine_current_user', array( $this, 'authenticate' ), 15 );
		// add_filter( 'rest_pre_dispatch', array( $this, 'check_user_permissions' ), 10, 3 );
	}

	/**
	 * @param $result
	 *
	 * @return mixed
	 */
	public function rest_cookie_check_errors( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( is_user_logged_in() ) {
			self::$wp_rest_nonce      = wp_create_nonce( 'wp_rest' );
			self::$wp_current_user_id = get_current_user_id();
		}

		return $result;
	}

	/**
	 * Check if request is rest api.
	 *
	 * @return bool
	 */
	public function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			return false;
		}

		$rest_prefix = trailingslashit( rest_get_url_prefix() );
		$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );

		return apply_filters( 'learn-press/is-rest-api-request', false !== strpos( $request_uri, $rest_prefix . 'lp/' ) );
	}

	/**
	 * @param int $user_id
	 *
	 * @return int
	 */
	public function authenticate( $user_id ) {
		if ( $user_id || ! $this->is_rest_api_request() ) {
			return $user_id;
		}

		if ( is_ssl() ) {
			$user_id = $this->perform_basic_authentication();
		}

		if ( $user_id ) {
			return $user_id;
		}

		return $this->perform_oauth_authentication();
	}

	public function perform_basic_authentication() {
		return 2;
	}

	public function perform_oauth_authentication() {

		return 2;

		$params = $this->get_oauth_parameters();
		if ( empty( $params ) ) {
			return false;
		}

		// Fetch WP user by consumer key.
		$this->user = $this->get_user_data_by_consumer_key( $params['oauth_consumer_key'] );

		if ( empty( $this->user ) ) {
			$this->set_error( new WP_Error( 'learnpress_rest_authentication_error', __( 'Consumer key is invalid.', 'learnpress' ), array( 'status' => 401 ) ) );

			return false;
		}

		// Perform OAuth validation.
		$signature = $this->check_oauth_signature( $this->user, $params );
		if ( is_wp_error( $signature ) ) {
			$this->set_error( $signature );
			return false;
		}

		$timestamp_and_nonce = $this->check_oauth_timestamp_and_nonce( $this->user, $params['oauth_timestamp'], $params['oauth_nonce'] );
		if ( is_wp_error( $timestamp_and_nonce ) ) {
			$this->set_error( $timestamp_and_nonce );
			return false;
		}

		return $this->user->user_id;
	}

	public function get_user_by_consumer_key() {

	}

	public function check_user_permissions() {

	}

	/**
	 * @return string
	 */
	public static function get_wp_rest_nonce() {
		return self::$wp_rest_nonce;
	}

	/**
	 * @return int
	 */
	public static function get_wp_user_id() {
		return self::$wp_current_user_id;
	}

	/**
	 * Check permission of a user on a post type.
	 *
	 * @param string $post_type
	 * @param string $permission
	 * @param int    $user_id
	 *
	 * @return boolean
	 */
	public static function check_post_permissions( $post_type, $permission = 'read', $user_id = 0 ) {
		if ( ! $user_id ) {
			$user_id = self::get_wp_user_id();
		}

		$permissions = array(
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
			'batch'  => 'edit_others_posts',
		);

		if ( 'revision' === $post_type ) {
			$user_permission = false;
		} else {
			$cap              = $permissions[ $permission ];
			$post_type_object = get_post_type_object( $post_type );
			$user_permission  = current_user_can( $post_type_object->cap->$cap, $user_id );
		}

		return apply_filters( 'learn-press/rest/check-permission', $user_permission, $permission, $user_id, $post_type );
	}

	/**
	 * Check permission if user is logged in.
	 *
	 * @return bool
	 */
	public static function check_logged_in_permission() {
		return ! ! self::get_wp_user_id();
	}

	/**
	 * Check permission if user is administrator.
	 *
	 * @return bool
	 */
	public static function check_admin_permission() {
		return current_user_can( ADMIN_ROLE );
	}
}

return new LP_REST_Authentication();

<?php

/**
 * Abstract LP_Async_Request class.
 *
 * @since 4.1.6.9.4
 * @version 1.0.1
 */
abstract class LP_Async_Request {
	/**
	 * Prefix
	 * @var string
	 */
	protected $prefix = 'lp';

	/**
	 * Action
	 * @var string
	 */
	protected $action = 'async_request';

	/**
	 * Identifier
	 *
	 * @var string
	 */
	protected $identifier;

	/**
	 * Constant identifier for a task that should be available to logged-in users
	 */
	const LOGGED_IN = 1;

	/**
	 * Constant identifier for a task that should be available to logged-out users
	 */
	const LOGGED_OUT = 2;

	/**
	 * Constant identifier for a task that should be available to all users regardless of auth status
	 */
	const BOTH = 3;

	/**
	 * Data
	 *
	 * (default value: array())
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Initiate new async request
	 */
	public function __construct( $auth_level = self::BOTH ) {
		$this->identifier = $this->prefix . '_' . $this->action;

		//add_action( 'wp_ajax_' . $this->identifier, array( $this, 'maybe_handle' ) );
		//add_action( 'wp_ajax_nopriv_' . $this->identifier, array( $this, 'maybe_handle' ) );

		if ( $auth_level & self::LOGGED_IN ) {
			add_action( "admin_post_lp_async_$this->identifier", [ $this, 'maybe_handle' ] );
		}
		if ( $auth_level & self::LOGGED_OUT ) {
			add_action( "admin_post_nopriv_lp_async_$this->identifier", [ $this, 'maybe_handle' ] );
		}
	}

	/**
	 * Set data used during the request
	 *
	 * @param array $data Data.
	 *
	 * @return $this
	 */
	public function data( array $data ): LP_Async_Request {
		$this->data = $data;

		return $this;
	}

	/**
	 * Dispatch the async request
	 *
	 * @return array|WP_Error
	 */
	public function dispatch() {
		$url  = esc_url_raw( $this->get_query_url() );
		$args = $this->get_post_args();

		return wp_remote_post( $url, $args );
	}

	/**
	 * Get query URL
	 *
	 * @return string
	 */
	protected function get_query_url(): string {
		if ( property_exists( $this, 'query_url' ) ) {
			return $this->query_url;
		}

		$url = admin_url( 'admin-post.php' );
		return apply_filters( $this->identifier . '/query_url', $url );
	}

	/**
	 * Get post args
	 *
	 * @return array
	 */
	protected function get_post_args(): array {
		$identifier           = $this->identifier;
		$this->data['action'] = "lp_async_{$identifier}";
		$this->data['_nonce'] = $this->create_async_nonce();

		$args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'body'      => $this->data,
			'cookies'   => $_COOKIE,
			'sslverify' => is_ssl(),
		);

		/**
		 * Filters the post arguments used during an async request.
		 *
		 * @param array $args
		 */
		return apply_filters( $this->identifier . '_post_args', $args );
	}

	/**
	 * Create nonce for async request
	 *
	 * @return false|string
	 */
	protected function create_async_nonce() {
		$action = $this->get_nonce_action();
		$i      = wp_nonce_tick();

		return substr( wp_hash( $i . $action . get_class( $this ), 'nonce' ), - 12, 10 );
	}

	/**
	 * Verify that the correct nonce was used within the time limit.
	 *
	 * @param string $nonce
	 *
	 * @return bool
	 */
	protected function verify_async_nonce( string $nonce ): bool {
		$action = $this->get_nonce_action();
		$i      = wp_nonce_tick();

		// Nonce generated 0-12 hours ago
		if ( substr( wp_hash( $i . $action . get_class( $this ), 'nonce' ), - 12, 10 ) == $nonce ) {
			return 1;
		}

		// Nonce generated 12-24 hours ago
		if ( substr( wp_hash( ( $i - 1 ) . $action . get_class( $this ), 'nonce' ), - 12, 10 ) == $nonce ) {
			return 2;
		}

		// Invalid nonce
		return false;
	}

	/**
	 * Get a nonce action based on the $action property of the class
	 *
	 * @return string The nonce action for the current instance
	 */
	protected function get_nonce_action(): string {
		$action = $this->identifier;
		if ( substr( $action, 0, 7 ) === 'nopriv_' ) {
			$action = substr( $action, 7 );
		}

		return "lp_async_$action";
	}

	/**
	 * Maybe handle
	 *
	 * Check for correct nonce and pass to handler.
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing
		session_write_close();

		/**
		 * set params $_POST['lp_no_check_referer'] = 1
		 * for case: send request when user not login, but get request when user logged
		 * @editor tungnx
		 * @modify 4.1.4
		 */
		/*if ( ! isset( $_POST['lp_no_check_referer'] ) ) {
			check_ajax_referer( $this->identifier, 'nonce' );
		}*/

		if ( isset( $_POST['_nonce'] ) && $this->verify_async_nonce( $_POST['_nonce'] ) ) {
			if ( ! is_user_logged_in() ) {
				$this->identifier = "nopriv_$this->identifier";
			}

			$this->handle();
		}

		wp_die();
	}

	/**
	 * Handle
	 *
	 * Override this method to perform any actions required
	 * during the async request.
	 */
	abstract protected function handle();
}

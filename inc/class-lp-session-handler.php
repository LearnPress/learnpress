<?php

/**
 * Class LP_Session_Handler
 *
 * Only set COOKIE for user guest
 */
class LP_Session_Handler {
	/**
	 * @var string $_customer_id
	 */
	public $_customer_id = '';

	/**
	 * @var array $_data
	 */
	protected $_data = array();

	/**
	 * @var bool $_changed
	 */
	protected $_changed = false;

	/**
	 * @var string cookie name
	 */
	private $_cookie = 'lp_session_guest';

	/**
	 * @var int session expiration timestamp
	 */
	private $_session_expiration = 0;

	/**
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * LP_Session_Handler constructor.
	 *
	 * @version 3.2.2
	 */
	protected function __construct() {
		$this->init_hooks();
		if ( is_admin() ) {
			return;
		}

		$this->init();
	}

	protected function init_hooks() {
		add_action( 'wp_login', [ $this, 'handle_when_user_login_success' ], 10, 2 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
	}

	/**
	 * Set COOKIE for only user guest
	 * Set data: customer_id, session_expiration
	 *
	 * @return self
	 * @since 3.0.0
	 * @version 4.0.1
	 * @modify Tungnx
	 */
	protected function init(): LP_Session_Handler {
		$expire_time_for_guest = 2 * DAY_IN_SECONDS;
		$expire_time_for_user  = 6 * DAY_IN_SECONDS;

		// Set data for user Guest.
		if ( ! is_user_logged_in() ) { // Generate data and set cookie for guest
			$cookie = $this->get_cookie_data();
			// If cookie exists, set data from cookie for guest
			if ( empty( $cookie ) ) {
				// Create new cookie and session for user Guest.
				$this->set_session_expiration( $expire_time_for_guest );
				$this->_customer_id = apply_filters( 'lp/cookie/guest-id', 'g-' . uniqid() );
				$this->set_customer_session_cookie();
			} else {
				$this->_customer_id = $cookie;
			}
		} else { // Set data for user logged.
			$this->set_session_expiration( $expire_time_for_user );
			$this->_customer_id = get_current_user_id();
		}

		// Get session data from DB.
		$this->_data = $this->get_session_data();

		return $this;
	}

	/**
	 * Handle when user logged in.
	 *
	 * @param $user_name
	 * @param $user
	 *
	 * @return void
	 */
	public function handle_when_user_login_success( $user_name, $user ) {
		// Remove COOKIE for user guest.
		learn_press_remove_cookie( $this->_cookie );

		/**
		 * Must set wp_set_current_user to get_current_user_id and is_user_logged_in work correctly.
		 * Because WP note must do that.
		 * Read more @see wp_signon
		 * If version WP after run correctly, remove wp_set_current_user.
		 */
		wp_set_current_user( $user->ID );
		$user_id = get_current_user_id();

		/**
		 * Delete session of user guest before.
		 */
		if ( $user_id ) {
			$customer_id = $this->get_customer_id();
			$user_before = get_user_by( 'ID', $customer_id );
			if ( ! $user_before ) {
				$this->delete_session( $customer_id );
			}

			$this->_customer_id = $user_id;
		}
	}

	/**
	 * Set cookie for user guest.
	 *
	 * @return LP_Session_Handler
	 */
	public function set_customer_session_cookie(): LP_Session_Handler {
		// Set the cookie
		if ( ! isset( $_COOKIE[ $this->_cookie ] ) ) {
			learn_press_setcookie( $this->_cookie, $this->_customer_id, $this->_session_expiration );
		}

		return $this;
	}

	/**
	 * Check has session.
	 *
	 * @return bool
	 */
	public function has_session(): bool {
		return isset( $_COOKIE[ $this->_cookie ] ) || is_user_logged_in();
	}

	/**
	 * Set session expiration.
	 *
	 * @param int $duration
	 *
	 * @return LP_Session_Handler
	 */
	public function set_session_expiration( int $duration = 0 ): LP_Session_Handler {
		$this->_session_expiration = time() + $duration;

		return $this;
	}

	/**
	 * Get cookie of guest.
	 *
	 * @return string
	 */
	public function get_cookie_data(): string {
		if ( empty( $_COOKIE[ $this->_cookie ] ) || ! is_string( $_COOKIE[ $this->_cookie ] ) ) {
			return '';
		}

		return $_COOKIE[ $this->_cookie ];
	}

	/**
	 * Get session data
	 *
	 * @return array
	 */
	public function get_session_data(): array {
		if ( is_user_logged_in() ) {
			$customer_id = get_current_user_id();
		} else {
			$customer_id = $this->get_customer_id();
		}

		return (array) $this->get_session_by_customer_id( (string) $customer_id );
	}

	/**
	 * Save session data to the database.
	 *
	 * @return bool
	 * @version 4.0.1
	 * @since 3.0.0
	 */
	public function save_data(): bool {
		$res = false;

		try {
			$lp_session_db = LP_Sessions_DB::getInstance();

			// Check exists on DB.
			$filter              = new LP_Session_filter();
			$filter->collection  = $lp_session_db->tb_lp_sessions;
			$filter->field_count = 'session_id';
			$filter->limit       = 1;
			$filter->where[]     = $lp_session_db->wpdb->prepare( 'AND session_key = %s', $this->_customer_id );
			$get                 = $lp_session_db->execute( $filter );

			$data = [
				'session_key'    => (string) $this->_customer_id,
				'session_value'  => maybe_serialize( $this->_data ),
				'session_expiry' => $this->_session_expiration,
			];
			if ( ! empty( $get ) ) {
				// Update
				$lp_session_db->wpdb->update(
					$lp_session_db->tb_lp_sessions,
					$data,
					[ 'session_key' => $this->_customer_id ],
					[ '%s', '%s', '%d' ],
					[ '%s' ]
				);
				// Clear cache.
				LP_Session_Cache::instance()->clear( $this->_customer_id );
			} else {
				// Insert
				$lp_session_db->wpdb->insert(
					$lp_session_db->tb_lp_sessions,
					$data,
					[ '%s', '%s', '%d' ]
				);
			}

			$res = true;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $res;
	}

	/**
	 * Destroy session.
	 */
	public function destroy_session() {
		// Clear cookie.
		if ( ! empty( $this->_cookie ) ) {
			learn_press_remove_cookie( $this->_cookie );
		}

		// Clear session expire.
		$this->cleanup_sessions_expire();

		$logout_redirect_page_id = LP_Settings::get_option( 'logout_redirect_page_id' );
		if ( $logout_redirect_page_id ) {
			wp_safe_redirect( get_the_permalink( $logout_redirect_page_id ) );
			die;
		}
	}

	/**
	 * Clear session expired.
	 *
	 * @return bool
	 */
	public function cleanup_sessions_expire(): bool {
		$res           = true;
		$lp_session_db = LP_Sessions_DB::getInstance();

		try {
			// Get session expired.
			$filter_get          = new LP_Session_Filter();
			$filter_get->where[] = $lp_session_db->wpdb->prepare( 'AND session_expiry < %d', time() );
			$sessions            = $lp_session_db->get_sessions( $filter_get );
			// Clear cache.
			foreach ( $sessions as $session ) {
				LP_Session_Cache::instance()->clear( $session->session_key );
			}

			// Delete session expired.
			$filter             = new LP_Session_Filter();
			$filter->collection = $lp_session_db->tb_lp_sessions;
			$filter->where[]    = $lp_session_db->wpdb->prepare( 'AND session_expiry < %d', time() );
			$lp_session_db->delete_execute( $filter );
		} catch ( Throwable $e ) {
			$res = false;
			error_log( $e->getMessage() );
		}

		return $res;
	}

	/**
	 * Get session on DB.
	 *
	 * @param string $customer_id
	 *
	 * @return false|mixed|string
	 * @Todo - Tungnx should handle data when save type json instead serialize.
	 */
	public function get_session_by_customer_id( string $customer_id = '' ) {
		$lp_session_db = LP_Sessions_DB::getInstance();
		$session       = [];

		try {
			// Get cache.
			$session_cache = LP_Session_Cache::instance();
			$key_cache     = $customer_id;
			$session       = $session_cache->get_cache( $key_cache );
			if ( $session !== false ) {
				return $session;
			}

			$filter              = new LP_Session_Filter();
			$filter->session_key = $customer_id;
			$filter->only_fields = [ 'session_key', 'session_value' ];
			$filter->field_count = 'session_key';
			$filter->limit       = 1;
			$res                 = $lp_session_db->get_sessions( $filter );
			if ( ! empty( $res ) ) {
				$session = $res[0]->session_value;
			}

			$session = LP_Helper::maybe_unserialize( $session );
			// Set cache.
			$session_cache->set_cache( $key_cache, $session );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $session;
	}

	/**
	 * Delete session by customer_id on DB.
	 *
	 * @param string $customer_id
	 *
	 * @return bool
	 */
	public function delete_session( string $customer_id ): bool {
		global $wpdb;
		$rs = true;

		try {
			$wpdb->delete(
				LP_Sessions_DB::getInstance()->tb_lp_sessions,
				[ 'session_key' => $customer_id ],
				[ '%s' ]
			);
			// Clear cache.
			LP_Session_Cache::instance()->clear( $customer_id );
		} catch ( Throwable $e ) {
			$rs = false;
			error_log( $e->getMessage() );
		}

		return $rs;
	}

	/**
	 * Get session value.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed|null
	 */
	public function get( string $key, $default = null ) {
		return isset( $this->_data[ $key ] ) ? LP_Helper::maybe_unserialize( $this->_data[ $key ] ) : $default;
	}

	/**
	 * Set session value.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @param bool $force_change
	 */
	public function set( string $key, $value, bool $force_change = false ) {
		$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );

		if ( $force_change ) {
			$this->save_data();
		}
	}

	/**
	 * Remove a value from session by key.
	 *
	 * @param string $key
	 * @param bool   $force_change
	 */
	public function remove( string $key, bool $force_change = false ) {
		if ( ! array_key_exists( $key, $this->_data ) ) {
			return;
		}
		unset( $this->_data[ $key ] );

		if ( $force_change ) {
			$this->save_data();
		}
	}

	/**
	 * Get customer id.
	 */
	public function get_customer_id(): string {
		return (string) $this->_customer_id;
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

/**
 * @param      $key
 * @param null $default
 *
 * @return array|string
 */
function learn_press_session_get( $key, $default = null ) {
	return LP_Session_Handler::instance()->get( $key, $default );
}

/**
 * @param $key
 * @param $value
 */
function learn_press_session_set( $key, $value ) {
	LP_Session_Handler::instance()->set( $key, $value );
}

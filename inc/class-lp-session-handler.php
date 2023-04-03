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
	 * $var bool based on whether a cookie exists
	 * @deprecated 4.2.0
	 */
	private $_has_cookie = false;

	/**
	 * @since 3.2.2
	 *
	 * @var bool
	 */
	private $_has_browser_cookie = true;

	/**
	 * @var string Custom session table name
	 * @deprecated 4.2.0
	 */
	private $_table;

	/**
	 * @var null
	 */
	private static $_instance = null;

	/**
	 * __get function.
	 *
	 * @param mixed $key
	 *
	 * @return mixed
	 * @deprecated 4.2.0
	 * Addon Stripe, 2Checkout, Authorize, Certificate is using this method via call session->order_awaiting_payment
	 * After change all to session->set('order_awaiting_payment') we can remove this method.
	 */
	public function __get( $key ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		//return $this->get( $key );
	}

	/**
	 * __set function.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @deprecated 4.2.0
	 * Addon Stripe, 2Checkout, Authorize, Certificate is using this method via call session->order_awaiting_payment
	 * After change all to session->set('order_awaiting_payment') we can remove this method.
	 */
	public function __set( $key, $value ) {
		_deprecated_function( 'LP_Session::__set', '4.2.0' );
		// if ( $key === 'order_awaiting_payment' ) {

		// }
		//$this->set( $key, $value );
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 * @deprecated 4.2.0
	 */
	public function __isset( $key ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		//return isset( $this->_data[ sanitize_key( $key ) ] );
	}

	/**
	 * __unset function.
	 *
	 * @param mixed $key
	 * @deprecated 4.2.0
	 */
	public function __unset( $key ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_changed = true;
		}
	}

	//protected $schedule_id = 'learn-press/clear-expired-session';

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
		//add_action( 'learn_press_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		//add_action( 'learn_press_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
		//add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		//add_action( 'wp', array( $this, 'schedule_event' ) );
		//add_action( $this->schedule_id, array( $this, 'cleanup_sessions' ), 10 );
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
				$this->_customer_id = 'g-' . uniqid();
				$this->set_customer_session_cookie();
			} else {
				$this->_customer_id = $cookie;
			}
		} else { // Set data for user logged.
			$this->set_session_expiration( $expire_time_for_user );
			$this->_customer_id = get_current_user_id();
		}

		//$this->_data = $this->get_session_data();

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
		 * Don't know why WP 6.3 and lower run wrong.
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
				$this->delete_session( $customer_id . '' );
			}

			$this->_customer_id = $user_id;
		}
	}

	/*public function schedule_event() {
		if ( ! wp_next_scheduled( $this->schedule_id ) ) {
			wp_schedule_event( time(), 'hourly', $this->schedule_id );
		}
	}*/

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
	 * @deprecated 4.2.0
	 */
	public function has_cookie() {
		_deprecated_function( __METHOD__, '4.2.0' );
		//return $this->_has_cookie && $this->_has_browser_cookie;
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
	 * Generate string customer id for guest
	 *
	 * @return string
	 * @deprecated 4.2.2
	 */
	public function generate_guest_id(): string {
		require_once ABSPATH . 'wp-includes/class-phpass.php';
		$hasher = new PasswordHash( 12, false );

		return md5( $hasher->get_random_bytes( 32 ) );
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
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @param string $group
	 * @deprecated 4.2.0
	 */
	public function incr_cache_prefix( $group ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		//wp_cache_incr( 'learn_press_' . $group . '_cache_prefix', 1, $group );
	}

	/**
	 * Save session data to the database.
	 *
	 * @return bool
	 */
	public function save_data(): bool {
		$res = false;

		try {
			if ( $this->_changed && $this->has_session() ) {
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

				$this->_changed = false;
				$res            = true;
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $res;
	}

	/**
	 * Destroy session.
	 */
	public function destroy_session() {
		/*$id = $this->get( 'temp_user' );
		if ( $id ) {
			delete_user_meta( $id, '_lp_expiration' );
		}*/

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
	 * Get session id.
	 *
	 * @param $customer_id
	 * @param $timestamp
	 *
	 * @return bool
	 */
	public function update_session_timestamp( $customer_id, $timestamp ): bool {
		global $wpdb;
		$res = true;

		try {
			$wpdb->update(
				LP_Sessions_DB::getInstance()->tb_lp_sessions,
				[ 'session_expiry' => $timestamp ],
				[ 'session_key' => $customer_id ],
				[ '%d' ]
			);
			// Clear cache.
			LP_Session_Cache::instance()->clear( $customer_id );
		} catch ( Throwable $e ) {
			$res = false;
			error_log( $e->getMessage() );
		}

		return $res;
	}

	/**
	 * Remove a value from session by key.
	 *
	 * @param string $key
	 * @param bool   $force_change
	 */
	public function remove( $key, $force_change = false ) {
		if ( ! array_key_exists( $key, $this->_data ) ) {
			return;
		}
		unset( $this->_data[ $key ] );
		$this->_changed = true;
		if ( $force_change ) {
			$this->save_data();
		}
	}

	/**
	 * Get session value.
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed|null
	 */
	public function get( $key, $default = null ) {
		$key = sanitize_key( $key );

		if ( empty( $this->_data ) ) {
			$this->_data = $this->get_session_data();
		}

		return isset( $this->_data[ $key ] ) ? LP_Helper::maybe_unserialize( $this->_data[ $key ] ) : $default;
	}

	/**
	 * Set session value.
	 *
	 * @param string $key
	 * @param mixed  $value
	 * @param bool   $force_change
	 */
	public function set( $key, $value, $force_change = false ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[ sanitize_key( $key ) ] = maybe_serialize( $value );
			$this->_changed                      = true;

			if ( $force_change ) {
				$this->save_data();
			}
		}
	}

	/**
	 * Get customer id.
	 */
	public function get_customer_id(): string {
		return (string) $this->_customer_id;
	}

	public function get_session_expiration() {
		return $this->_session_expiration;
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @deprecated 4.2.0
	 */
	public function offsetExists( $offset ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		return array_key_exists( $offset, $this->_data );
	}

	/**
	 * @deprecated 4.2.0
	 */
	public function offsetGet( $offset ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		//return $this->get( $offset );
	}

	/**
	 * @deprecated 4.2.0
	 */
	public function offsetUnset( $offset ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		//$this->remove( $offset );
	}

	/**
	 * @deprecated 4.2.0
	 */
	public function offsetSet( $offset, $value ) {
		_deprecated_function( __METHOD__, '4.2.0' );
		//$this->set( $offset, $value );
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

<?php

/**
 * Class LP_Session_Handler
 *
 * Only set COOKIE for user guest
 */
class LP_Session_Handler {

	/**
	 * @var int $_customer_id
	 */
	public $_customer_id;

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
	private $_cookie;

	/**
	 * @var string session expiration timestamp
	 */
	private $_session_expiration;

	/**
	 * $var bool based on whether a cookie exists
	 * @deprecated 4.1.7.4
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
	 */
	public function __get( $key ) {
		return $this->get( $key );
	}

	/**
	 * __set function.
	 *
	 * @param mixed $key
	 * @param mixed $value
	 * @deprecated 4.1.7.4
	 */
	public function __set( $key, $value ) {
		_deprecated_function( 'LP_Session::__set', '4.1.7.4' );
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
	 * @deprecated 4.1.7.4
	 */
	public function __isset( $key ) {
		_deprecated_function( __METHOD__, '4.1.7.4' );
		//return isset( $this->_data[ sanitize_key( $key ) ] );
	}

	/**
	 * __unset function.
	 *
	 * @param mixed $key
	 * @deprecated 4.1.7.4
	 */
	public function __unset( $key ) {
		_deprecated_function( __METHOD__, '4.1.7.4' );
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
		$this->init();
		$this->init_hooks();
	}

	protected function init_hooks() {
		//add_action( 'learn_press_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'learn_press_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
		//add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
		//add_action( 'wp', array( $this, 'schedule_event' ) );
		//add_action( $this->schedule_id, array( $this, 'cleanup_sessions' ), 10 );
		add_action( 'wp_login', [$this, 'set_cookie_session_for_user'], 10, 2 );
	}

	public function set_cookie_session_for_user($user_name, $user) {
		wp_set_current_user($user->ID);
		$user_id = get_current_user_id();

		/**
		 * Delete session of user guest before.
		 * Set again cookie for user.
		 */
		if ( $user_id ) {
			$customer_id = $this->get_customer_id();
			$user_before = learn_press_get_user($customer_id);
			if ( $user_before->is_guest() ) {
				learn_press_remove_cookie($this->_cookie);
				$this->delete_session($customer_id);
			}

			$this->_customer_id = $user_id;
			//$this->set_customer_session_cookie();
		}
	}

	protected function init() {
		global $wpdb;
		$this->_table  = $wpdb->prefix . 'learnpress_sessions';
		$this->_cookie = '_learn_press_session_' . COOKIEHASH;
		$cookie = $this->get_session_cookie();
		// If cookie exists, set data from cookie for guest
		if ( $cookie ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			//$this->_has_cookie         = true;
			if ( time() > $this->_session_expiration - HOUR_IN_SECONDS ) {
				$this->set_session_expiration( 2 * DAY_IN_SECONDS );
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}
		} elseif ( ! is_user_logged_in() ) { // Generate data and set cookie for guest
			$this->set_session_expiration( 2 * DAY_IN_SECONDS );
			$this->_customer_id = $this->generate_guest_id();
			$this->set_customer_session_cookie();
		} else { // Set data for user logged.
			$this->set_session_expiration( 6 * DAY_IN_SECONDS );
			$this->_customer_id = get_current_user_id();
		}

		$this->_data = $this->get_session_data();
	}

	/*public function schedule_event() {
		if ( ! wp_next_scheduled( $this->schedule_id ) ) {
			wp_schedule_event( time(), 'hourly', $this->schedule_id );
		}
	}*/

	/**
	 * Set cookie for user guest.
	 *
	 * @return void
	 */
	public function set_customer_session_cookie() {
		// Set/renew our cookie
		$to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
		$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
		$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $cookie_hash;
		//$this->_has_cookie = true;

		// Set the cookie
		if ( ! isset( $_COOKIE[ $this->_cookie ] ) || $_COOKIE[ $this->_cookie ] !== $cookie_value ) {
			learn_press_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration,  is_ssl(), true );
		}
	}

	/**
	 * @deprecated 4.1.7.4
	 */
	public function has_cookie() {
		_deprecated_function( __METHOD__, '4.1.7.4');
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

	public function set_session_expiration( int $duration = 0 ) {
		$this->_session_expiration = time() + $duration;
	}

	/**
	 * Generate string customer id for guest
	 *
	 * @return string
	 */
	public function generate_guest_id(): string {
		require_once ABSPATH . 'wp-includes/class-phpass.php';
		$hasher = new PasswordHash( 12, false );

		return md5( $hasher->get_random_bytes( 32 ) );
	}

	public function get_session_cookie() {
		if ( empty( $_COOKIE[ $this->_cookie ] ) || ! is_string( $_COOKIE[ $this->_cookie ] ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $cookie_hash );
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

		return (array) $this->get_session( $customer_id, array() );
	}

	public function get_cache_prefix( $group = LP_SESSION_CACHE_GROUP ) {
		$prefix = LP_Object_Cache::get( 'learn_press_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			LP_Object_Cache::set( 'learn_press_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'learn_press_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @param string $group
	 */
	public function incr_cache_prefix( $group ) {
		wp_cache_incr( 'learn_press_' . $group . '_cache_prefix', 1, $group );
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
				$filter = new LP_Session_filter();
				$filter->collection = $lp_session_db->tb_lp_sessions;
				$filter->field_count = 'session_id';
				$filter->limit = 1;
				$filter->where[] = $lp_session_db->wpdb->prepare("AND session_key = %s", $this->_customer_id);
				$get = $lp_session_db->execute($filter);

				$data = [
					'session_key'    => (string) $this->_customer_id,
					'session_value'  => maybe_serialize( $this->_data ),
					'session_expiry' => $this->_session_expiration,
				];
				if ( !empty($get) ) {
					// Update
					$lp_session_db->wpdb->update(
						$lp_session_db->tb_lp_sessions,
						$data,
						[ 'session_key' => $this->_customer_id ],
						[ '%s', '%s', '%d' ],
						[ '%s' ]
					);
				} else {
					// Insert
					$lp_session_db->wpdb->insert(
						$lp_session_db->tb_lp_sessions,
						$data,
						[ '%s', '%s', '%d' ]
					);
				}

				$this->_changed = false;
				$res = true;
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $res;
	}

	public function destroy_session() {
		/*$id = $this->get( 'temp_user' );
		if ( $id ) {
			delete_user_meta( $id, '_lp_expiration' );
		}*/

		// Clear cookie
		learn_press_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'learn_press_session_secure_cookie', false ) );

		//$this->delete_session( $this->_customer_id );

		// Clear cart
		$this->set( 'cart', '' );
		//$this->set( 'temp_user', '' );

		// Clear data
		$this->_data        = array();
		$this->_changed     = false;
		//$this->_customer_id = $this->generate_customer_id();

		$logout_redirect_page_id = LP_Settings::get_option( 'logout_redirect_page_id', false );
		if ( $logout_redirect_page_id ) {

			wp_safe_redirect( get_the_permalink( $logout_redirect_page_id ) );
			die;
		}
	}

	public function cleanup_sessions() {
		global $wpdb;

		if ( ! defined( 'WP_SETUP_CONFIG' ) && ! defined( 'WP_INSTALLING' ) ) {

			// Delete expired sessions
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) ); // phpcs:ignore

			// Invalidate cache
			//$this->incr_cache_prefix( LP_SESSION_CACHE_GROUP );
		}
	}

	/**
	 * Get session on DB.
	 *
	 * @param $customer_id
	 * @param $default
	 *
	 * @return false|mixed|string
	 */
	public function get_session( $customer_id, $default = false ) {
		global $wpdb;

		// Try get it from the cache, it will return false if not present or if object cache not in use
		//$value = LP_Object_Cache::get( $this->get_cache_prefix() . $customer_id, LP_SESSION_CACHE_GROUP );
		$value = false;
		// echo "KEY:" . $this->get_cache_prefix() . $customer_id . "]";

		//if ( false === $value && $wpdb->get_var( "SHOW TABLES LIKE '$this->_table'" ) == $this->_table ) {
			$q     = $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ); // phpcs:ignore
			$value = $wpdb->get_var( $q );

			if ( is_null( $value ) ) {
				$value = $default;
			}

			//wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, LP_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
		//}

		return LP_Helper::maybe_unserialize( $value );
	}

	public function delete_session( $customer_id ) {
		global $wpdb;

		//wp_cache_delete( $this->get_cache_prefix() . $customer_id, LP_SESSION_CACHE_GROUP );

		$wpdb->delete(
			$this->_table,
			array(
				'session_key' => $customer_id,
			)
		);
	}

	public function update_session_timestamp( $customer_id, $timestamp ) {
		global $wpdb;
		$wpdb->update(
			$this->_table,
			array(
				'session_expiry' => $timestamp,
			),
			array(
				'session_key' => $customer_id,
			),
			array(
				'%d',
			)
		);
		// LP_Debug::instance()->add( $customer_id, __FUNCTION__ );
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
		return ( string ) $this->_customer_id;
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
	 * @deprecated 4.1.7.4
	 */
	public function offsetExists( $offset ) {
		_deprecated_function( __METHOD__, '4.1.7.4' );
		return array_key_exists( $offset, $this->_data );
	}

	/**
	 * @deprecated 4.1.7.4
	 */
	public function offsetGet( $offset ) {
		_deprecated_function( __METHOD__, '4.1.7.4' );
		//return $this->get( $offset );
	}

	/**
	 * @deprecated 4.1.7.4
	 */
	public function offsetUnset( $offset ) {
		_deprecated_function( __METHOD__, '4.1.7.4' );
		//$this->remove( $offset );
	}

	/**
	 * @deprecated 4.1.7.4
	 */
	public function offsetSet( $offset, $value ) {
		_deprecated_function( __METHOD__, '4.1.7.4' );
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

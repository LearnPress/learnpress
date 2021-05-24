<?php

/**
 * Class LP_Session_Handler
 */
class LP_Session_Handler implements ArrayAccess {

	/**
	 * @var int $_customer_id
	 */
	protected $_customer_id;

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
	 * $var bool Bool based on whether a cookie exists
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
	 */
	public function __set( $key, $value ) {
		// if ( $key === 'order_awaiting_payment' ) {

		// }
		$this->set( $key, $value );
	}

	/**
	 * __isset function.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function __isset( $key ) {
		return isset( $this->_data[ sanitize_key( $key ) ] );
	}

	/**
	 * __unset function.
	 *
	 * @param mixed $key
	 */
	public function __unset( $key ) {
		if ( isset( $this->_data[ $key ] ) ) {
			unset( $this->_data[ $key ] );
			$this->_changed = true;
		}
	}

	protected $schedule_id = 'learn-press/clear-expired-session';

	/**
	 * LP_Session_Handler constructor.
	 *
	 * @version 3.2.2
	 */
	public function __construct() {
		$this->init();
		$this->init_hooks();
	}

	protected function init_hooks() {
		add_action( 'learn_press_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'learn_press_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );
		//add_action( 'wp', array( $this, 'schedule_event' ) );
		//add_action( $this->schedule_id, array( $this, 'cleanup_sessions' ), 10 );
	}

	protected function init() {
		global $wpdb;
		$this->_cookie = '_learn_press_session_' . COOKIEHASH;
		$this->_table  = $wpdb->prefix . 'learnpress_sessions';

		// Check cookie ...
		if ( ! isset( $_COOKIE ) || sizeof( $_COOKIE ) == 0 ) {
			$this->_has_browser_cookie = false;
		}

		// ...and user-agent to ensure user a viewing in a web browser
		if ( empty( $_SERVER['HTTP_USER_AGENT'] ) || strpos( $_SERVER['HTTP_USER_AGENT'], 'Mozilla' ) === false ) {
			$this->_has_browser_cookie = false;
		}

		$cookie = $this->get_session_cookie();
		if ( $cookie ) {
			$this->_customer_id        = $cookie[0];
			$this->_session_expiration = $cookie[1];
			$this->_has_cookie         = true;
			if ( time() > $this->_session_expiration - HOUR_IN_SECONDS ) {
				$this->set_session_expiration();
				$this->update_session_timestamp( $this->_customer_id, $this->_session_expiration );
			}
		} else {
			$this->set_session_expiration();
			$this->_customer_id = $this->generate_customer_id();

		}
		$this->_data = $this->get_session_data();
	}

	/*public function schedule_event() {
		if ( ! wp_next_scheduled( $this->schedule_id ) ) {
			wp_schedule_event( time(), 'hourly', $this->schedule_id );
		}
	}*/

	public function set_customer_session_cookie( $set ) {
		if ( $set ) {
			// Set/renew our cookie
			$to_hash           = $this->_customer_id . '|' . $this->_session_expiration;
			$cookie_hash       = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );
			$cookie_value      = $this->_customer_id . '||' . $this->_session_expiration . '||' . $cookie_hash;
			$this->_has_cookie = true;

			// Set the cookie
			learn_press_setcookie( $this->_cookie, $cookie_value, $this->_session_expiration, apply_filters( 'learn_press_session_use_secure_cookie', false ) );
		}
	}

	public function has_cookie() {
		return $this->_has_cookie && $this->_has_browser_cookie;
	}

	public function has_session() {
		return isset( $_COOKIE[ $this->_cookie ] ) || $this->_has_cookie || is_user_logged_in();
	}

	public function set_session_expiration() {
		$this->_session_expiration = time() + intval( apply_filters( 'learn_press_session_expiration', 60 * 60 * 48 ) );
	}

	public function generate_customer_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {
			require_once ABSPATH . 'wp-includes/class-phpass.php';
			$hasher = new PasswordHash( 12, false );

			return md5( $hasher->get_random_bytes( 32 ) );
		}
	}

	public function get_session_cookie() {

		if ( empty( $_COOKIE[ $this->_cookie ] ) || ! is_string( $_COOKIE[ $this->_cookie ] ) ) {
			return false;
		}

		list( $customer_id, $session_expiration, $cookie_hash ) = explode( '||', $_COOKIE[ $this->_cookie ] );

		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		// LP_Debug::instance()->add( array(
		// $this->_customer_id,
		// $_COOKIE,
		// $_REQUEST,
		// $_POST,
		// $_GET,
		// $_SERVER
		// ), 'sessions-cookie' );

		if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $cookie_hash );
	}

	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_customer_id, array() ) : array();
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


	public function save_data() {
		// var_dump($this->_changed , $this->has_session() , $this->has_cookie() );
		if ( $this->_changed && $this->has_session() && $this->has_cookie() ) {
			global $wpdb;
			$wpdb->replace(
				$this->_table,
				array(
					'session_key'    => $this->_customer_id,
					'session_value'  => maybe_serialize( $this->_data ),
					'session_expiry' => $this->_session_expiration,
				),
				array(
					'%s',
					'%s',
					'%d',
				)
			);
			// LP_Debug::instance()->add( array(
			// $this->_customer_id,
			// $_COOKIE,
			// $_REQUEST,
			// $_POST,
			// $_GET,
			// $_SERVER
			// ), __FUNCTION__ );

			// Set cache
			LP_Object_Cache::set( $this->get_cache_prefix() . $this->_customer_id, $this->_data, LP_SESSION_CACHE_GROUP, $this->_session_expiration - time() );

			// Mark session clean after saving
			$this->_changed = false;
		}
	}

	public function destroy_session() {
		$id = $this->get( 'temp_user' );
		if ( $id ) {
			delete_user_meta( $id, '_lp_expiration' );
		}

		// Clear cookie
		learn_press_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'learn_press_session_secure_cookie', false ) );

		$this->delete_session( $this->_customer_id );

		// Clear cart
		$this->set( 'cart', '' );
		$this->set( 'temp_user', '' );

		// Clear data
		$this->_data        = array();
		$this->_changed     = false;
		$this->_customer_id = $this->generate_customer_id();

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
			$this->incr_cache_prefix( LP_SESSION_CACHE_GROUP );
		}
	}

	public function get_session( $customer_id, $default = false ) {
		global $wpdb;

		if ( defined( 'WP_SETUP_CONFIG' ) ) {
			return false;
		}

		// Try get it from the cache, it will return false if not present or if object cache not in use
		$value = LP_Object_Cache::get( $this->get_cache_prefix() . $customer_id, LP_SESSION_CACHE_GROUP );
		// echo "KEY:" . $this->get_cache_prefix() . $customer_id . "]";

		if ( false === $value && $wpdb->get_var( "SHOW TABLES LIKE '$this->_table'" ) == $this->_table ) {
			$q     = $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id ); // phpcs:ignore
			$value = $wpdb->get_var( $q );

			if ( is_null( $value ) ) {
				$value = $default;
			}

			wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, LP_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
		}

		return LP_Helper::maybe_unserialize( $value );
	}

	public function delete_session( $customer_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $customer_id, LP_SESSION_CACHE_GROUP );

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

	public function get_customer_id() {
		return $this->_customer_id;
	}

	public function get_session_expiration() {
		return $this->_session_expiration;
	}

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function offsetExists( $offset ) {
		return array_key_exists( $offset, $this->_data );
	}

	public function offsetGet( $offset ) {
		return $this->get( $offset );
	}

	public function offsetUnset( $offset ) {
		$this->remove( $offset );
	}

	public function offsetSet( $offset, $value ) {
		$this->set( $offset, $value );
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

<?php

/**
 * Class LP_Session_Handler
 */
class LP_Session_Handler {

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
		return isset( $this->_data[sanitize_key( $key )] );
	}

	/**
	 * __unset function.
	 *
	 * @param mixed $key
	 */
	public function __unset( $key ) {
		if ( isset( $this->_data[$key] ) ) {
			unset( $this->_data[$key] );
			$this->_changed = true;
		}
	}

	public function __construct() {
		global $wpdb;

		$this->_cookie = 'wp_learn_press_session_' . COOKIEHASH;
		$this->_table  = $wpdb->prefix . 'learnpress_sessions';

		if ( $cookie = $this->get_session_cookie() ) {
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

		add_action( 'learn_press_set_cart_cookies', array( $this, 'set_customer_session_cookie' ), 10 );
		add_action( 'learn_press_cleanup_sessions', array( $this, 'cleanup_sessions' ), 10 );
		add_action( 'shutdown', array( $this, 'save_data' ), 20 );
		add_action( 'wp_logout', array( $this, 'destroy_session' ) );

		if ( !is_user_logged_in() ) {
			//add_filter( 'nonce_user_logged_out', array( $this, 'nonce_user_logged_out' ) );
		}
	}

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

	public function has_session() {
		return isset( $_COOKIE[$this->_cookie] ) || $this->_has_cookie || is_user_logged_in();
	}

	public function set_session_expiration() {
		$this->_session_expiration = time() + intval( apply_filters( 'learn_press_session_expiration', 60 * 60 * 48 ) );
	}

	public function generate_customer_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		} else {
			require_once( ABSPATH . 'wp-includes/class-phpass.php' );
			$hasher = new PasswordHash( 12, false );
			return md5( $hasher->get_random_bytes( 32 ) );
		}
	}

	public function get_session_cookie() {

		if ( empty( $_COOKIE[$this->_cookie] ) || !is_string( $_COOKIE[$this->_cookie] ) ) {


			return false;
		}

		list( $customer_id, $session_expiration, $cookie_hash ) = explode( '||', $_COOKIE[$this->_cookie] );


		$to_hash = $customer_id . '|' . $session_expiration;
		$hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

		if ( empty( $cookie_hash ) || !hash_equals( $hash, $cookie_hash ) ) {
			return false;
		}

		return array( $customer_id, $session_expiration, $cookie_hash );
	}

	public function get_session_data() {
		return $this->has_session() ? (array) $this->get_session( $this->_customer_id, array() ) : array();
	}

	public function get_cache_prefix( $group = LP_SESSION_CACHE_GROUP ) {
		$prefix = wp_cache_get( 'learn_press_' . $group . '_cache_prefix', $group );

		if ( false === $prefix ) {
			$prefix = 1;
			wp_cache_set( 'learn_press_' . $group . '_cache_prefix', $prefix, $group );
		}

		return 'learn_press_cache_' . $prefix . '_';
	}

	/**
	 * Increment group cache prefix (invalidates cache).
	 *
	 * @param  string $group
	 */
	public function incr_cache_prefix( $group ) {
		wp_cache_incr( 'learn_press_' . $group . '_cache_prefix', 1, $group );
	}


	public function save_data() {

		if ( $this->_changed && $this->has_session() ) {
			global $wpdb;

			$wpdb->replace(
				$this->_table,
				array(
					'session_key'    => $this->_customer_id,
					'session_value'  => maybe_serialize( $this->_data ),
					'session_expiry' => $this->_session_expiration
				),
				array(
					'%s',
					'%s',
					'%d'
				)
			);

			// Set cache
			wp_cache_set( $this->get_cache_prefix() . $this->_customer_id, $this->_data, LP_SESSION_CACHE_GROUP, $this->_session_expiration - time() );

			// Mark session clean after saving
			$this->_changed = false;
		}

	}

	public function destroy_session() {
		// Clear cookie
		learn_press_setcookie( $this->_cookie, '', time() - YEAR_IN_SECONDS, apply_filters( 'learn_press_session_secure_cookie', false ) );

		$this->delete_session( $this->_customer_id );

		// Clear cart
		learn_press_session_set( 'cart', '' );

		// Clear data
		$this->_data        = array();
		$this->_changed     = false;
		$this->_customer_id = $this->generate_customer_id();
	}

	public function nonce_user_logged_out( $uid ) {
		return $this->has_session() && $this->_customer_id ? $this->_customer_id : $uid;
	}

	public function cleanup_sessions() {
		global $wpdb;

		if ( !defined( 'WP_SETUP_CONFIG' ) && !defined( 'WP_INSTALLING' ) ) {

			// Delete expired sessions
			$wpdb->query( $wpdb->prepare( "DELETE FROM $this->_table WHERE session_expiry < %d", time() ) );

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
		$value = wp_cache_get( $this->get_cache_prefix() . $customer_id, LP_SESSION_CACHE_GROUP );
		///echo "KEY:" . $this->get_cache_prefix() . $customer_id . "]";
		if ( false === $value ) {
			$q     = $wpdb->prepare( "SELECT session_value FROM $this->_table WHERE session_key = %s", $customer_id );
			$value = $wpdb->get_var( $q );
			if ( is_null( $value ) ) {
				$value = $default;
			}

			wp_cache_add( $this->get_cache_prefix() . $customer_id, $value, LP_SESSION_CACHE_GROUP, $this->_session_expiration - time() );
		}

		return maybe_unserialize( $value );
	}

	public function delete_session( $customer_id ) {
		global $wpdb;

		wp_cache_delete( $this->get_cache_prefix() . $customer_id, LP_SESSION_CACHE_GROUP );

		$wpdb->delete(
			$this->_table,
			array(
				'session_key' => $customer_id
			)
		);
	}

	public function update_session_timestamp( $customer_id, $timestamp ) {
		global $wpdb;

		$wpdb->update(
			$this->_table,
			array(
				'session_expiry' => $timestamp
			),
			array(
				'session_key' => $customer_id
			),
			array(
				'%d'
			)
		);
	}

	public function get( $key, $default = null ) {
		$key = sanitize_key( $key );
		return isset( $this->_data[$key] ) ? maybe_unserialize( $this->_data[$key] ) : $default;
	}

	public function set( $key, $value ) {
		if ( $value !== $this->get( $key ) ) {
			$this->_data[sanitize_key( $key )] = maybe_serialize( $value );
			$this->_changed                    = true;
		}
	}

	public function get_customer_id() {
		return $this->_customer_id;
	}

	public static function instance() {
		if ( !self::$_instance ) {
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
	return LP_Session_Handler::instance()->set( $key, $value );
}

function learn_press_session_remove( $key ) {

}
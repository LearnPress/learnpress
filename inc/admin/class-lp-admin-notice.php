<?php
/**
 * Manage the admin notices and display them in admin
 *
 * @package    LearnPress
 * @author     ThimPress
 * @version    1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LP_Admin_Notice
 */
class LP_Admin_Notice {
	/**
	 * Store all notices which added anywhere before show
	 *
	 * @var array
	 */
	protected static $_notices = array();

	/**
	 * @since 3.2.6
	 *
	 * @var LP_Admin_Notice
	 */
	protected static $instance = false;

	/**
	 * List of notices.
	 *
	 * @since 3.2.6
	 *
	 * @var array
	 */
	protected $notices = array();

	/**
	 * Option key for storing notices.
	 *
	 * @since 3.2.6
	 *
	 * @var string
	 */
	protected $option_id = 'lp_admin_notices';

	/**
	 * Check is right plugin base.
	 *
	 * @return bool
	 */
	public static function check_plugin_base(): bool {
		return 0 !== strcmp( LP_PLUGIN_BASENAME, 'learnpress/learnpress.php' );
	}

	/**
	 * Check LP has beta version.
	 *
	 * @return bool|[]
	 */
	public static function check_lp_beta_version() {
		$url    = 'https://learnpress.github.io/learnpress/lp-beta-version.json';
		$config = [];

		try {
			$res = wp_remote_get( $url );
			if ( is_wp_error( $res ) ) {
				throw new Exception( $res->get_error_message() );
			}

			$config = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( json_last_error() ) {
				throw new Exception( json_last_error_msg() );
			}

			$version = $config['version'] ?? 0;
			if ( ! $version ) {
				throw new Exception( 'Version LP beta is invalid!' );
			}

			if ( ! version_compare( $version, LEARNPRESS_VERSION, '>' ) ) {
				return false;
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $config;
	}

	/**
	 * Get description of beta version.
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	public static function get_data_lp_beta( array $config = [] ): array {
		try {
			$keys        = array_keys( $config );
			$title       = $config['title'] ?? '';
			$description = $config['description'] ?? '';
			foreach ( $keys as $key ) {
				$description = str_replace( '[[' . $key . ']]', $config[ $key ], $description );
				$title       = str_replace( '[[' . $key . ']]', $config[ $key ], $title );
			}
		} catch ( Throwable $e ) {
			$title       = '';
			$description = '';
			error_log( $e->getMessage() );
		}

		return [
			'title'       => $title,
			'description' => $description,
		];
	}

	/**
	 * Tests the background handler's connection.
	 *
	 * @since 4.1.7.3.2
	 *
	 * @return bool|WP_Error
	 */
	public static function check_wp_remote() {
		$test_url = add_query_arg( 'lp_test_wp_remote', 1, home_url() );
		$args     = [
			'timeout' => 30,
		];
		$result   = wp_remote_get( $test_url, $args );
		$body     = ! is_wp_error( $result ) ? wp_remote_retrieve_body( $result ) : $result;

		return $body === '[TEST_REMOTE]' ? true : new WP_Error( 'wp_remote_get_err', $body );
	}

	/**
	 * Add new notice to show in admin page.
	 *
	 * @since 3.2.6
	 *
	 * @param string|WP_Error $message
	 * @param string          $type
	 * @param bool            $dismissible
	 * @param string          $id
	 * @param bool            $redirect
	 * @param bool            $override
	 *
	 * @return boolean
	 * @deprecated 4.2.5
	 */
	public function add( $message, $type = 'success', $dismissible = true, $id = '', $redirect = false, $override = false ) {
		_deprecated_function( __METHOD__, '4.2.5' );
		return false;
		if ( ! $id ) {
			$id = uniqid();
		}

		if ( empty( $this->notices[ $id ] ) || $override ) {
			$this->notices[ $id ] = array(
				'message'     => $message,
				'type'        => $type ? $type : 'success',
				'redirect'    => $redirect,
				'dismissible' => $dismissible,
			);

			return true;
		}

		return false;
	}

	/**
	 * Remove one or more notices from queue by ids.
	 *
	 * @since 3.2.6
	 *
	 * @param string|array $notice
	 *
	 * @return array|bool
	 */
	public function remove( $notice ) {
		settype( $notice, 'array' );

		if ( ! $this->notices ) {
			return false;
		}

		foreach ( $notice as $id ) {
			if ( ! empty( $this->notices[ $id ] ) ) {
				unset( $this->notices[ $id ] );
			}
		}

		// Also remove in db
		$redirects = get_option( $this->option_id );
		if ( $redirects ) {
			foreach ( $notice as $id ) {
				if ( ! empty( $redirects[ $id ] ) ) {
					unset( $redirects[ $id ] );
				}
			}

			update_option( $this->option_id, $redirects );
		}

		return $this->notices;
	}

	/**
	 * Clear all notices.
	 *
	 * @since 3.2.6
	 */
	public function clear() {
		$this->notices = array();
		delete_option( $this->option_id );
	}

	/**
	 * Get single instance of this class
	 *
	 * @since 3.2.6
	 *
	 * @return LP_Admin_Notice
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Admin_Notice::instance();

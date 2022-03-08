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
	 * @var string
	 */
	protected $dismissed_option_id = 'lp_admin_dismissed_notices';

	/**
	 * LP_Admin_Notice construct
	 */
	protected function __construct() {
		// add_action( 'init', array( $this, 'dismiss_notice' ) );
		add_action( 'init', array( $this, 'load' ) );
		add_action( 'admin_notices', array( $this, 'show_notices' ), 90 );
	}

	public function load() {
		$notices = get_option( $this->option_id );

		if ( ! $notices ) {
			return false;
		}

		$this->notices = array_merge( $notices, $this->notices );

		delete_option( $this->option_id );

		return true;
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
	 */
	public function add( $message, $type = 'success', $dismissible = true, $id = '', $redirect = false, $override = false ) {
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
	 * @param string|WP_Error $message
	 * @param string          $type
	 * @param bool            $dismissible
	 * @param string          $id
	 * @param bool            $override
	 *
	 * @return bool
	 */
	public function add_redirect( $message, $type = 'success', $dismissible = false, $id = '', $override = false ) {
		return $this->add( $message, $type, $dismissible, $id, true, $override );
	}

	/**
	 * Show all notices.
	 *
	 * @return bool
	 */
	public function show_notices() {
		if ( empty( $this->notices ) ) {
			return false;
		}

		$redirect_notices = array();

		foreach ( $this->notices as $id => $notice ) {
			$notice['message'] = $notice['message'] instanceof WP_Error ? $notice['message']->get_error_message() : $notice['message'];

			if ( $notice['redirect'] ) {
				$notice['redirect']      = false;
				$redirect_notices[ $id ] = $notice;
			} else {
				if ( ! $this->has_dismissed_notice( $id ) ) {
					if ( preg_match( '/.php$/', $notice['message'] ) ) {
						$view = $notice['message'];
					} else {
						$view = 'admin-notice.php';
					}

					learn_press_admin_view( $view, array_merge( $notice, array( 'id' => $id ) ) );
				}
			}
		}

		if ( $redirect_notices ) {
			update_option( $this->option_id, $redirect_notices );
		}

		return true;
	}

	/**
	 * Check if a notice is already added by id.
	 *
	 * @since 3.2.6
	 *
	 * @param string[] $notice
	 *
	 * @return bool
	 */
	public function has_notice( $notice ) {
		settype( $notice, 'array' );

		if ( ! $this->notices ) {
			return false;
		}

		foreach ( $notice as $id ) {
			if ( ! empty( $this->notices[ $id ] ) ) {
				return true;
			}
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
	 * @since 3.2.6
	 */
	/*public function dismiss_notice() {
		$id = LP_Request::get( 'lp-dismiss-notice' );

		if ( ! $id ) {
			return;
		}

		$dismissed = get_option( $this->dismissed_option_id );

		if ( ! $dismissed ) {
			$dismissed = array();
		}

		do_action( 'learn-press/before-dismiss-notice', $id );

		if ( array_search( $id, $dismissed ) === false ) {
			$dismissed[] = $id;
			update_option( $this->dismissed_option_id, $dismissed );
		}

		do_action( 'learn-press/dismissed-notice', $id );

		learn_press_send_json(
			apply_filters(
				'learn-press/dismissed-notice-response',
				array(
					'dismissed' => $id,
				),
				$id
			)
		);
	}*/

	/**
	 * Update option to turn-off a notice.
	 *
	 * @since 3.2.6
	 *
	 * @param string $name
	 * @param string $value
	 * @param int    $expired
	 */
	public function dismiss_notice_2( $name, $value, $expired = 0 ) {
		if ( $expired ) {
			set_transient( 'lp_dismiss_notice' . $name, $value, $expired );
		} else {
			$values = get_option( 'lp_dismiss_notice' );
			if ( ! $values ) {
				$values = array( $name => $value );
			} else {
				$values[ $name ] = $value;
			}

			update_option( 'lp_dismiss_notice', $values );
		}
	}

	/**
	 * Check if a notice has dismissed.
	 *
	 * @since 3.2.6
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has_dismissed_notice( $name ) {

		$dismissed = get_option( $this->dismissed_option_id );

		if ( ! $dismissed ) {
			return false;
		}

		return array_search( $name, $dismissed ) !== false;
	}

	/**
	 * Restore dismissed notices by id.
	 *
	 * @since 3.2.6
	 *
	 * @param string[] $notice
	 *
	 * @return bool
	 */
	public function restore_dismissed_notice( $notice ) {
		$dismissed = get_option( $this->dismissed_option_id );

		if ( ! $dismissed ) {
			return false;
		}

		settype( $notice, 'array' );

		foreach ( $notice as $id ) {
			$at = array_search( $id, $dismissed );

			if ( false !== $at ) {
				unset( $dismissed[ $at ] );
			}
		}

		update_option( $this->dismissed_option_id, $dismissed );

		return true;
	}

	/**
	 * Clear all notices has dismissed.
	 *
	 * @since 3.2.6
	 */
	public function clear_dismissed_notice() {
		delete_option( $this->dismissed_option_id );
	}

	/**
	 * Remove a notice has been dismissed.
	 *
	 * @since 3.2.6
	 *
	 * @param string|array $name    - Optional. NULL will remove all notices.
	 * @param bool         $expired - Optional. TRUE if dismiss notice as transient (in case $name passed).
	 *
	 * @return bool
	 */
	public function remove_dismissed_notice( $name = '' ) {
		if ( ! $name ) {
			global $wpdb;

			$query = $wpdb->prepare( "SELECT SUBSTR(option_name, 12) FROM {$wpdb->options} WHERE option_name LIKE %s", '%' . $wpdb->esc_like( '_transient_lp_dismiss_notice' ) . '%' );

			$all_notices = $wpdb->get_col( $query );

			if ( $all_notices ) {
				foreach ( $all_notices as $notice ) {
					delete_transient( $notice );
				}
			}

			delete_option( 'lp_dismiss_notice' );

			return true;

		} elseif ( is_array( $name ) ) {
			foreach ( $name as $notice ) {
				$this->remove_dismissed_notice( $notice );
			}

			return true;
		}

		delete_transient( 'lp_dismiss_notice' . $name );

		$values = get_option( 'lp_dismiss_notice' );

		if ( ! $values ) {
			return false;
		} else {
			if ( array_key_exists( $name, $values ) ) {
				unset( $values[ $name ] );

				update_option( 'lp_dismiss_notice', $values );

				return $values;
			}
		}

		return false;
	}

	/**
	 * @deprecated
	 */
	public function dismiss_notice_deprecated() {
		$notice = learn_press_get_request( 'lp-hide-notice' );

		if ( ! $notice ) {
			return;
		}

		$transient = learn_press_get_request( 't' );

		if ( $transient ) {
			set_transient( 'lp-hide-notice-' . $notice, 'yes', $transient );
		} else {
			learn_press_update_user_option( 'hide-notice-' . $notice, 'yes' );
		}

		$redirect = apply_filters( 'learn_press_hide_notice_redirect', remove_query_arg( 'lp-hide-notice' ) );
		if ( $redirect ) {
			wp_redirect( untrailingslashit( $redirect ) );
			exit();
		}
	}

	/**
	 * Add new notice to queue
	 *
	 * @deprecated
	 *
	 * @param string $message The message want to display
	 * @param string $type    The class name of WP message type updated|update-nag|error
	 * @param string $id      Custom id for html element's ID
	 * @param        bool
	 */
	public static function add_deprecated( $message, $type = 'success', $id = '', $redirect = false ) {
		if ( $redirect ) {
			$notices = get_transient( 'learn_press_redirect_notices' );
			if ( empty( $notices ) ) {
				$notices = array();
			}
			$notices[] = array(
				'type'    => $type,
				'message' => $message,
				'id'      => $id,
			);
			set_transient( 'learn_press_redirect_notices', $notices );
		} else {
			self::$_notices[] = array(
				'type'    => $type,
				'message' => $message,
				'id'      => $id,
			);
		}
	}

	public static function add_redirect_reprecated( $message, $type = 'updated', $id = '' ) {
		self::add( $message, $type, $id, true );
	}

	/**
	 * Show all notices has registered
	 *
	 * @deprecated
	 */
	public static function show_notices_deprecated() {
		if ( self::$_notices ) {
			foreach ( self::$_notices as $notice ) {
				if ( empty( $notice ) ) {
					continue;
				}
				learn_press_admin_view( 'admin-notice.php', $notice );
			}
		}

		$notices = get_transient( 'learn_press_redirect_notices' );
		if ( $notices ) {
			foreach ( $notices as $notice ) {
				if ( empty( $notice ) ) {
					continue;
				}

				learn_press_admin_view( 'admin-notice.php', $notice );
			}

			delete_transient( 'learn_press_redirect_notices' );
		}
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

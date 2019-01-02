<?php
/**
 * Manage the admin notices and display them in admin
 *
 * @package    LearnPress
 * @author     ThimPress
 * @version    1.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Admin_Notice
 */
class LP_Admin_Notice {
	/**
	 * Store all notices which added anywhere before show
	 * @var array
	 */
	protected static $_notices = array();

	/**
	 * @since 3.x.x
	 *
	 * @var LP_Admin_Notice
	 */
	protected static $instance = false;

	/**
	 * LP_Admin_Notice construct
	 */
	protected function __construct() {
		add_action( 'init', array( $this, 'dismiss_notice' ) );
		add_action( 'admin_notices', array( __CLASS__, 'show_notices' ), 100000 );
	}

	/**
	 * Update option to turn-off a notice.
	 *
	 * @since 3.x.x
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
	 * @since 3.x.x
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public function has_dismissed_notice( $name ) {
		if ( $transient = get_transient( 'lp_dismiss_notice' . $name ) ) {
			return $transient;
		}

		$values = get_option( 'lp_dismiss_notice' );
		if ( ! $values ) {
			return false;
		}

		return isset( $values[ $name ] ) ? $values[ $name ] : false;
	}

	/**
	 * Remove a notice has been dismissed.
	 *
	 * @since 3.x.x
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

			if ( $all_notices = $wpdb->get_col( $query ) ) {
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

	public function dismiss_notice() {

		$notice = learn_press_get_request( 'lp-hide-notice' );
		if ( ! $notice ) {
			return;
		}
		if ( $transient = learn_press_get_request( 't' ) ) {
			set_transient( 'lp-hide-notice-' . $notice, 'yes', $transient );
		} else {
			learn_press_update_user_option( 'hide-notice-' . $notice, 'yes' );
		}

		if ( $redirect = apply_filters( 'learn_press_hide_notice_redirect', remove_query_arg( 'lp-hide-notice' ) ) ) {
			wp_redirect( untrailingslashit( $redirect ) );
			exit();
		}
	}

	/**
	 * Add new notice to queue
	 *
	 * @param string $message The message want to display
	 * @param string $type    The class name of WP message type updated|update-nag|error
	 * @param string $id      Custom id for html element's ID
	 * @param        bool
	 */
	public static function add( $message, $type = 'success', $id = '', $redirect = false ) {
		if ( $redirect ) {
			$notices = get_transient( 'learn_press_redirect_notices' );
			if ( empty( $notices ) ) {
				$notices = array();
			}
			$notices[] = array(
				'type'    => $type,
				'message' => $message,
				'id'      => $id
			);
			set_transient( 'learn_press_redirect_notices', $notices );
		} else {
			self::$_notices[] = array(
				'type'    => $type,
				'message' => $message,
				'id'      => $id
			);
		}
	}

	public static function add_redirect( $message, $type = 'updated', $id = '' ) {
		self::add( $message, $type, $id, true );
	}

	/**
	 * Show all notices has registered
	 */
	public static function show_notices() {
		if ( self::$_notices ) {
			foreach ( self::$_notices as $notice ) {
				if ( empty( $notice ) ) {
					continue;
				}
				learn_press_admin_view( 'admin-notice.php', $notice );
			}
		}
		if ( $notices = get_transient( 'learn_press_redirect_notices' ) ) {
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
	 * @since 3.x.x
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
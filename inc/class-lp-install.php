<?php
/**
 * Install and update functions.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

use LP\Helpers\Config;

defined( 'ABSPATH' ) || exit();

if ( ! function_exists( 'LP_Install' ) ) {

	/**
	 * Class LP_Install
	 */
	class LP_Install {
		protected static $instance;
		/**
		 * Default pages used by LP
		 *
		 * @var array
		 */
		private static $_pages = array( 'checkout', 'profile', 'courses', 'become_a_teacher', 'term_conditions' );

		protected function __construct() {
		}

		/**
		 * Get instance
		 *
		 * @return LP_Install
		 */
		public static function instance(): self {
			if ( ! self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Init action.
		 */
		public static function init() {
			// add_action( 'learn-press/activate', array( __CLASS__, 'on_activate' ) );
			// add_action( 'admin_init', array( __CLASS__, 'do_install' ) );
			// add_action( 'admin_init', array( __CLASS__, 'subscription_button' ) );
		}

		/**
		 * Do something after LP is activated.
		 *
		 * @since 4.0.0
		 */
		public function on_activate() {
			// update_option( 'learn_press_status', 'activated' );

			$this->create_tables();

			if ( ! self::tables_install_done() ) {
				return;
			}

			// Check if LP install before has db version
			if ( ! get_option( LP_KEY_DB_VERSION, false ) ) {
				// Save database version of LP
				update_option( LP_KEY_DB_VERSION, LearnPress::instance()->db_version );
			}

			update_option( 'learnpress_version', LearnPress::instance()->version );

			// Create pages default.
			self::create_pages();

			// Set permalink is "Post name".
			if ( ! get_option( 'permalink_structure' ) ) {
				update_option( 'permalink_structure', '/%postname%/' );
				// flush_rewrite_rules();
			}

			// Force option users_can_register to ON.
			if ( ! get_option( 'users_can_register' ) ) {
				update_option( 'users_can_register', 1 );
			}
		}

		/**
		 * Check to run installer in the first-time LP installed.
		 *
		 * @since 3.x.x
		 */
		public static function do_install() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$status = get_option( 'learn_press_status' );

			switch ( $status ) {
				case 'activated':
					self::install();
					update_option( 'learn_press_status', 'installed' );
			}
		}

		public static function subscription_button() {
			// Only administrator of the site can do this
			if ( ! current_user_can( 'administrator' ) ) {
				return;
			}

			LP_Admin_Notice::instance()->add( 'tools/subscription-button.php', '', true, 'newsletter-button' );
		}

		/**
		 * Run installation after LearnPress is activated.
		 *
		 * @depecated 4.1.6.4
		 */
		public static function install() {
			_deprecated_function( __FUNCTION__, '4.1.6.4' );
			/*self::create_options();
			self::_create_pages();
			self::_create_cron_jobs();
			self::_delete_transients();
			//self::_create_log_path();
			self::_clear_backgrounds();

			$current_version    = get_option( 'learnpress_version', null );
			$current_db_version = get_option( 'learnpress_db_version', null );

			// Fresh installation .
			if ( is_null( $current_db_version ) ) {
				update_option( 'learn_press_install', 'yes' );
				set_transient( 'lp_activation_redirect', 'yes', 60 );
			}

			// Force to show notice outdated template .
			learn_press_delete_user_option( 'hide-notice-template-files' );

			LP_Admin_Notice::instance()->remove_dismissed_notice( array( 'outdated-template' ) );

			//if ( ! get_option( 'learnpress_db_version' ) ) {
			update_option( 'learnpress_db_version', (int) LEARNPRESS_VERSION );
			//}*/
		}

		protected static function _clear_backgrounds() {
			global $wpdb;

			$query = $wpdb->prepare(
				"
				DELETE FROM {$wpdb->options} WHERE option_name LIKE %s
			",
				$wpdb->esc_like( 'lp_schedule_items_batch_' ) . '%'
			);
			$wpdb->query( $query );

			$query = $wpdb->prepare(
				"
				DELETE FROM {$wpdb->options} WHERE option_name LIKE %s
			",
				$wpdb->esc_like( 'lp_installer_batch_' ) . '%'
			);
			$wpdb->query( $query );
		}

		/**
		 * Update default options for LP
		 */
		public static function create_options() {
			$settings_classes = array(
				'LP_Settings_General'  => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-general.php',
				'LP_Settings_Courses'  => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-courses.php',
				'LP_Settings_Profile'  => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-profile.php',
				'LP_Settings_Payments' => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-payments.php',
			);

			ob_start();
			$str = array();

			foreach ( $settings_classes as $c => $class ) {
				if ( ! is_object( $class ) ) {
					$class = @new $c();
				}

				$options = array();

				if ( is_callable( array( $class, 'get_settings' ) ) ) {
					$options = $class->get_settings( '', '' );
				}

				if ( ! $options ) {
					continue;
				}

				foreach ( $options as $option ) {
					if ( ! isset( $option['id'] ) ) {
						continue;
					}

					$default_value = '';

					if ( array_key_exists( 'default', $option ) ) {
						$default_value = $option['default'];
					} elseif ( array_key_exists( 'std', $option ) ) {
						$default_value = $option['std'];
					}

					if ( $default_value === '' || $default_value === null ) {
						continue;
					}

					if ( ! preg_match( '~^learn_press_~', $option['id'] ) ) {
						$option_name = 'learn_press_' . $option['id'];
					} else {
						$option_name = $option['id'];
					}

					// Don't update existing option
					if ( false !== get_option( $option_name ) ) {
						continue;
					}

					// If option name doesn't like an array then update directly.
					if ( ! preg_match( '/\[|\]/', $option_name ) ) {
						update_option( $option_name, $default_value, 'yes' );
						continue;
					}

					// Concat option as query string to parse it later
					$value = maybe_serialize( $default_value );
					$str[] = "{$option_name}=$value";
				}
			}

			// Parse query string to get options
			if ( $str ) {
				$str     = join( '&', $str );
				$options = array();
				parse_str( $str, $options );

				if ( $options ) {
					foreach ( $options as $name => $value ) {
						$value = LP_Helper::maybe_unserialize( $value );
						update_option( $name, $value, 'yes' );
					}
				}
			}

			ob_get_clean();
		}

		/**
		 * Create tables required for LP
		 */
		private function create_tables() {
			try {
				$tables = Config::instance()->get( 'tables-v4', 'table' );
				foreach ( $tables as $table ) {
					LP_Database::getInstance()->wpdb->query( $table );
				}

				update_option( 'learn_press_check_tables', 'yes' );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Delete transients
		 */
		private static function _delete_transients() {
			global $wpdb;
			$sql = "
				DELETE a, b FROM $wpdb->options a, $wpdb->options b
				WHERE a.option_name LIKE %s
				AND a.option_name NOT LIKE %s
				AND b.option_name = CONCAT( '_transient_timeout_', SUBSTRING( a.option_name, 12 ) )
				AND b.option_value < %d
			";
			$wpdb->query(
				$wpdb->prepare(
					$sql,
					$wpdb->esc_like( '_transient_' ) . '%',
					$wpdb->esc_like( '_transient_timeout_' ) . '%',
					time()
				)
			);
		}

		/**
		 * Remove learnpress page if total of learn page > 10
		 *
		 * @return mixed
		 * @depecated 4.1.6.4
		 */
		/*public static function _remove_pages() {
			global $wpdb;

			// Get all pages
			$sql = $wpdb->prepare(
				"
				SELECT *
				FROM {$wpdb->posts} p
				INNER JOIN  {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key= %s AND p.post_type = %s
				",
				'_learn_press_page',
				'page'
			);

			$page_ids = $wpdb->get_col( $sql );

			if ( sizeof( $page_ids ) < 10 ) {
				return $page_ids;
			}

			// Delete pages
			$query = $wpdb->prepare(
				"
				DELETE FROM p, pm
				USING {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND p.post_type = %s
				WHERE p.post_status = %s
				AND p.ID IN(" . implode( ',', $page_ids ) . ')
			',
				'page',
				'publish'
			);

			$wpdb->query( $query );

			$pages = self::$_pages;
			foreach ( $pages as $page ) {
				delete_option( "learn_press_{$page}_page_id" );
			}

			return array();
		}*/

		/**
		 * Create default pages for LP
		 */
		/*public static function _create_pages() {
			global $wpdb;

			// Just delete duplicated pages
			$created_page = self::_remove_pages();
			$pages        = self::$_pages;

			foreach ( $pages as $page ) {
				// If page already existed
				$page_id = get_option( "learn_press_{$page}_page_id" );

				if ( $page_id && get_post_type( $page_id ) == 'page' && get_post_status( $page_id ) == 'publish' ) {
					continue;
				}

				$page_id = self::_search_page( $page, $pages );

				if ( ! $page_id ) {
					// Check if page has already existed
					switch ( $page ) {
						case 'courses':
							$_lpr_settings_pages = (array) get_option( '_lpr_settings_pages' );

							if ( ! empty( $_lpr_settings_pages['general'] ) ) {
								if ( ! empty( $_lpr_settings_pages['general']['courses_page_id'] ) ) {
									$page_id = $_lpr_settings_pages['general']['courses_page_id'];
								}
							}
							break;
						case 'profile':
							$_lpr_settings_general = (array) get_option( '_lpr_settings_general' );
							if ( ! empty( $_lpr_settings_general['set_page'] ) && $_lpr_settings_general['set_page'] == 'lpr_profile' ) {
								$page_id = $wpdb->get_var(
									$wpdb->prepare(
										"
									SELECT ID
										FROM $wpdb->posts p
										INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %d
								",
										'_lpr_is_profile_page',
										1
									)
								);
							}
							break;
					}

					if ( ! $page_id ) {
						if ( $page === 'courses' ) {
							$page_title = 'All Courses';
						} else {
							$page_title = ucwords( str_replace( '_', ' ', $page ) );
						}

						if ( $page === 'profile' ) {
							$page_content = '<!-- wp:shortcode -->[' . apply_filters( 'learn-press/shortcode/profile/tag', 'learn_press_profile' ) . ']<!-- /wp:shortcode -->';
						} else {
							$page_content = '';
						}

						$page_slug = 'lp-' . str_replace( '_', '-', $page );

						$inserted = wp_insert_post(
							array(
								'post_title'     => $page_title,
								'post_name'      => $page_slug,
								'post_status'    => 'publish',
								'post_type'      => 'page',
								'comment_status' => 'closed',
								'post_content'   => isset( $page_content ) ? $page_content : '',
								'post_author'    => get_current_user_id(),
							)
						);
						if ( $inserted ) {
							$page_id = $inserted;
						}
					}
				}
				if ( $page_id ) {
					update_option( "learn_press_{$page}_page_id", $page_id );
					update_post_meta( $page_id, '_learn_press_page', $page );
				}
			}

			flush_rewrite_rules();
		}*/

		/**
		 * Create default pages for LP
		 */
		public static function create_pages() {
			$pages = self::$_pages;

			try {
				foreach ( $pages as $page ) {
					// Check if page has already existed
					$page_id = get_option( "learn_press_{$page}_page_id", false );

					if ( $page_id && get_post_type( $page_id ) == 'page' && get_post_status( $page_id ) == 'publish' ) {
						continue;
					}

					//$page_id = self::_search_page( $page, $pages );

					if ( $page === 'courses' ) {
						$page_title = 'All Courses';
						$page_slug  = $page;
					} else {
						$page_title = ucwords( str_replace( '_', ' ', $page ) );
						$page_slug  = 'lp-' . str_replace( '_', '-', $page );
					}

					if ( $page === 'profile' ) {
						$page_content = '<!-- wp:shortcode -->[' . apply_filters( 'learn-press/shortcode/profile/tag', 'learn_press_profile' ) . ']<!-- /wp:shortcode -->';
					} else {
						$page_content = '';
					}

					$page_id = wp_insert_post(
						array(
							'post_title'     => $page_title,
							'post_name'      => $page_slug,
							'post_status'    => 'publish',
							'post_type'      => 'page',
							'comment_status' => 'closed',
							'post_content'   => $page_content ?? '',
							'post_author'    => get_current_user_id(),
						)
					);

					if ( ! $page_id instanceof WP_Error ) {
						update_option( "learn_press_{$page}_page_id", $page_id );
					}
				}

				flush_rewrite_rules();
			} catch ( Exception $ex ) {
				error_log( $ex->getMessage() );
			}
		}

		/**
		 * Search LP page to see if they are already created.
		 *
		 * @param $type
		 * @param $types
		 *
		 * @return int|mixed
		 * @depecated 4.1.6.4
		 */
		/*protected static function _search_page( $type, $types ) {
			static $pages = array();

			if ( empty( $pages[ $type ] ) ) {
				global $wpdb;
				$in_types = array_fill( 0, sizeof( $types ), '%s' );
				$args     = array( '_learn_press_page' );
				$args     = array_merge( $args, $types );
				$args[]   = 'publish';
				$query    = $wpdb->prepare(
					"
					SELECT ID, pm.meta_value as type
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value IN(" . join( ',',
						$in_types ) . ')
					WHERE p.post_status = %s
				',
					$args
				);

				if ( $rows = $wpdb->get_results( $query ) ) {
					foreach ( $rows as $row ) {
						$pages[ $row->type ] = $row->ID;
					}
				}
			}

			$page_id = ! empty( $pages[ $type ] ) ? $pages[ $type ] : 0;

			return $page_id;
		}*/

		/**
		 * @depecated 4.1.6.4
		 */
		private static function _create_cron_jobs() {
			_deprecated_function( __FUNCTION__, '4.1.6.4' );
			wp_clear_scheduled_hook( 'learn_press_cleanup_sessions' );
			wp_schedule_event(
				time(),
				apply_filters( 'learn_press_cleanup_session_recurrence', 'twicedaily' ),
				'learn_press_cleanup_sessions'
			);
		}

		/**
		 * Check installed all tables required for LearnPress.
		 *
		 * @return bool
		 */
		public function tables_install_done(): bool {
			return 'yes' === get_option( 'learn_press_check_tables', 'no' );
		}
	}
}

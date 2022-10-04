<?php
/**
 * Install and update functions.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
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
		 * Do something after LP is activated.
		 *
		 * @since 4.0.0
		 */
		public function on_activate() {
			$this->create_tables();

			if ( ! self::tables_install_done() ) {
				return;
			}

			// Check if LP install before has db version
			if ( ! get_option( LP_KEY_DB_VERSION, false ) ) {
				// Save database version of LP
				update_option( LP_KEY_DB_VERSION, LearnPress::instance()->db_version );
			}

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
		 * Check installed all tables required for LearnPress.
		 *
		 * @return bool
		 */
		public function tables_install_done(): bool {
			$install_done = get_option( 'learn_press_check_tables', 'no' );
			if ( is_multisite() && 'yes' !== $install_done ) {
				$this->create_tables();
			}

			return $install_done;
		}
	}
}

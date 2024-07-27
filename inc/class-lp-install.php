<?php
/**
 * Install and update functions.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
 */

use LearnPress\Helpers\Config;

defined( 'ABSPATH' ) || exit();

if ( ! function_exists( 'LP_Install' ) ) {

	/**
	 * Class LP_Install
	 */
	class LP_Install {
		protected static $instance;
		protected $lp_db;
		/**
		 * Default pages used by LP
		 *
		 * @var array
		 */
		private static $_pages = array(
			'checkout',
			'profile',
			'courses',
			'instructors',
			'single_instructor',
			'become_a_teacher',
			'term_conditions',
		);

		protected function __construct() {
			$this->lp_db = LP_Database::getInstance();
			// Only run on backend.
			if ( ! is_admin() ) {
				return;
			}
			$time_limit_default = ini_get('max_execution_time');
			@set_time_limit( 0 );
			// From LP v4.2.2 temporary run create table thim_cache.
			// After a long time, will remove this code. Only run create table when activate plugin LP.
			if ( ! LP_Settings::is_created_tb_thim_cache() ) {
				$this->create_table_thim_cache();
			}

			// From LP v4.2.6.9 temporary run create table learnpress_files.
			// After a long time, will remove this code. Only run create table when activate plugin LP.
			if ( ! LP_Settings::is_created_tb_courses() ) {
				$this->create_table_courses();
			}

			// From LP v4.2.6.6 temporary run create table learnpress_files.
			// After a long time, will remove this code. Only run create table when activate plugin LP.
			if ( ! LP_Settings::is_created_tb_material_files() ) {
				$this->create_table_learnpress_files();
			}
			@set_time_limit( $time_limit_default );
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
			/*if ( ! get_option( 'users_can_register' ) ) {
				update_option( 'users_can_register', 1 );
			}*/
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

				if ( ! LP_Settings::is_created_tb_thim_cache() ) {
					$this->create_table_thim_cache();
				}

				if ( ! LP_Settings::is_created_tb_material_files() ) {
					$this->create_table_learnpress_files();
				}

				update_option( 'learn_press_check_tables', 'yes' );
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Create table thim_cache
		 *
		 * @since 4.2.2
		 */
		private function create_table_thim_cache() {
			global $wpdb;

			try {
				$collation = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

				$sql = "CREATE TABLE IF NOT EXISTS {$this->lp_db->tb_thim_cache} (
					key_cache VARCHAR (191) NOT NULL UNIQUE,
					value LONGTEXT NOT NULL,
					expiration VARCHAR (191),
					PRIMARY KEY (key_cache)
				) $collation";

				$rs = $wpdb->query( $sql );

				if ( $rs ) {
					update_option( 'thim_cache_tb_created', 'yes' );
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Create table learnpress_courses
		 *
		 * @since 4.2.6.9
		 */
		public function create_table_courses() {
			global $wpdb;

			try {
				$collation = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

				$sql = "CREATE TABLE IF NOT EXISTS {$this->lp_db->tb_lp_courses} (
					ID bigint(20) unsigned NOT NULL,
					json LONGTEXT NOT NULL,
					price_to_sort FLOAT,
					is_sale int(1) default 0,
					post_author bigint unsigned,
					post_date_gmt datetime,
					post_content LONGTEXT,
					post_title text not null,
					post_status varchar(20) default 'publish' not null,
					post_name varchar(200) default '',
					menu_order int default 0,
					lang varchar(20),
					PRIMARY KEY (ID),
					KEY post_title (post_title(191)),
					KEY post_status (post_status),
					KEY post_name (post_name),
					KEY id_status (ID, post_status)
				) $collation";

				$rs = $wpdb->query( $sql );

				if ( $rs ) {
					update_option( 'tb_learnpress_courses', 'yes' );
				}
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() );
			}
		}

		/**
		 * Create table learnpress_material_files
		 *
		 * @since 4.2.6.6
		 */
		private function create_table_learnpress_files() {
			global $wpdb;

			try {
				$collation = $wpdb->has_cap( 'collation' ) ? $wpdb->get_charset_collate() : 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';

				$sql = "CREATE TABLE IF NOT EXISTS {$this->lp_db->tb_lp_files} (
					file_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					file_name varchar(191) NOT NULL DEFAULT '',
					file_type varchar(100) NOT NULL DEFAULT '',
					item_id bigint(20) unsigned NOT NULL DEFAULT '0',
					item_type varchar(100) NOT NULL DEFAULT '',
					method varchar(10) NOT NULL DEFAULT 'upload' CHECK ( method IN ( 'upload', 'external' ) ),
					file_path varchar(255) NOT NULL DEFAULT '',
					orders int(4) NOT NULL DEFAULT '0',
					created_at datetime NULL DEFAULT NULL,
					PRIMARY KEY (file_id),
					KEY file_name (file_name),
					KEY item_id (item_id),
					KEY item_type (item_type)
				) $collation";

				$rs = $wpdb->query( $sql );

				if ( $rs ) {
					update_option( 'table_learnpress_files_created', 'yes' );
				}
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

					if ( $page_id && get_post_type( $page_id ) === 'page' && get_post_status( $page_id ) == 'publish' ) {
						continue;
					}

					if ( $page === 'courses' ) {
						$page_title = 'All Courses';
						$page_slug  = $page;
					} elseif ( 'single_instructor' === $page ) {
						$page_title = 'Instructor';
						$page_slug  = 'instructor';
					} elseif ( 'instructors' === $page ) {
						$page_title = 'Instructors';
						$page_slug  = $page;
					} else {
						$page_title = ucwords( str_replace( '_', ' ', $page ) );
						$page_slug  = 'lp-' . str_replace( '_', '-', $page );
					}

					$data_create_page = array(
						'post_title' => $page_title,
						'post_name'  => $page_slug,
					);
					LP_Helper::create_page( $data_create_page, "learn_press_{$page}_page_id" );
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

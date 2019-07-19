<?php
/**
 * Install and update functions.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

define( 'LEARN_PRESS_UPDATE_DATABASE', true );

if ( ! function_exists( 'LP_Install' ) ) {

	/**
	 * Class LP_Install
	 */
	class LP_Install {

		/**
		 * Hold the file for each update
		 *
		 * @var array
		 */
		private static $_update_files = array();

		/**
		 * @var null
		 */
		private static $_is_old_version = null;

		/**
		 * Default static pages used by LP
		 *
		 * @var array
		 */
		private static $_pages = array( 'checkout', 'profile', 'courses', 'become_a_teacher' );

		/**
		 * Init action.
		 */
		public static function init() {
			self::get_update_files();
			add_action( 'learn-press/activate', array( __CLASS__, 'install' ) );
			add_action( 'admin_init', array( __CLASS__, 'do_update' ) );
			add_action( 'admin_init', array( __CLASS__, 'check_update' ) );
			add_action( 'admin_init', array( __CLASS__, 'subscription_button' ) );

			//add_action( 'learn_press_activate', array( __CLASS__, 'install' ) );

			return;
			add_action( 'admin_init', array( __CLASS__, 'include_update' ), - 10 );
			add_action( 'admin_init', array( __CLASS__, 'update_from_09' ), 5 );
			add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
			add_action( 'admin_init', array( __CLASS__, 'db_update_notices' ), 5 );
			add_action( 'admin_init', array( __CLASS__, 'update_actions' ), 5 );
			add_action( 'wp_ajax_lp_repair_database', array( __CLASS__, 'repair_database' ) );
			add_action( 'wp_ajax_lp_rollback_database', array( __CLASS__, 'rollback_database' ) );
			add_action( 'wp_ajax_learn_press_hide_upgrade_notice', array( __CLASS__, 'hide_upgrade_notice' ) );
			add_action( 'admin_init', array( __CLASS__, 'upgrade_wizard' ) );
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		}

		/**
		 * Run updater if user click on 'Update Now' button
		 */
		public static function do_update() {
			if ( empty( $_REQUEST['do-update-learnpress'] ) ) {
				return;
			}

			if ( ! empty( $_REQUEST['redirect'] ) ) {
				wp_safe_redirect( urldecode( $_REQUEST['redirect'] ) );
			}
		}

		public static function subscription_button() {
			// Only administrator of the site can do this
			if ( ! current_user_can( 'administrator' ) ) {
				return;
			}

			LP_Admin_Notice::instance()->add('tools/subscription-button.php', '', true, 'newsletter-button');

//			return;
//
//			$is_dismiss_newsletter_button = get_option( 'learn-press-dismissed-newsletter-button', 0 );
//			if ( $is_dismiss_newsletter_button ) {
//				return;
//			}
//			// Show message if the latest version is not already updated
//			add_action( 'admin_notices', array( __CLASS__, 'show_subscription_button' ), 20 );
		}

//		public static function show_subscription_button() {
//			learn_press_admin_view( 'tools/subscription-button' );
//		}

		/**
		 * Check new update and show message in admin
		 */
		public static function check_update() {

			// Only administrator of the site can do this
			if ( ! current_user_can( 'administrator' ) ) {
				return;
			}

			/**
			 * For test upgrade
			 */
			if ( isset( $_REQUEST['test-upgrade'] ) ) {
				$ver = $_REQUEST['test-upgrade'];
				if ( ! empty( self::$_update_files[ $ver ] ) ) {
					include_once LP_PLUGIN_PATH . '/inc/updates/' . self::$_update_files[ $ver ];
				}
			}

			// There is no file to update
			if ( ! self::$_update_files ) {
				return;
			}

			// Get versions
			$versions   = array_keys( self::$_update_files );
			$latest_ver = end( $versions );
			$db_version = get_option( 'learnpress_db_version' );

			// Check latest version with the value updated in db
			if ( ! $db_version || version_compare( $db_version, $latest_ver, '>=' ) ) {
				return;
			}

//			// If version to update is less than in db
//			if ( version_compare( $latest_ver, $db_version, '<' ) ) {
//				return;
//			}

			// Show message if the latest version is not already updated
			add_action( 'admin_notices', array( __CLASS__, 'check_update_message' ), 20 );
		}

		/**
		 * Show message for new update
		 */
		public static function check_update_message() {
			learn_press_admin_view( 'updates/html-update-message' );
		}

		/**
		 * Run installation after LearnPress is activated.
		 */
		public static function install() {
			self::create_options();
			self::create_tables();
			self::_create_cron_jobs();
			self::_delete_transients();
			self::_create_log_path();
			self::_clear_backgrounds();
			///self::_create_pages();
			delete_transient( 'lp_upgraded_30' );
			$current_version    = get_option( 'learnpress_version', null );
			$current_db_version = get_option( 'learnpress_db_version', null );

			// Fresh installation
			if ( is_null( $current_version ) && is_null( $current_db_version ) ) {
				update_option( 'learn_press_install', 'yes' );
				set_transient( 'lp_activation_redirect', 'yes', 60 );
			}

			// Force to show notice outdated template
			learn_press_delete_user_option( 'hide-notice-template-files' );

			LP_Admin_Notice::instance()->remove_dismissed_notice(
				array(
					'outdated-template'
				)
			);

			self::update_db_version();
			self::update_version();
		}

		protected static function _clear_backgrounds() {
			global $wpdb;
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->options} WHERE option_name LIKE %s
			", $wpdb->esc_like( 'lp_schedule_items_batch_' ) . '%' );
			$wpdb->query( $query );

			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->options} WHERE option_name LIKE %s
			", $wpdb->esc_like( 'lp_installer_batch_' ) . '%' );
			$wpdb->query( $query );
		}

		/**
		 * Update default options for LP
		 */
		public static function create_options() {
			//include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-base.php';
			$settings_classes = array(
				'LP_Settings_General'  => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-general.php',
				'LP_Settings_Courses'  => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-courses.php',
				'LP_Settings_Pages'    => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-pages.php',
				///'LP_Settings_Checkout' => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-checkout.php',
				'LP_Settings_Profile'  => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-profile.php',
				'LP_Settings_Payments' => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-payments.php',
				'LP_Settings_Emails'   => include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-emails.php'
			);
			ob_start();
			$str = array();

			foreach ( $settings_classes as $c => $class ) {
				if ( ! is_object( $class ) ) {
					$class = @new $c();
				}

				$options = array();

				switch ( $c ) {
					case 'LP_Settings_Emails':
						$options = $class->get_settings_general();
						break;
					default:
						if ( ! is_callable( array( $class, 'get_settings' ) ) ) {
							continue 2;
						}

						$options = $class->get_settings( '', '' );
				}

				if ( ! $options ) {
					continue;
				}

				foreach ( $options as $option ) {
					if ( ( isset( $option['default'] ) || isset( $option['std'] ) ) && isset( $option['id'] ) ) {

						if ( ! preg_match( '~^learn_press_~', $option['id'] ) ) {
							$option_name = 'learn_press_' . $option['id'];
						} else {
							$option_name = $option['id'];
						}

						if ( false !== get_option( $option_name ) ) {
							continue;
						}

						$value = array_key_exists( 'default', $option ) ? $option['default'] : $option['std'];
						$value = get_option( $option_name, $value );

						$value = maybe_serialize( $value );

						$str[] = "{$option_name}=$value";
					}
				}
			}

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
		 * Create tables.
		 */
		public static function create_tables() {
			global $wpdb;

			// Do not show errors
			$wpdb->hide_errors();

			error_reporting( 0 );
			ini_set( 'display_errors', 0 );

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

			if ( $schema = self::_get_schema() ) {
				dbDelta( $schema );
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
			$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%', $wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );
		}

		/**
		 * Create log directory and add some files for security.
		 */
		public static function _create_log_path() {
			$files = array(
				array(
					'base'    => LP_LOG_PATH,
					'file'    => '.htaccess',
					'content' => 'deny from all'
				),
				array(
					'base'    => LP_LOG_PATH,
					'file'    => 'index.html',
					'content' => ''
				)
			);

			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
					if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
						fwrite( $file_handle, $file['content'] );
						fclose( $file_handle );
					}
				}
			}
		}

		/**
		 * Remove learnpress page if total of learn page > 10
		 *
		 * @return mixed
		 */
		public static function _remove_pages() {
			global $wpdb;

			// Get all pages
			$sql = $wpdb->prepare( "
				SELECT * 
	            FROM {$wpdb->posts} p 
	            INNER JOIN  {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key= %s AND p.post_type = %s
	        ", '_learn_press_page', 'page' );

			$page_ids = $wpdb->get_col( $sql );

			if ( sizeof( $page_ids ) < 10 ) {
				return $page_ids;
			}

			// Delete pages
			$query = $wpdb->prepare( "
				DELETE FROM p, pm
				USING {$wpdb->posts} AS p LEFT JOIN {$wpdb->postmeta} AS pm ON p.ID = pm.post_id AND p.post_type = %s
				WHERE p.post_status = %s 
				AND p.ID IN(" . implode( ',', $page_ids ) . ")
			", 'page', 'publish' );

			$wpdb->query( $query );

			$pages = self::$_pages;
			foreach ( $pages as $page ) {
				delete_option( "learn_press_{$page}_page_id" );
			}

			return array();
		}

		/**
		 * Create default pages for LP
		 */
		public static function _create_pages() {
			global $wpdb;
			$created_page = self::_remove_pages();

			if ( ! empty( $created_page ) ) {
				return;
			}

			$pages = self::$_pages;
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
									$wpdb->prepare( "
									SELECT ID
										FROM $wpdb->posts p
										INNER JOIN $wpdb->postmeta pm ON p.ID = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %d
								", '_lpr_is_profile_page', 1 )
								);
							}
							break;
					}

					if ( ! $page_id ) {
						$inserted = wp_insert_post(
							array(
								'post_title'     => 'LP ' . ucwords( str_replace( '_', ' ', $page ) ),
								'post_status'    => 'publish',
								'post_type'      => 'page',
								'comment_status' => 'closed'
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
		}

		/**
		 * Search LP page to see if they are already created.
		 *
		 * @param $type
		 * @param $types
		 *
		 * @return int|mixed
		 */
		protected static function _search_page( $type, $types ) {
			static $pages = array();
			if ( empty( $pages[ $type ] ) ) {
				global $wpdb;
				$in_types = array_fill( 0, sizeof( $types ), '%s' );
				$args     = array( '_learn_press_page' );
				$args     = array_merge( $args, $types );
				$args[]   = 'publish';
				$query    = $wpdb->prepare( "
					SELECT ID, pm.meta_value as type
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value IN(" . join( ',', $in_types ) . ")
					WHERE p.post_status = %s
				", $args );
				if ( $rows = $wpdb->get_results( $query ) ) {
					foreach ( $rows as $row ) {
						$pages[ $row->type ] = $row->ID;
					}
				}
			}

			$page_id = ! empty( $pages[ $type ] ) ? $pages[ $type ] : 0;

			return $page_id;
		}

		/**********************************/

		public static function include_update() {
			if ( ! self::$_update_files ) {
				return;
			}
			$versions       = array_keys( self::$_update_files );
			$latest_version = end( $versions );
			// Update LearnPress from 0.9.x to 1.0
			if ( version_compare( learn_press_get_current_version(), $latest_version, '=' ) ) {
				add_action( 'admin_notices', array( __CLASS__, 'hide_other_notices' ), - 100 );
				learn_press_include( 'updates/' . self::$_update_files[ $latest_version ] );
			}
		}

		public static function hide_other_notices() {
			//remove_action( 'admin_notices', 'learn_press_one_click_install_sample_data_notice' );
		}

		public static function update_from_09() {

			if ( ! self::_has_new_table() || version_compare( LEARNPRESS_VERSION, get_option( 'learnpress_db_version' ), '>' ) ) {
				self::install();
			}
			if ( ! get_option( 'learnpress_version' ) || ! get_option( 'learn_press_currency' ) ) {
				self::create_options();
			}
			$ask = get_transient( 'learn_press_upgrade_courses_ask_again' );
			if ( self::_need_to_update() ) {
				// Notify for administrator
				if ( empty( $ask ) && learn_press_current_user_is( 'administrator' ) ) {
					LP_Assets::enqueue_style( 'learn-press-upgrade', LP()->plugin_url( 'inc/updates/09/style.css' ) );
					LP_Assets::enqueue_script( 'learn-press-upgrade', LP()->plugin_url( 'inc/updates/09/script.js' ) );
					$upgrade_url = wp_nonce_url( admin_url( 'options-general.php?page=learn_press_upgrade_from_09' ), 'learn-press-upgrade-09' );
					$message     = sprintf( '<p>%s</p>', __( 'It seems like you have updated LearnPress from an older version and there are some outdated courses or data that need to be upgraded.', 'learnpress' ) );
					$message     .= sprintf( '<div id="learn-press-confirm-abort-upgrade-course"><p><label><input type="checkbox" id="learn-press-ask-again-abort-upgrade" /> %s</label></p><p><button href="" class="button disabled" data-action="yes">%s</button> <button href="" class="button" data-action="no">%s</button> </p></div>', __( 'Do not ask again.', 'learnpress' ), __( 'Ok', 'learnpress' ), __( 'Cancel', 'learnpress' ) );
					$message     .= sprintf( '<p id="learn-press-upgrade-course-actions"><a href="%s" class="button" data-action="upgrade">%s</a>&nbsp;<button class="button disabled" data-action="abort">%s</button></p>', $upgrade_url, __( 'Upgrade now', 'learnpress' ), __( 'No, thank!', 'learnpress' ) );

					LP_Admin_Notice::instance()->add( $message, 'error' );
				}

				// Notify for instructor
				if ( learn_press_current_user_is( 'instructor' ) ) {
					LP_Admin_Notice::instance()->add( sprintf( '<p>%s</p>', __( 'LearnPress has been updated and the database needs to be upgraded before you can work with it. Please notify the site administrator.', 'learnpress' ) ), 'error' );
				}
			}
		}

		public static function admin_menu() {
			add_dashboard_page( '', '', 'manage_options', 'learn_press_upgrade_from_09', '' );

		}

		public static function hide_upgrade_notice() {
			$ask_again  = learn_press_get_request( 'ask_again' );
			$expiration = DAY_IN_SECONDS;
			if ( $ask_again == 'no' ) {
				$expiration = 0;
			}
			set_transient( 'learn_press_upgrade_courses_ask_again', $ask_again, $expiration );
			learn_press_send_json( array(
				'result'  => 'success',
				'message' => sprintf( '<p>%s</p>', __( 'Thank you for using LearnPress', 'learnpress' ) )
			) );
		}

		public static function upgrade_wizard() {
			require_once LP_PLUGIN_PATH . '/inc/updates/_update-from-0.9.php';
		}

		/**
		 * Scan folder updates to get update patches.
		 */
		public static function get_update_files() {
			if ( ! self::$_update_files ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				if ( WP_Filesystem() ) {
					global $wp_filesystem;

					if ( $files = $wp_filesystem->dirlist( LP_PLUGIN_PATH . '/inc/updates' ) ) {
						foreach ( $files as $file ) {
							if ( preg_match( '!learnpress-update-([0-9.]+).php!', $file['name'], $matches ) ) {
								self::$_update_files [ $matches[1] ] = $file['name'];
							}
						}
					}

				}
				/**
				 * Sort files by version
				 */
				if ( self::$_update_files ) {
					ksort( self::$_update_files );
				}
			}
		}

		/**
		 * Check version
		 */
		public static function check_version() {
			if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'learnpress_version' ) != LP()->version ) ) {
				self::install();
			}
		}

		/**
		 * Install update actions when user click update button
		 */
		public static function update_actions() {
			if ( ! empty( $_GET['upgrade_learnpress'] ) ) {
				self::update();
			}
		}

		/**
		 * Check for new database version and show notice
		 */
		public static function db_update_notices() {
			if ( get_option( 'learnpress_db_version' ) != LP()->version ) {
				// code
			}
		}


		private static function _create_cron_jobs() {
			wp_clear_scheduled_hook( 'learn_press_cleanup_sessions' );
			wp_schedule_event( time(), apply_filters( 'learn_press_cleanup_session_recurrence', 'twicedaily' ), 'learn_press_cleanup_sessions' );
		}

		public static function _auto_update() {
			self::get_update_files();
			self::update();
		}


		private function _is_old_version() {
			if ( is_null( self::$_is_old_version ) ) {
				$is_old_version = get_transient( 'learn_press_is_old_version' );

				if ( empty( $is_old_version ) ) {
					if ( ! get_option( 'learnpress_db_version' ) ||
					     get_posts(
						     array(
							     'post_type'      => 'lpr_course',
							     'post_status'    => 'any',
							     'posts_per_page' => 1
						     )
					     )
					) {
						$is_old_version = 'yes';
					}
					if ( empty( $is_old_version ) ) {
						$is_old_version = 'no';
					}
					set_transient( 'learn_press_is_old_version', $is_old_version );
				}
				self::$_is_old_version = $is_old_version == 'yes';
			}

			return self::$_is_old_version;
		}

		/**
		 * Find if there is any old course and did not upgrade
		 * If a course has got a meta key like _learn_press_upgraded that means it is not upgraded
		 *
		 * @return mixed
		 */
		private static function _has_old_posts() {
			global $wpdb;
			$query = $wpdb->prepare( "
			SELECT DISTINCT p.ID, pm.meta_value as upgraded
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE post_type = %s
			HAVING upgraded IS NULL
			LIMIT 0, 1
		", '_learn_press_upgraded', 'lpr_course' );

			return $wpdb->get_row( $query );
		}

		private static function _has_new_table() {
			global $wpdb;
			$query = $wpdb->prepare( "
			SELECT COUNT(*)
			FROM information_schema.tables
			WHERE table_schema = %s
			AND table_name LIKE %s
		", DB_NAME, '%learnpress_sections%' );

			return $wpdb->get_var( $query ) ? true : false;
		}

		private static function _need_to_update() {
			return self::_has_old_posts() || self::_has_old_teacher_role();
		}

		private static function _has_old_teacher_role() {
			global $wpdb;
			$query = $wpdb->prepare( "
			SELECT um.*
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s
			WHERE um.meta_value LIKE %s
			LIMIT 0, 1
		", 'wp_capabilities', '%"lpr_teacher"%' );

			return $wpdb->get_results( $query );
		}

		private static function _has_new_posts() {
			$new_post = get_posts(
				array(
					'post_type'      => 'lp_course',
					'post_status'    => 'any',
					'posts_per_page' => 1
				)
			);

			return sizeof( $new_post ) > 0;
		}

		public static function update() {
			$learnpress_db_version = get_option( 'learnpress_db_version' );

			foreach ( self::$_update_files as $version => $updater ) {
				if ( version_compare( $learnpress_db_version, $version, '<' ) ) {
					@include( LP_PLUGIN_PATH . '/inc/updates/' . $updater );
					self::update_db_version( $version );
				}
			}

			self::update_db_version();
			self::update_version();
		}

		public static function update_db_version( $version = null ) {
			delete_option( 'learnpress_db_version' );
			update_option( 'learnpress_db_version', is_null( $version ) ? LEARNPRESS_VERSION : $version );

			//LP_Debug::instance()->add( debug_backtrace(), 'update_db_version', false, true );
		}

		public static function update_version( $version = null ) {
			delete_option( 'learnpress_version' );
			update_option( 'learnpress_version', is_null( $version ) ? LEARNPRESS_VERSION : $version );
		}


		/**
		 * Build sql queries to create tables.
		 *
		 * @return string
		 */
		private static function _get_schema() {
			global $wpdb;

			$collate = '';

			if ( $wpdb->has_cap( 'collation' ) ) {
				if ( ! empty( $wpdb->charset ) ) {
					$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
				}
				if ( ! empty( $wpdb->collate ) ) {
					$collate .= " COLLATE $wpdb->collate";
				}
			}
			$tables = $wpdb->get_col( $wpdb->prepare( "SHOW TABLES LIKE %s", '%' . $wpdb->esc_like( 'learnpress' ) . '%' ) );
			$query  = '';

			if ( ! in_array( $wpdb->learnpress_order_itemmeta, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_order_itemmeta} (
					meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					learnpress_order_item_id bigint(20) unsigned NOT NULL DEFAULT '0',
					meta_key varchar(45) NOT NULL DEFAULT '',
					meta_value longtext NOT NULL,
					PRIMARY KEY  (meta_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_order_items, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_order_items} (
					order_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					order_item_name longtext NOT NULL,
					order_id bigint(20) unsigned NOT NULL DEFAULT '0',
					PRIMARY KEY  (order_item_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_question_answers, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_question_answers} (
					question_answer_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					question_id bigint(20) unsigned NOT NULL DEFAULT '0',
					answer_data text NOT NULL,
					answer_order bigint(20) unsigned NOT NULL DEFAULT '0',
					PRIMARY KEY  (question_answer_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_quiz_questions, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_quiz_questions} (
					quiz_question_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					quiz_id bigint(20) unsigned NOT NULL DEFAULT '0',
					question_id bigint(20) unsigned NOT NULL DEFAULT '0',
					question_order bigint(20) unsigned NOT NULL DEFAULT '1',
					params longtext NULL,
					PRIMARY KEY  (quiz_question_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_review_logs, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_review_logs} (
					review_log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					course_id bigint(20) unsigned NOT NULL DEFAULT '0',
					user_id bigint(20) unsigned NOT NULL DEFAULT '0',
					message text NOT NULL,
					date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					status varchar(45) NOT NULL DEFAULT '',
					user_type varchar(45) NOT NULL DEFAULT '',
					PRIMARY KEY  (review_log_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_section_items, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_section_items} (
					section_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					section_id bigint(20) unsigned NOT NULL DEFAULT '0',
					item_id bigint(20) unsigned NOT NULL DEFAULT '0',
					item_order bigint(20) unsigned NOT NULL DEFAULT '0',
					item_type varchar(45),
					PRIMARY KEY  (section_item_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_sections, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_sections} (
					section_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					section_name varchar(255) NOT NULL DEFAULT '',
					section_course_id bigint(20) unsigned NOT NULL DEFAULT '0',
					section_order bigint(5) unsigned NOT NULL DEFAULT '0',
					section_description longtext NOT NULL,
					PRIMARY KEY  (section_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_sessions, $tables ) ) {
				$query .= "
				CREATE TABLE  {$wpdb->learnpress_sessions} (
					session_id bigint(20) NOT NULL AUTO_INCREMENT,
					session_key char(32) NOT NULL,
					session_value longtext NOT NULL,
					session_expiry bigint(20) NOT NULL,
					UNIQUE KEY session_id (session_id),
					PRIMARY KEY  (session_key)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_user_items, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_user_items} (
					user_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					user_id bigint(20) unsigned NOT NULL DEFAULT '0',
					item_id bigint(20) unsigned NOT NULL DEFAULT '0',
					start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					start_time_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					end_time_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					item_type varchar(45) NOT NULL DEFAULT '',
					status varchar(45) NOT NULL DEFAULT '',
					ref_id bigint(20) unsigned NOT NULL DEFAULT '0',
					ref_type varchar(45) DEFAULT '',
					parent_id bigint(20) unsigned NOT NULL DEFAULT '0',
					PRIMARY KEY  (user_item_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_user_itemmeta, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->prefix}learnpress_user_itemmeta (
					meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					learnpress_user_item_id bigint(20) unsigned NOT NULL,
					meta_key varchar(45) NOT NULL DEFAULT '',
					meta_value text NOT NULL,
					PRIMARY KEY  (meta_id)
				) $collate;
				";
			}

			if ( ! in_array( $wpdb->learnpress_question_answermeta, $tables ) ) {
				$query .= "
				CREATE TABLE {$wpdb->learnpress_question_answermeta} (
					meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					learnpress_question_answer_id bigint(20) unsigned NOT NULL,
					meta_key varchar(45) NOT NULL DEFAULT '',
					meta_value text NOT NULL,
					PRIMARY KEY  (meta_id)
				) $collate;
				";
			}

			return $query;
		}
	}

	LP_Install::init();
}
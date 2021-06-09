<?php
/**
 * Install and update functions.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
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
		private static $_pages = array( 'checkout', 'profile', 'courses', 'become_a_teacher', 'term_conditions' );

		/**
		 * Init action.
		 */
		public static function init() {
			self::get_update_files();

			add_action( 'learn-press/activate', array( __CLASS__, 'on_activate' ) );
			add_action( 'admin_init', array( __CLASS__, 'do_install' ) );
			add_action( 'admin_init', array( __CLASS__, 'subscription_button' ) );
		}

		/**
		 * Do something after LP is activated.
		 *
		 * @since 4.0.0
		 */
		public static function on_activate() {
			update_option( 'learn_press_status', 'activated' );

			// Force option permalink to 'postname'.
			if ( ! get_option( 'permalink_structure' ) ) {
				update_option( 'permalink_structure', '/%postname%/' );
			}

			// Force option users_can_register to ON.
			if ( ! get_option( 'users_can_register' ) ) {
				update_option( 'users_can_register', 1 );
			}

			if ( ! get_option( 'learn_press_currency' ) ) {
				update_option( 'learn_press_currency', 'USD' );
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
		 */
		public static function install() {
			self::create_options();
			self::create_tables();
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

			LP_Admin_Notice::instance()->remove_dismissed_notice( array( 'outdated-template', ) );

			//if ( ! get_option( 'learnpress_db_version' ) ) {
			update_option( 'learnpress_db_version', (int) LEARNPRESS_VERSION );
			//}
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
		 * Create tables.
		 */
		public static function create_tables() {
			global $wpdb;

			// Do not show errors .
			$wpdb->hide_errors();

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

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
			$wpdb->query( $wpdb->prepare( $sql, $wpdb->esc_like( '_transient_' ) . '%',
				$wpdb->esc_like( '_transient_timeout_' ) . '%', time() ) );
		}

		/**
		 * Create log directory and add some files for security.
		 */
		/*public static function _create_log_path() {
			$files = array(
				array(
					'base'    => LP_LOG_PATH,
					'file'    => '.htaccess',
					'content' => 'deny from all',
				),
				array(
					'base'    => LP_LOG_PATH,
					'file'    => 'index.html',
					'content' => '',
				),
			);

			foreach ( $files as $file ) {
				if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
					if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
						fwrite( $file_handle, $file['content'] );
						fclose( $file_handle );
					}
				}
			}
		}*/

		/**
		 * Remove learnpress page if total of learn page > 10
		 *
		 * @return mixed
		 */
		public static function _remove_pages() {
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
		}

		/**
		 * Create default pages for LP
		 */
		public static function _create_pages() {
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
				learn_press_include( 'updates/' . self::$_update_files[ $latest_version ] );
			}
		}

		public static function hide_upgrade_notice() {
			$ask_again  = learn_press_get_request( 'ask_again' );
			$expiration = DAY_IN_SECONDS;

			if ( $ask_again == 'no' ) {
				$expiration = 0;
			}

			set_transient( 'learn_press_upgrade_courses_ask_again', $ask_again, $expiration );

			learn_press_send_json(
				array(
					'result'  => 'success',
					'message' => sprintf( '<p>%s</p>', __( 'Thank you for using LearnPress', 'learnpress' ) ),
				)
			);
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

					/**
					 * @var WP_Filesystem_Base $wp_filesystem
					 */
					global $wp_filesystem;

					$files = $wp_filesystem->dirlist( LP_PLUGIN_PATH . '/inc/updates' );

					if ( $files ) {
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

				if ( file_exists( LP_PLUGIN_PATH . '/inc/updates/learnpress-update-x.x.x.php' ) ) {
					self::$_update_files['9.9.9'] = 'learnpress-update-x.x.x.php';
				}
			}

		}

		/**
		 * Check version
		 */
		/*public static function check_version() {
			if ( ! defined( 'IFRAME_REQUEST' ) && ( get_option( 'learnpress_version' ) != LP()->version ) ) {
				self::install();
			}
		}*/

		private static function _create_cron_jobs() {
			wp_clear_scheduled_hook( 'learn_press_cleanup_sessions' );
			wp_schedule_event( time(), apply_filters( 'learn_press_cleanup_session_recurrence', 'twicedaily' ),
				'learn_press_cleanup_sessions' );
		}

		/*private function _is_old_version() {
			if ( is_null( self::$_is_old_version ) ) {
				$is_old_version = get_transient( 'learn_press_is_old_version' );

				if ( empty( $is_old_version ) ) {
					if ( ! get_option( 'learnpress_db_version' ) ||
					     get_posts(
						     array(
							     'post_type'      => 'lpr_course',
							     'post_status'    => 'any',
							     'posts_per_page' => 1,
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
		}*/

		/**
		 * Find if there is any old course and did not upgrade
		 * If a course has got a meta key like _learn_press_upgraded that means it is not upgraded
		 *
		 * @return mixed
		 */
		/*private static function _has_old_posts() {
			global $wpdb;
			$query = $wpdb->prepare(
				"
			SELECT DISTINCT p.ID, pm.meta_value as upgraded
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE post_type = %s
			HAVING upgraded IS NULL
			LIMIT 0, 1
		",
				'_learn_press_upgraded',
				'lpr_course'
			);

			return $wpdb->get_row( $query );
		}*/

		private static function _has_new_table() {
			global $wpdb;
			$query = $wpdb->prepare(
				'
			SELECT COUNT(*)
			FROM information_schema.tables
			WHERE table_schema = %s
			AND table_name LIKE %s
		',
				DB_NAME,
				'%learnpress_sections%'
			);

			return $wpdb->get_var( $query ) ? true : false;
		}

		/*private static function _need_to_update() {
			return self::_has_old_posts() || self::_has_old_teacher_role();
		}*/

		/*private static function _has_old_teacher_role() {
			global $wpdb;
			$query = $wpdb->prepare(
				"
			SELECT um.*
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s
			WHERE um.meta_value LIKE %s
			LIMIT 0, 1
		",
				'wp_capabilities',
				'%"lpr_teacher"%'
			);

			return $wpdb->get_results( $query );
		}*/

		/*private static function _has_new_posts() {
			$new_post = get_posts(
				array(
					'post_type'      => 'lp_course',
					'post_status'    => 'any',
					'posts_per_page' => 1,
				)
			);

			return sizeof( $new_post ) > 0;
		}*/

//		public static function update_db_version( $version = null ) {
//			delete_option( 'learnpress_db_version' );
//			update_option( 'learnpress_db_version', is_null( $version ) ? LEARNPRESS_VERSION : $version );
//		}

//		public static function update_version( $version = null ) {
//			delete_option( 'learnpress_version' );
//			update_option( 'learnpress_version', is_null( $version ) ? LEARNPRESS_VERSION : $version );
//		}


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

			$tables = $wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s',
				'%' . $wpdb->esc_like( 'learnpress' ) . '%' ) );
			$query  = '';

			if ( ! in_array( $wpdb->learnpress_order_items, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_order_items} (
					order_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					order_item_name longtext NOT NULL,
					order_id bigint(20) unsigned NOT NULL DEFAULT 0,
					item_id bigint(20) unsigned NOT NULL DEFAULT 0,
					item_type varchar(45) NOT NULL DEFAULT '',
					PRIMARY KEY (order_item_id),
  					KEY order_id (order_id),
  					KEY item_id (item_id),
  					KEY item_type (item_type)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_order_itemmeta, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_order_itemmeta} (
					meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					learnpress_order_item_id bigint(20) unsigned NOT NULL DEFAULT '0',
					meta_key varchar(255) NOT NULL DEFAULT '',
					meta_value varchar(255) NULL,
					extra_value longtext,
					PRIMARY KEY (meta_id),
  					KEY learnpress_order_item_id (learnpress_order_item_id),
  					KEY meta_key (meta_key(190)),
  					KEY meta_value (meta_value(190))
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_question_answers, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_question_answers} (
					question_answer_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					question_id bigint(20) unsigned NOT NULL DEFAULT '0',
					title text NOT NULL,
					`value` varchar(32) NOT NULL,
					`order` bigint(20) unsigned NOT NULL DEFAULT '1',
					is_true varchar(3),
					PRIMARY KEY (question_answer_id),
					KEY question_id (question_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_question_answermeta, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_question_answermeta} (
					meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					learnpress_question_answer_id bigint(20) unsigned NOT NULL,
					meta_key varchar(255) NOT NULL DEFAULT '',
					meta_value longtext NULL,
					PRIMARY KEY (meta_id),
					KEY question_answer_meta (`learnpress_question_answer_id`, `meta_key`(150))
				) $collate;
				";
			}

			if ( ! in_array( $wpdb->learnpress_quiz_questions, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_quiz_questions} (
					quiz_question_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					quiz_id bigint(20) unsigned NOT NULL DEFAULT '0',
					question_id bigint(20) unsigned NOT NULL DEFAULT '0',
					question_order bigint(20) unsigned NOT NULL DEFAULT '1',
					PRIMARY KEY (quiz_question_id),
					KEY quiz_id (quiz_id),
					KEY question_id (question_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_review_logs, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_review_logs} (
					review_log_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					course_id bigint(20) unsigned NOT NULL DEFAULT '0',
					user_id bigint(20) unsigned NOT NULL DEFAULT '0',
					message text NOT NULL,
					date datetime NULL DEFAULT NULL,
					status varchar(45) NOT NULL DEFAULT '',
					user_type varchar(45) NOT NULL DEFAULT '',
					PRIMARY KEY (review_log_id),
				    KEY course_id (course_id),
					KEY user_id (user_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_section_items, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_section_items} (
					section_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					section_id bigint(20) unsigned NOT NULL DEFAULT '0',
					item_id bigint(20) unsigned NOT NULL DEFAULT '0',
					item_order bigint(20) unsigned NOT NULL DEFAULT '0',
					item_type varchar(45),
					PRIMARY KEY (section_item_id),
					KEY section_item (`section_id`, `item_id`)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_sections, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_sections} (
					section_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					section_name varchar(255) NOT NULL DEFAULT '',
					section_course_id bigint(20) unsigned NOT NULL DEFAULT '0',
					section_order bigint(10) unsigned NOT NULL DEFAULT '1',
					section_description longtext NOT NULL,
					PRIMARY KEY (section_id),
					KEY section_course_id (section_course_id)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_sessions, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_sessions} (
					session_id bigint(20) NOT NULL AUTO_INCREMENT,
					session_key char(32) NOT NULL,
					session_value longtext NOT NULL,
					session_expiry bigint(20) NOT NULL,
					UNIQUE KEY session_id (session_id),
					PRIMARY KEY (session_key)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_user_items, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_user_items} (
					user_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					user_id bigint(20) unsigned NOT NULL DEFAULT '0',
					item_id bigint(20) unsigned NOT NULL DEFAULT '0',
					start_time datetime NULL DEFAULT NULL,
					end_time datetime NULL DEFAULT NULL,
					item_type varchar(45) NOT NULL DEFAULT '',
					status varchar(45) NOT NULL DEFAULT '',
					graduation varchar(20) NULL DEFAULT NULL,
					access_level int(3) NOT NULL DEFAULT 50,
					ref_id bigint(20) unsigned NOT NULL DEFAULT '0',
					ref_type varchar(45) DEFAULT '',
					parent_id bigint(20) unsigned NOT NULL DEFAULT '0',
					PRIMARY KEY (user_item_id),
					KEY parent_id (parent_id),
					KEY user_id (user_id),
					KEY item_id (item_id),
					KEY item_type (item_type),
					KEY ref_id (ref_id),
					KEY ref_type (ref_type),
					KEY status (status)
				) $collate;";
			}

			if ( ! in_array( $wpdb->learnpress_user_itemmeta, $tables ) ) {
				$query .= "
				CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_user_itemmeta} (
					meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					learnpress_user_item_id bigint(20) unsigned NOT NULL,
					meta_key varchar(255) NOT NULL DEFAULT '',
					meta_value varchar(255) NULL,
					extra_value longtext NULL,
					PRIMARY KEY (meta_id),
					KEY learnpress_user_item_id (learnpress_user_item_id),
  					KEY meta_key (meta_key(190)),
  					KEY meta_value (meta_value(190))
				) $collate;
				";
			}

			if ( ! in_array( $wpdb->learnpress_user_item_results, $tables ) ) {
				$query .= "
					CREATE TABLE IF NOT EXISTS {$wpdb->learnpress_user_item_results} (
						id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
						user_item_id bigint(20) unsigned NOT NULL,
						result longtext NULL,
						PRIMARY KEY (id),
						KEY user_item_id (user_item_id)
					) $collate;
				";
			}

			return $query;
		}
	}

	LP_Install::init();
}

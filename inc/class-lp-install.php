<?php
/**
 * Install and update functions
 *
 * @author  ThimPress
 * @version 1.0
 * @see     https://codex.wordpress.org/Creating_Tables_with_Plugins
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;

/**
 * Class LP_Install
 */
class LP_Install {

	/**
	 * DB update versions
	 *
	 * @var array
	 */
	private static $_db_updates = array();

	private static $_is_old_version = null;

	/**
	 * Init
	 */
	static function init() {
		add_action( 'admin_init', array( __CLASS__, 'update_version_10' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'get_update_versions' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'check_version' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'db_update_notices' ), 5 );
		add_action( 'admin_init', array( __CLASS__, 'update_actions' ), 5 );
		add_action( 'wp_ajax_lp_repair_database', array( __CLASS__, 'repair_database' ) );
		add_action( 'wp_ajax_lp_rollback_database', array( __CLASS__, 'rollback_database' ) );
		add_action( 'wp_ajax_learn_press_hide_upgrade_notice', array( __CLASS__, 'hide_upgrade_notice' ) );
		add_action( 'admin_init', array( __CLASS__, 'upgrade_wizard' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	static function update_version_10() {

		$update = false;

		if( !self::_has_new_table() ){
			self::_create_tables();
			$update = true;
		}
		if( ! get_option( 'learnpress_version' ) ){
			self::_create_options();
			$update = true;
		}
		$ask = get_transient( 'learn_press_upgrade_courses_ask_again' );
		if ( version_compare( LEARNPRESS_VERSION, '1.0' ) === 0 && self::_need_to_update() || $update ) {
			// Notify for administrator
			if(  empty( $ask ) && learn_press_current_user_is( 'administrator') ){
				LP_Admin_Assets::enqueue_style( 'learn-press-upgrade', LP()->plugin_url( 'assets/css/admin/upgrade.css' ) );
				LP_Admin_Assets::enqueue_script( 'learn-press-upgrade', LP()->plugin_url( 'assets/js/admin/upgrade.js' ) );
				$upgrade_url = wp_nonce_url( admin_url( 'options-general.php?page=learn_press_upgrade_10' ), 'learn-press-upgrade' );
				$message     = sprintf( '<p>%s</p>', __( 'It seem to be you have updated LearnPress from old version and there are some courses or data is out of date and need to upgrade.', 'learn_press' ) );
				$message .= sprintf( '<div id="learn-press-confirm-abort-upgrade-course"><p><label><input type="checkbox" id="learn-press-ask-again-abort-upgrade" /> %s</label></p><p><button href="" class="button disabled" data-action="yes">%s</button> <button href="" class="button" data-action="no">%s</button> </p></div>', __( 'Do not ask again.', 'learn_press' ), __( 'Ok', 'learn_press' ), __( 'Cancel', 'learn_press' ) );
				$message .= sprintf( '<p id="learn-press-upgrade-course-actions"><a href="%s" class="button" data-action="upgrade">%s</a>&nbsp;<button class="button disabled" data-action="abort">%s</button></p>', $upgrade_url, __( 'Upgrade now', 'learn_press' ), __( 'No, thank!', 'learn_press' ) );

				LP_Admin_Notice::add( $message, 'error' );
			}

			// Notify for instructor
			if( learn_press_current_user_is( 'instructor' ) ){
				LP_Admin_Notice::add( sprintf( '<p>%s</p>', __( 'LearnPress has upgraded and need to upgrade the database before you can work with it. Please notify the site administrator.', 'learn_press' ) ), 'error' );
			}
		}
	}

	static function admin_menu(){
		add_dashboard_page( '', '', 'manage_options', 'learn_press_upgrade_10', '' );
	}

	static function hide_upgrade_notice(){
		$ask_again = learn_press_get_request( 'ask_again' );
		$expiration = DAY_IN_SECONDS;
		if( $ask_again == 'no' ){
			$expiration = 0;
		}
		set_transient( 'learn_press_upgrade_courses_ask_again', $ask_again, $expiration );
		learn_press_send_json( array( 'result' => 'success', 'message' => sprintf( '<p>%s</p>', __( 'Thank for using LearnPress', 'learn_press' ) ) ) );
	}

	static function upgrade_wizard(){
		require_once LP_PLUGIN_PATH . '/inc/updates/learnpress-update-1.0.php';
	}

	/**
	 * Auto get update patches from inc/updates path
	 */
	static function get_update_versions() {
		if ( !$patches = get_transient( 'learnpress_update_patches' ) ) {
			$patches = array();
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( WP_Filesystem() ) {
				global $wp_filesystem;

				$list = $wp_filesystem->dirlist( LP_PLUGIN_PATH . '/inc/updates' );
				foreach ( $list as $file ) {
					if ( preg_match( '!learnpress-update-([0-9.]+).php!', $file['name'], $matches ) ) {
						$patches[$matches[1]] = $file['name'];
					}
				}
			}
			if ( $patches ) {
				self::$_db_updates = $patches;
			}
		} else {
			self::$_db_updates = $patches;
		}
	}

	/**
	 * Check version
	 */
	static function check_version() {
		if ( !defined( 'IFRAME_REQUEST' ) && ( get_option( 'learnpress_version' ) != LP()->version || get_option( 'learnpress_version' ) != LP()->version ) ) {
			self::install();
		}
	}

	/**
	 * Install update actions when user click update button
	 */
	static function update_actions() {
		if ( !empty( $_GET['upgrade_learnpress'] ) ) {
			self::update();
		}
	}

	/**
	 * Check for new database version and show notice
	 */
	static function db_update_notices() {
		if ( get_option( 'learnpress_db_version' ) != LP()->db_version ) {
			LP_Admin_Notice::add( __( '<p>LearnPress ' . LP()->version . ' need to upgrade your database.</p><p><a href="' . admin_url( 'admin.php?page=learnpress_update_10' ) . '" class="button">Update Now</a></p>', 'learn_press' ) );
		}
	}

	static function install() {
		self::_create_options();
		self::_create_tables();

		$current_version = get_option( 'learnpress_version' );
		$current_db_version = get_option( 'learnpress_db_version' );

		// is new install
		if( is_null( $current_version ) && is_null( $current_db_version ) ){

		}
		// Update version
		delete_option( 'learnpress_version' );
		add_option( 'learnpress_version', LP()->version );

	}

	private function _is_old_version() {
		if ( is_null( self::$_is_old_version ) ) {
			$is_old_version = get_transient( 'learn_press_is_old_version' );
			//echo empty( $is_old_version ) ? "null" : "<>";
			if( empty( $is_old_version ) ) {
				if ( !get_option( 'learnpress_db_version' ) ||
					get_posts(
						array(
							'post_type'      => 'lpr_course',
							'post_status'    => 'any',
							'posts_per_page' => 1
						)
					) ){
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
	private static function _has_old_posts(){
		global $wpdb;
		$query = $wpdb->prepare("
			SELECT DISTINCT p.ID, pm.meta_value as upgraded
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE post_type = %s
			HAVING upgraded IS NULL
			LIMIT 0, 1
		", '_learn_press_upgraded', 'lpr_course' );
		return $wpdb->get_row( $query );
	}

	private static function _has_new_table(){
		global $wpdb;
		$query = $wpdb->prepare("
			SELECT COUNT(*)
			FROM information_schema.tables
			WHERE table_schema = %s
			AND table_name LIKE %s
		", DB_NAME, '%learnpress_sections%');
		return $wpdb->get_var( $query ) ? true : false;
	}

	private static function _need_to_update(){
		return self::_has_old_posts() || self::_has_old_teacher_role();
	}

	private static function _has_old_teacher_role(){
		global $wpdb;
		$query = $wpdb->prepare("
			SELECT um.*
			FROM wp_users u
			INNER JOIN wp_usermeta um ON um.user_id = u.ID AND um.meta_key = %s
			WHERE um.meta_value LIKE %s
			LIMIT 0, 1
		", 'wp_capabilities', '%"lpr_teacher"%' );
		return $wpdb->get_results( $query );
	}

	private static function _has_new_posts(){
		$new_post = get_posts(
			array(
				'post_type'      => 'lp_course',
				'post_status'    => 'any',
				'posts_per_page' => 1
			)
		);
		return sizeof( $new_post ) > 0;
	}

	private static function _create_options() {
		include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-base.php';

		$settings_classes = array(
			include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-general.php',
			include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-courses.php',
			include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-checkout.php',
			include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-profile.php',
			include_once LP_PLUGIN_PATH . '/inc/admin/settings/class-lp-settings-emails.php'
		);
		foreach ( $settings_classes as $class ) {
			if ( is_callable( array( $class, 'get_settings' ) ) ) {
				$options = $class->get_settings();
				foreach ( $options as $option ) {
					if ( isset( $option['default'] ) && isset( $option['id'] ) ) {
						$autoload = isset( $option['autoload'] ) ? (bool) $option['autoload'] : true;
						update_option( $option['id'], $option['default'], '', ( $autoload ? 'yes' : 'no' ) );
					}
				}
			}
		}
	}

	private static function _create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( self::_get_schema() );
		update_option( 'learnpress_db_version', LEARNPRESS_DB_VERSION );
	}

	private static function _get_schema() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			if ( !empty( $wpdb->charset ) ) {
				$collate .= "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( !empty( $wpdb->collate ) ) {
				$collate .= " COLLATE $wpdb->collate";
			}
		}

		return "
CREATE TABLE {$wpdb->prefix}learnpress_order_itemmeta (
  meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  learnpress_order_item_id bigint(20) unsigned NOT NULL DEFAULT '0',
  meta_key varchar(45) NOT NULL DEFAULT '',
  meta_value longtext NOT NULL,
  PRIMARY KEY (meta_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_order_items (
  order_item_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  order_item_name longtext NOT NULL,
  order_id bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (order_item_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_question_answers (
  question_answer_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  question_id bigint(20) unsigned NOT NULL DEFAULT '0',
  answer_data text NOT NULL,
  ordering bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (question_answer_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_quiz_questions (
  quiz_question_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  quiz_id bigint(11) unsigned NOT NULL DEFAULT '0',
  question_id bigint(11) unsigned NOT NULL DEFAULT '0',
  question_order bigint(11) unsigned NOT NULL DEFAULT '1'
  params longtext NOT NULL,
  PRIMARY KEY (quiz_question_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_review_logs (
  review_log_id bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  course_id bigint(11) unsigned NOT NULL DEFAULT '0',
  user_id bigint(11) unsigned NOT NULL DEFAULT '0',
  message text NOT NULL,
  date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  status varchar(45) NOT NULL DEFAULT '',
  user_type varchar(45) NOT NULL DEFAULT '',
  PRIMARY KEY (review_log_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_section_items (
  item_id bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  section_id bigint(11) unsigned NOT NULL DEFAULT '0',
  section_item_id bigint(11) unsigned NOT NULL DEFAULT '0',
  section_item_order bigint(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (item_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_sections (
  section_id bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  section_name varchar(255) NOT NULL DEFAULT '',
  section_course_id bigint(11) unsigned NOT NULL DEFAULT '0',
  section_order bigint(5) unsigned NOT NULL DEFAULT '0',
  section_description longtext NOT NULL,
  is_closed tinyint(1) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (section_id)
) $collate;
CREATE TABLE  {$wpdb->prefix}learnpress_user_courses (
  user_id bigint(11) unsigned NOT NULL DEFAULT '0',
  course_id bigint(11) unsigned NOT NULL DEFAULT '0',
  start_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  end_time datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  status varchar(45) NOT NULL DEFAULT '',
  order_id bigint(11) unsigned NOT NULL DEFAULT '0',
  user_course_id bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (user_course_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_user_quizmeta (
  learnpress_user_quiz_id bigint(11) unsigned NOT NULL,
  meta_key varchar(45) NOT NULL DEFAULT '',
  meta_value text NOT NULL,
  meta_id bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (meta_id)
) $collate;
CREATE TABLE {$wpdb->prefix}learnpress_user_quizzes (
  user_quiz_id bigint(11) unsigned NOT NULL AUTO_INCREMENT,
  user_id bigint(11) unsigned NOT NULL DEFAULT '0',
  quiz_id bigint(11) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (user_quiz_id)
) $collate;
";
	}
}

LP_Install::init();
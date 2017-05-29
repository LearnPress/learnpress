<?php

defined( 'ABSPATH' ) || exit;

/**
 * Class LP_User_Factory
 */
class LP_User_Factory {
	/**
	 * @var array
	 */
	protected static $_users = array();

	/**
	 * @var int
	 */
	protected static $_guest_transient = 0;

	public static $_deleted_users = array();

	/**
	 *
	 */
	public static function init() {
		self::$_guest_transient = WEEK_IN_SECONDS;
		add_action( 'wp_login', array( __CLASS__, 'clear_temp_user_data' ) );
		add_action( 'learn_press_user_start_quiz', array( __CLASS__, 'start_quiz' ), 10, 4 );
		add_action( 'learn_press_user_retake_quiz', array( __CLASS__, 'retake_quiz' ), 10, 4 );
		add_action( 'learn_press_activate', array( __CLASS__, 'register_event' ), 15 );
		add_action( 'learn_press_deactivate', array( __CLASS__, 'deregister_event' ), 15 );
		add_action( 'learn_press_schedule_cleanup_temp_users', array( __CLASS__, 'schedule_cleanup_temp_users' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
	}

	/**
	 * Register new schedules
	 *
	 * @param $schedules
	 *
	 * @return mixed
	 */
	public static function cron_schedules( $schedules ) {
		$schedules['every_three_minutes'] = array(
			'interval' => self::$_guest_transient,
			'display'  => __( 'Every 3 Minutes', 'learnpress' )
		);

		return $schedules;
	}

	/**
	 * Register schedule event to cleanup data of temp users who have took quiz
	 */
	public static function register_event() {
		if ( ! wp_next_scheduled( 'learn_press_schedule_cleanup_temp_users' ) ) {
			wp_schedule_event( time(), 'every_three_minutes', 'learn_press_schedule_cleanup_temp_users' );
		}
	}

	/**
	 * Clear schedule event while deactivating LP
	 */
	public static function deregister_event() {
		wp_clear_scheduled_hook( 'learn_press_schedule_cleanup_temp_users' );
	}

	/**
	 * Call this function hourly
	 */
	public static function schedule_cleanup_temp_users() {
		LP_Debug::instance()->add( __FUNCTION__ );
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT user_id
			FROM {$wpdb->prefix}learnpress_user_items c
			INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta d ON c.user_item_id = d.learnpress_user_item_id AND d.meta_key = %s
			INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta e ON c.user_item_id = e.learnpress_user_item_id AND e.meta_key = %s AND e.meta_value < TIMESTAMPADD(SECOND, %d, NOW())
		", 'temp_user_id', 'temp_user_time', self::$_guest_transient );

		if ( $uids = $wpdb->get_col( $query ) ) {
			$query = $wpdb->prepare( "
				DELETE a.*, b.*
				FROM {$wpdb->prefix}learnpress_user_items a
				INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta b
				WHERE %d
				AND a.user_item_id = b.learnpress_user_item_id
				AND a.user_id IN (" . join( ',', $uids ) . ")
			", 1 );
			$wpdb->query( $query );
		}
	}

	/**
	 * Generate unique id for anonymous user
	 */
	public static function generate_guest_id() {
		$id = self::get_guest_id();
		if ( ! $id ) {
			$id = time();
			if ( ! is_user_logged_in() ) {
				learn_press_setcookie( 'learn_press_user_guest_id', $id, time() + self::$_guest_transient );
				set_transient( 'learn_press_user_guest_' . $id, $id, self::$_guest_transient );
			}
		}

		return $id;
	}

	public static function get_guest_id() {
		return empty( $_COOKIE['learn_press_user_guest_id'] ) ? false : $_COOKIE['learn_press_user_guest_id'];
	}

	/**
	 * @param      $the_user
	 * @param bool $force
	 *
	 * @return LP_Abstract_User
	 */
	public static function get_user( $the_user, $force = false ) {
		$the_id = 0;
		if ( is_numeric( $the_user ) ) {
			$the_id = $the_user;
		} elseif ( $the_user instanceof LP_Abstract_User ) {
			$the_id = $the_user->id;
		} elseif ( isset( $the_user->ID ) ) {
			$the_id = $the_user->ID;
		} elseif ( null === $the_user ) {
			$the_id = get_current_user_id();
		}

		$user_class = self::get_user_class( $the_id );
		if ( $user_class instanceof LP_User_Guest ) {
			$the_id = self::get_guest_id();
		}
		if ( empty( self::$_users[ $the_id ] ) || $force ) {
			self::$_users[ $the_id ] = new $user_class( $the_id );
		}

		return self::$_users[ $the_id ];
	}

	/**
	 * Get class name for User Object
	 *
	 * @param int
	 *
	 * @return mixed|void
	 */
	public static function get_user_class( $the_id = 0 ) {
		$deleted     = in_array( $the_id, self::$_deleted_users );
		$exists_user = ! $deleted ? get_userdata( $the_id ) : false;
		if ( $exists_user ) {
			$class = 'LP_User';
		} else {
			if ( ! $deleted ) {
				self::$_deleted_users[] = $the_id;
				/**
				 * Prevent loading user does not exists in database
				 */
				$user = new LP_User_Guest( $the_id );
				wp_cache_add( $the_id, $user, 'users' );
				wp_cache_add( '', $the_id, 'userlogins' );
				wp_cache_add( '', $the_id, 'useremail' );
				wp_cache_add( '', $the_id, 'userslugs' );
			}
			$is_logged_in = function_exists( 'is_user_logged_in' ) && is_user_logged_in();
			$class        = $is_logged_in ? 'LP_User' : 'LP_User_Guest';
		}

		return apply_filters( 'learn_press_user_class', $class );
	}

	/**
	 * Clear temp data for guest user
	 *
	 * @param $user_login
	 */
	public static function clear_temp_user_data( $user_login ) {
		if ( $temp_id = self::get_guest_id() ) {
			learn_press_remove_cookie( 'learn_press_user_guest_id' );
			delete_transient( 'learn_press_user_guest_' . $temp_id );

			global $wpdb;

			$query = $wpdb->prepare( "
				SELECT user_item_id
				FROM {$wpdb->prefix}learnpress_user_items a
				INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta b ON a.user_item_id = b.learnpress_user_item_id AND b.meta_key = %s And b.meta_value = %s
			", 'temp_user_id', 'yes' );

			$user_item_ids = $wpdb->get_row( $query );
			if ( $user_item_ids ) {
				$query = $wpdb->prepare( "
					DELETE a.*, b.*
					FROM {$wpdb->prefix}learnpress_user_items a
					INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta b
					WHERE a.user_item_id = b.learnpress_user_item_id
					AND a.user_id = %d
				", $temp_id );
				$wpdb->query( $query );
			}
		}
	}

	/**
	 * @param $item
	 * @param $quiz_id
	 * @param $course_id
	 * @param $user_id
	 */
	public static function start_quiz( $item, $quiz_id, $course_id, $user_id ) {
		self::_update_user_item_meta( $item, $quiz_id, $course_id, $user_id );
	}

	/**
	 * @param $item
	 * @param $quiz_id
	 * @param $course_id
	 * @param $user_id
	 */
	public static function retake_quiz( $item, $quiz_id, $course_id, $user_id ) {
		self::_update_user_item_meta( $item, $quiz_id, $course_id, $user_id );
	}

	/**
	 * @param $item
	 * @param $quiz_id
	 * @param $course_id
	 * @param $user_id
	 */
	private static function _update_user_item_meta( $item, $quiz_id, $course_id, $user_id ) {
		if ( get_user_by( 'id', $user_id ) ) {
			return;
		}
		if ( ! $item ) {
			return;
		}
		$item_id = ! empty( $item->user_item_id ) ? $item->user_item_id : $item->history_id;
		learn_press_add_user_item_meta( $item_id, 'temp_user_id', 'yes' );
		learn_press_add_user_item_meta( $item_id, 'temp_user_time', date( 'Y-m-d H:i:s', time() ) );
	}
}

LP_User_Factory::init();

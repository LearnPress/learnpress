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
	 * @var LP_Background_Clear_Temp_Users
	 */
	//protected static $_background_clear_users = null;
	
	/**
	 *
	 */
	public static function init() {
		self::$_guest_transient = WEEK_IN_SECONDS;
		add_action( 'wp_login', array( __CLASS__, 'clear_temp_user_data' ) );
		add_action( 'learn-press/user/quiz-started', array( __CLASS__, 'start_quiz' ), 10, 3 );
		add_action( 'learn_press_user_retake_quiz', array( __CLASS__, 'retake_quiz' ), 10, 4 );
		add_action( 'learn_press_activate', array( __CLASS__, 'register_event' ), 15 );
		add_action( 'learn_press_deactivate', array( __CLASS__, 'deregister_event' ), 15 );
		add_action( 'learn_press_schedule_cleanup_temp_users', array( __CLASS__, 'schedule_cleanup_temp_users' ) );
		add_filter( 'cron_schedules', array( __CLASS__, 'cron_schedules' ) );
		///add_action( 'init', array( __CLASS__, 'clear_temp_users' ) );
		
		/**
		 * Filters into wp users manager
		 */
		add_filter( 'users_list_table_query_args', array( __CLASS__, 'exclude_temp_users' ) );
		
		add_action( 'learn-press/order/status-changed', array( __CLASS__, 'update_user_items' ), 10, 3 );
		add_action( 'learn-press/deleted-order-item', array( __CLASS__, 'delete_user_item' ), 10, 2 );
	}
	
	public static function clear_temp_users() {
		global $wpdb;
		if ( $users = learn_press_get_temp_users() ) {
			LP()->background( 'clear-temp-users' )->push_to_queue(
					array(
							'action' => 'clear_temp_users',
							'users'  => $users
					)
					);
		}
	}
	
	/**
	 * Delete user course from user_items table after an order item is deleted.
	 *
	 * @param int $item_id
	 * @param int $order_id
	 */
	public static function delete_user_item( $item_id, $order_id ) {
		$curd = new LP_User_CURD();
		
		$order = learn_press_get_order( $order_id );
		if ( $order ) {
			$course_id = learn_press_get_order_item_meta( $item_id, '_course_id' );
			$users     = $order->get_users();
			
			if ( $users ) {
				foreach ( $users as $user_id ) {
					$curd->delete_user_item( array(
							'item_id' => $course_id,
							'ref_id'  => $order_id,
							'user_id' => $user_id
					) );
				}
			}
		}
		
	}
	
	public static function update_user_items( $the_id, $old_status, $new_status ) {
		if ( ! $order = learn_press_get_order( $the_id ) ) {
			return;
		}
		remove_action( 'learn-press/order/status-changed', array( __CLASS__, 'update_user_items' ), 10 );
		//LP_Debug::startTransaction();
		try {
			switch ( $new_status ) {
				case 'pending':
				case 'processing':
				case 'cancelled':
				case 'failed':
					self::_update_user_item_pending( $order, $old_status, $new_status );
					break;
				case'completed':
					self::_update_user_item_purchased( $order, $old_status, $new_status );
			}
			//LP_Debug::commitTransaction();
		}
		catch ( Exception $ex ) {
			//LP_Debug::rollbackTransaction();
		}
		add_action( 'learn-press/order/status-changed', array( __CLASS__, 'update_user_items' ), 10, 3 );
	}
	
	/**
	 * @param LP_Order $order
	 * @param string   $old_status
	 * @param string   $new_status
	 */
	protected static function _update_user_item_pending( $order, $old_status, $new_status ) {
		$curd  = new LP_User_CURD();
		$items = $order->get_items();
		if ( ! $items ) {
			return;
		}
		$order_id = $order->get_id();
		foreach ( $order->get_users() as $user_id ) {
			foreach ( $items as $item ) {
				$item = $curd->get_user_item(
						$user_id,
						$item['course_id']
						);
				if ( $item && intval($item['ref_id'])== intval($order_id) ) {
					if ( is_array( $item ) ) {
						$item_id = $item['user_item_id'];
					} else {
						$item_id = $item;
					}
					$curd->update_user_item_status( $item_id, $new_status );
				}
			}
		}
	}
	
	/**
	 * @param LP_Order $order
	 * @param string   $old_status
	 * @param string   $new_status
	 */
	protected static function _update_user_item_purchased( $order, $old_status, $new_status ) {
		global $wpdb;
		$curd         = new LP_User_CURD();
		$parent_order = $order->is_child() ? $order->get_parent() : $order;
		$items        = ! $order->is_child() ? $order->get_items() : $parent_order->get_items();
		
		if ( ! $items ) {
			return;
		}
		
		if ( $order->is_multi_users() && ! $order->is_child() ) {
			return;
		}
		
		foreach ( $order->get_users() as $user_id ) {
			
			foreach ( $items as $item ) {
				
				if ( $user_item_id = self::_get_course_item( $order->get_id(), $item['course_id'], $user_id ) ) {
					$user_item_id = $curd->update_user_item(
							$user_id,
							$item['course_id'],
							array(
									'ref_id'    => $order->get_id(),
									'ref_type'  => LP_ORDER_CPT,
									'parent_id' => 0
							)
							);
				} else {
					$wpdb->insert(
							$wpdb->learnpress_user_items,
							array(
									'item_id'   => $item['course_id'],
									'ref_id'    => $order->get_id(),
									'ref_type'  => LP_ORDER_CPT,
									'user_id'   => $user_id,
									'item_type' => LP_COURSE_CPT
							)
							);
					$user_item_id = $wpdb->insert_id;
				}
				
				if ( $user_item_id ) {
					$item        = $curd->get_user_item_by_id( $user_item_id );
					$last_status = $curd->get_user_item_meta( $user_item_id, '_last_status' );
					$args        = array( 'status' => $last_status );
					$user 		= learn_press_get_user($user_id);
					$course_id 	= $item['item_id'];
					$can_enroll 	= $user->can_enroll_course($course_id);
					$auto_enroll 	= LP()->settings->get( 'auto_enroll' ) == 'yes';
					if ( $new_status == 'completed' && $can_enroll && $auto_enroll) {
						$args['status'] = 'enrolled';
					}
					if ( ! $last_status ) {
						$args['status'] = $auto_enroll && $can_enroll ? 'enrolled' : 'purchased';
						if ( 'enrolled' == $args['status'] ) {
							$time                   = new LP_Datetime();
							$args['start_time']     = $time->toSql();
							$args['start_time_gmt'] = $time->toSql( false );
						}
					}
					
					$curd->update_user_item_by_id( $user_item_id, $args );
				}
			}
		}
		
	}
	
	protected static function _get_course_item( $order_id, $course_id, $user_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE ref_id = %d
				AND ref_type = %s
				AND item_id = %d
				AND user_id = %d
		", $order_id, LP_ORDER_CPT, $course_id, $user_id );
		
		return $wpdb->get_var( $query );
	}
	
	/**
	 * Hook into wp users list to exclude our temp users.
	 *
	 * @param array $args
	 *
	 * @return mixed
	 */
	public static function exclude_temp_users( $args ) {
		//$args['exclude'] = self::_get_temp_user_ids();
		if ( LP_Request::get_string( 'lp-action' ) == 'pending-request' ) {
			$args['include'] = self::get_pending_requests();
		}
		
		return $args;
	}
	
	public static function get_pending_requests() {
		if ( false === ( $pending_requests = LP_Object_Cache::get( 'pending-requests', 'lp-users' ) ) ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s
				WHERE um.meta_value = %s
			", '_requested_become_teacher', 'yes' );
			
			$pending_requests = $wpdb->get_col( $query );
			LP_Object_Cache::set( 'pending-requests', $pending_requests, 'lp-users' );
		}
		
		return $pending_requests;
	}
	
	/**
	 * Query all ids of temp users has created.
	 *
	 * @return array
	 */
	protected static function _get_temp_user_ids() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s AND um.meta_value = %s
			LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = %s
		", '_lp_temp_user', 'yes', '_lp_expiration' );
		
		return $wpdb->get_col( $query );
	}
	
	/**
	 * Get temp user for some purpose.
	 * This function will create a temp user in users table.
	 * This user is not a REAL user but have all functions
	 * as a real user should have.
	 * Usually, use this user in case real user is not logged
	 * in and we need an user for some purpose such as:
	 * do a quiz , etc...
	 *
	 * @return LP_User|LP_User_Guest
	 */
	public static function get_temp_user() {
		global $wpdb;
		
		$id = LP()->session->get( 'temp_user' );
		
		// If temp user is not set or is not exists
		if ( ! $id || ! get_user_by( 'id', $id ) ) {
			
			// Force to enable cookie
			LP()->session->set_customer_session_cookie( true );
			
			// Find temp user is not used
			$query = $wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s AND um.meta_value = %s
				LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = %s
				WHERE um2.meta_value IS NULL
				LIMIT 0, 1
			", '_lp_temp_user', 'yes', '_lp_expiration' );
			
			$id = $wpdb->get_var( $query );
			
			// If there is no user, create one
			if ( ! $id ) {
				$username = 'user_' . date( 'YmdHis' );
				$email    = sprintf( '%s@%s', $username, $_SERVER['HTTP_HOST'] );
				if ( ! preg_match( '~\.([a-zA-Z])$~', $email ) ) {
					$email .= '.com';
				}
				$thing = wp_create_user( $username, 'test', $email );
				if ( ! is_wp_error( $thing ) ) {
					$id = $thing;
					update_user_meta( $id, '_lp_temp_user', 'yes' );
				} else {
				}
			}
			
			if ( $id ) {
				
				// Set session and temp user expiration time
				update_user_meta( $id, '_lp_expiration', time() + DAY_IN_SECONDS * 2 );
				
				//
				delete_user_meta( $id, 'wp_capabilities' );
				
				// Set session
				LP()->session->set( 'temp_user', $id );
			}
		}
		
		if ( $id ) {
			$expiring = get_user_meta( $id, '_lp_expiration', true );
			// Update new expiration time if time is going to reach
			if ( time() > $expiring - HOUR_IN_SECONDS ) {
				update_user_meta( $id, '_lp_expiration', time() + DAY_IN_SECONDS * 2 );
			}
		}
		
		return new LP_User_Guest( $id );
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
		return LP()->session->guest_user_id;// empty( $_COOKIE['learn_press_user_guest_id'] ) ? false : $_COOKIE['learn_press_user_guest_id'];
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
	 * @return string
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
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param int $user_id
	 */
	public static function start_quiz( $quiz_id, $course_id, $user_id ) {
		
		if ( $user = learn_press_get_user( $user_id ) ) {
			if ( $item = $user->get_item_data( $quiz_id, $course_id ) ) {
				self::_update_user_item_meta( $item, $quiz_id, $course_id, $user_id );
			}
		}
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
	 * @param LP_User_Item $item
	 * @param int          $quiz_id
	 * @param int          $course_id
	 * @param int          $user_id
	 */
	private static function _update_user_item_meta( $item, $quiz_id, $course_id, $user_id ) {
		if ( get_user_by( 'id', $user_id ) ) {
			return;
		}
		
		if ( ! $item ) {
			return;
		}
		
		learn_press_add_user_item_meta( $item->get_user_item_id(), 'temp_user_id', 'yes' );
		learn_press_add_user_item_meta( $item->get_user_item_id(), 'temp_user_time', date( 'Y-m-d H:i:s', time() ) );
	}
}

LP_User_Factory::init();

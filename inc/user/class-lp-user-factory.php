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
	 * Init hooks
	 */
	public static function init() {
		self::$_guest_transient = WEEK_IN_SECONDS;
		add_action( 'learn-press/user/quiz-started', array( __CLASS__, 'start_quiz' ), 10, 3 );
		add_action( 'learn_press_activate', array( __CLASS__, 'register_event' ), 15 );

		/**
		 * Filters into wp users manager
		 */
		add_filter( 'users_list_table_query_args', array( __CLASS__, 'exclude_temp_users' ) );

		add_action( 'learn-press/order/status-changed', array( __CLASS__, 'update_user_items' ), 10, 3 );
	}

	/**
	 * Handle when order changed status
	 *
	 * @param $the_id
	 * @param $old_status
	 * @param $new_status
	 * @Todo tungnx - should write on class LP_Order
	 */
	public static function update_user_items( $the_id, $old_status, $new_status ) {
		$order = learn_press_get_order( $the_id );

		if ( ! $order ) {
			return;
		}

		try {
			switch ( $new_status ) {
				case 'pending':
				case 'processing':
				case 'cancelled':
				case 'failed':
					self::_update_user_item_order_pending( $order, $old_status, $new_status );
					break;
				case 'completed':
					self::_update_user_item_order_completed( $order, $old_status, $new_status );
					break;
			}
		} catch ( Exception $ex ) {
			error_log( __METHOD__ . ': ' . $ex->getMessage() );
		}
	}

	/**
	 * Update lp_user_items has Order
	 *
	 * @param LP_Order $order
	 * @param string $old_status
	 * @param string $new_status
	 *
	 * @throws Exception
	 * @author Nhamdv <email@email.com>
	 * @editor tungnx
	 * @modify 4.1.4
	 * @version 1.0.1
	 */
	protected static function _update_user_item_order_pending( $order, $old_status, $new_status ) {
		$items            = $order->get_items();
		$lp_order_db      = LP_Order_DB::getInstance();
		$lp_user_items_db = LP_User_Items_DB::getInstance();

		if ( ! $items ) {
			return;
		}

		foreach ( $order->get_users() as $user_id ) {
			foreach ( $items as $item ) {
				if ( ! isset( $item['course_id'] ) ) {
					continue;
				}

				$course_id = $item['course_id'];

				// Check this order is the latest by user and course_id
				$last_order_id = $lp_order_db->get_last_lp_order_id_of_user_course( $user_id, $course_id );
				if ( $last_order_id && $last_order_id != $order->get_id() ) {
					continue;
				}

				$lp_user_items_db->delete_user_items_old( $user_id, $course_id );
			}
		}
	}

	/**
	 * Enroll course if Order completed
	 *
	 * @param LP_Order $order
	 * @param string $old_status
	 * @param string $new_status
	 * @throws Exception
	 * @editor tungnx
	 * @modify 4.1.2
	 * @version 1.0.2
	 */
	protected static function _update_user_item_order_completed( LP_Order $order, string $old_status, string $new_status ) {
		$lp_order_db = LP_Order_DB::getInstance();
		$items       = $order->get_items();

		if ( ! $items ) {
			return;
		}

		$created_via = $order->get_created_via();

		foreach ( $order->get_users() as $user_id ) {
			$user = learn_press_get_user( $user_id );

			foreach ( $items as $item ) {
				if ( ! isset( $item['course_id'] ) || get_post_type( $item['course_id'] ) !== LP_COURSE_CPT ) {
					continue;
				}

				$course_id = $item['course_id'];

				// Check this order is the latest by user and course_id
				$last_order_id = $lp_order_db->get_last_lp_order_id_of_user_course( $user_id, $course_id );
				if ( $last_order_id && $last_order_id != $order->get_id() ) {
					continue;
				}

				if ( 'manual' === $created_via ) {
					self::handle_item_manual_order_completed( $order, $user, $item );
				} else {
					self::handle_item_order_completed( $order, $user, $item );
				}
			}
		}
	}

	/**
	 * Handle something when Order completed
	 *
	 * @author  tungnx
	 * @since   4.1.3
	 * @version 1.0.2
	 */
	protected static function handle_item_order_completed( LP_Order $order, LP_User $user, $item ) {
		$lp_user_items_db = LP_User_Items_DB::getInstance();

		try {
			$course      = learn_press_get_course( $item['course_id'] );
			$auto_enroll = LP_Settings::is_auto_start_course();

			$user_id = $user->get_id();
			if ( $user instanceof LP_User_Guest ) {
				$user_id = 0;
			}

			/** Get the newest user_item_id of course for allow_repurchase */
			$filter          = new LP_User_Items_Filter();
			$filter->user_id = $user_id;
			$filter->item_id = $item['course_id'];
			$user_course     = $lp_user_items_db->get_last_user_course( $filter );

			$latest_user_item_id   = 0;
			$allow_repurchase_type = '';

			// Data user_item for save database
			$user_item_data = [
				'user_id' => $user_id,
				'item_id' => $course->get_id(),
				'ref_id'  => $order->get_id(),
			];

			if ( ! $user instanceof LP_User_Guest && $user_course && isset( $user_course->user_item_id ) ) {
				$latest_user_item_id = $user_course->user_item_id;

				/** Get allow_repurchase_type for reset, update. Add in: rest-api/v1/frontend/class-lp-courses-controller.php: purchase_course */
				$allow_repurchase_type = learn_press_get_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type' );
			}

			// If > 1 time purchase same course and allow repurchase
			if ( ! empty( $allow_repurchase_type ) && $course->allow_repurchase() && ! empty( $latest_user_item_id ) && ! $course->is_free() ) {
				/**
				 * If keep course progress will reset start_time, end_time, status, graduation
				 * where user_item_id = $latest_user_item_id
				 */
				if ( $allow_repurchase_type === 'keep' ) {
					// Set data for update user item
					$user_item_data['user_item_id'] = $latest_user_item_id;
					$user_item_data['start_time']   = current_time( 'mysql', true );
					$user_item_data['end_time']     = null;
					$user_item_data['status']       = LP_COURSE_ENROLLED;
					$user_item_data['graduation']   = LP_COURSE_GRADUATION_IN_PROGRESS;

					do_action( 'lp/allow_repurchase_options/continue/db/update', $user_item_data, $latest_user_item_id );
				} elseif ( $allow_repurchase_type === 'reset' ) {
					if ( $auto_enroll ) {
						$user_item_data['status']     = LP_COURSE_ENROLLED;
						$user_item_data['graduation'] = LP_COURSE_GRADUATION_IN_PROGRESS;
					} else {
						$user_item_data['status'] = LP_COURSE_PURCHASED;
					}
				}

				learn_press_delete_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type' );
			} elseif ( ! $course->is_free() ) { // First purchase course
				// Set data for create user_item
				if ( $auto_enroll ) {
					$user_item_data['status']     = LP_COURSE_ENROLLED;
					$user_item_data['graduation'] = LP_COURSE_GRADUATION_IN_PROGRESS;
				} else {
					$user_item_data['status'] = LP_COURSE_PURCHASED;
				}
			} else { // Enroll course free
				// Set data for create user_item
				$user_item_data['status']     = LP_COURSE_ENROLLED;
				$user_item_data['graduation'] = LP_COURSE_GRADUATION_IN_PROGRESS;
			}

			$user_item_new_or_update = new LP_User_Item_Course( $user_item_data );
			$result                  = $user_item_new_or_update->update();

			if ( $result && isset( $user_item_data['status'] ) && LP_COURSE_ENROLLED == $user_item_data['status'] ) {
				do_action( 'learnpress/user/course-enrolled', $order->get_id(), $user_item_data['item_id'], $user_item_data['user_id'] );
			}
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Handle something when Manual Order completed
	 *
	 * @author tungnx
	 * @since 4.1.3
	 * @version 1.0.1
	 */
	protected static function handle_item_manual_order_completed( LP_Order $order, LP_User $user, $item ) {
		try {
			$course      = learn_press_get_course( $item['course_id'] );
			$auto_enroll = LP_Settings::is_auto_start_course();

			// Data user_item for save database
			$user_item_data = [
				'user_id' => $user->get_id(),
				'item_id' => $course->get_id(),
				'ref_id'  => $order->get_id(),
			];

			if ( $auto_enroll ) {
				$user_item_data['status']     = LP_COURSE_ENROLLED;
				$user_item_data['graduation'] = LP_COURSE_GRADUATION_IN_PROGRESS;
			} else {
				$user_item_data['status'] = LP_COURSE_PURCHASED;
			}

			$user_item_data = apply_filters( 'learnpress/lp_order/item/handle_item_manual_order_completed', $user_item_data, $order, $user, $course, $item );

			// Delete lp_user_items old
			LP_User_Items_DB::getInstance()->delete_user_items_old( $user->get_id(), $course->get_id() );
			// End

			if ( isset( $user_item_data['status'] ) ) {
				$user_item_new = new LP_User_Item_Course( $user_item_data );
				$result        = $user_item_new->update();

				if ( $result && LP_COURSE_ENROLLED == $user_item_data['status'] ) {
					do_action( 'learnpress/user/course-enrolled', $order->get_id(), $user_item_data['item_id'], $user_item_data['user_id'] );
				}
			}
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Hook into wp users list to exclude our temp users.
	 *
	 * @param array $args
	 *
	 * @return mixed
	 */
	public static function exclude_temp_users( $args ) {
		if ( LP_Request::get_string( 'lp-action' ) == 'pending-request' ) {
			$args['include'] = self::get_pending_requests();
		}

		return $args;
	}

	public static function get_pending_requests() {
		if ( false === ( $pending_requests = LP_Object_Cache::get( 'pending-requests', 'lp-users' ) ) ) {
			global $wpdb;
			$query = $wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->usermeta} um ON um.user_id = u.ID AND um.meta_key = %s
				WHERE um.meta_value = %s
			",
				'_requested_become_teacher',
				'yes'
			);

			$pending_requests = $wpdb->get_col( $query );
			LP_Object_Cache::set( 'pending-requests', $pending_requests, 'lp-users' );
		}

		return $pending_requests;
	}

	public static function get_guest_id() {
		return 0;// empty( $_COOKIE['learn_press_user_guest_id'] ) ? false : $_COOKIE['learn_press_user_guest_id'];
	}

	/**
	 * @param      $the_user
	 * @param bool     $force
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
	 * @param int $quiz_id
	 * @param int $course_id
	 * @param int $user_id
	 */
	public static function start_quiz( $quiz_id, $course_id, $user_id ) {
		if ( learn_press_get_user( $user_id ) ) {
			$user = learn_press_get_user( $user_id );
			if ( $user->get_item_data( $quiz_id, $course_id ) ) {
				self::_update_user_item_meta( $user->get_item_data( $quiz_id, $course_id ), $quiz_id, $course_id, $user_id );
			}
		}
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
		learn_press_add_user_item_meta( $item->get_user_item_id(), 'temp_user_time', gmdate( 'Y-m-d H:i:s', time() ) );
	}
}

LP_User_Factory::init();

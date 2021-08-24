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
		// add_action( 'wp_login', array( __CLASS__, 'clear_temp_user_data' ) );
		add_action( 'learn-press/user/quiz-started', array( __CLASS__, 'start_quiz' ), 10, 3 );
		add_action( 'learn_press_activate', array( __CLASS__, 'register_event' ), 15 );
		//add_action( 'learn_press_deactivate', array( __CLASS__, 'deregister_event' ), 15 );
		//add_action( 'learn_press_schedule_cleanup_temp_users', array( __CLASS__, 'schedule_cleanup_temp_users' ) );

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
		//remove_action( 'learn-press/order/status-changed', array( __CLASS__, 'update_user_items' ), 10 );

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
		//add_action( 'learn-press/order/status-changed', array( __CLASS__, 'update_user_items' ), 10, 3 );
	}

	/**
	 * @param LP_Order $order
	 * @param string   $old_status
	 * @param string   $new_status
	 *
	 * @author Nhamdv <email@email.com>
	 */
	protected static function _update_user_item_order_pending( $order, $old_status, $new_status ) {
		$curd  = new LP_User_CURD();
		$items = $order->get_items();

		if ( ! $items ) {
			return;
		}

		global $wpdb;

		foreach ( $order->get_users() as $user_id ) {
			foreach ( $items as $item ) {
				if ( ! isset( $item['course_id'] ) ) {
					continue;
				}

				$user_item_id = LP_Course_DB::getInstance()->get_user_item_id( $order->get_id(), $item['course_id'], $user_id );

				if ( $user_item_id ) {
					//Todo: tungnx - handle code on background
					$wpdb->delete(
						$wpdb->learnpress_user_items,
						array(
							'user_item_id' => $user_item_id,
						),
						array( '%d' )
					);

					// DELETE user_itemmeta by user_item_id.
					$wpdb->delete(
						$wpdb->learnpress_user_itemmeta,
						array(
							'learnpress_user_item_id' => absint( $user_item_id ),
						),
						array( '%d' )
					);

					// DELETE user_item_result
					LP_User_Items_Result_DB::instance()->delete( absint( $user_item_id ) );

					// DELETE Lesson, Quiz in Course.
					$wpdb->delete(
						$wpdb->learnpress_user_items,
						array(
							'parent_id' => $user_item_id,
						),
						array( '%d' )
					);

					$child_user_item_ids = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT user_item_id FROM $wpdb->learnpress_user_items
							WHERE parent_id=%d",
							$user_item_id
						)
					);

					// DELETE user_itemmeta for ( lesson, quiz... )
					if ( ! empty( $child_user_item_ids ) ) {
						foreach ( $child_user_item_ids as $child_user_item_id ) {
							$wpdb->delete(
								$wpdb->learnpress_user_itemmeta,
								array(
									'learnpress_user_item_id' => absint( $child_user_item_id ),
								),
								array( '%d' )
							);

							LP_User_Items_Result_DB::instance()->delete( absint( $child_user_item_id ) );
						}
					}
				}
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
	 */
	//  protected static function _update_user_item_purchased( LP_Order $order, $old_status, $new_status ) {
	//      global $wpdb;
	//      $lp_user_items_db = LP_User_Items_DB::getInstance();
	//
	//      $curd  = new LP_User_CURD();
	//      $items = $order->get_items();
	//      //$parent_order = $order->is_child() ? $order->get_parent() : $order;
	//      //$items        = ! $order->is_child() ? $order->get_items() : $parent_order->get_items();
	//
	//      if ( ! $items ) {
	//          return;
	//      }
	//
	//      /*if ( $order->is_multi_users() && ! $order->is_child() ) {
	//          return;
	//      }*/
	//
	//      foreach ( $order->get_users() as $user_id ) {
	//          $user = learn_press_get_user( $user_id );
	//
	//          foreach ( $items as $item ) {
	//              if ( ! isset( $item['course_id'] ) || get_post_type( $item['course_id'] ) !== LP_COURSE_CPT ) {
	//                  continue;
	//              }
	//
	//              $user_item_id = LP_Course_DB::getInstance()->get_user_item_id( $order->get_id(), $item['course_id'], $user_id );
	//
	//              if ( $user_item_id ) {
	//                  continue;
	//              }
	//
	//              $course = learn_press_get_course( $item['course_id'] );
	//
	//              /** Get newest user_item_id of course for allow_repurchase */
	//              $filter          = new LP_User_Items_Filter();
	//              $filter->user_id = $user_id;
	//              $filter->item_id = $item['course_id'];
	//              $user_course     = $lp_user_items_db->get_last_user_course( $filter );
	//
	//              $latest_user_item_id   = 0;
	//              $allow_repurchase_type = '';
	//
	//              if ( $user_course && isset( $user_course->user_item_id ) ) {
	//                  $latest_user_item_id = $user_course->user_item_id;
	//
	//                  /** Get allow_repurchase_type for reset, update. Add in: rest-api/v1/frontend/class-lp-courses-controller.php: purchase_course */
	//                  $allow_repurchase_type = learn_press_get_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type' );
	//              }
	//
	//              /*$latest_user_item_id = $wpdb->get_var(
	//                  $wpdb->prepare(
	//                      "SELECT MAX(user_item_id) user_item_id
	//                    FROM {$wpdb->learnpress_user_items}
	//                    WHERE ref_type = %s
	//                    AND item_type = %s
	//                    AND item_id = %d
	//                    AND user_id = %d",
	//                      LP_ORDER_CPT,
	//                      LP_COURSE_CPT,
	//                      $item['course_id'],
	//                      $user_id
	//                  )
	//              );*/
	//
	//              /**
	//               * If allow_repurchase.
	//               */
	//              if ( $course->allow_repurchase() && ! empty( $latest_user_item_id ) && ! $course->is_free() && ! empty( $allow_repurchase_type ) ) {
	//
	//                  /** If keep course progress will reset start_time, graduation.. */
	//                  if ( $allow_repurchase_type === 'keep' ) {
	//                      do_action( 'lp/allow_repurchase_options/continue/db/update', $latest_user_item_id );
	//
	//                      /**
	//                       * Update latest user_item_id to continue course progress.
	//                       *
	//                       * @author Nhamdv.
	//                       */
	//                      $update = $wpdb->update(
	//                          $wpdb->learnpress_user_items,
	//                          array(
	//                              'ref_id'     => $order->get_id(),
	//                              'start_time' => current_time( 'mysql', true ),
	//                              'end_time'   => null,
	//                              'graduation' => 'in-progress',
	//                              'status'     => LP_COURSE_ENROLLED,
	//                          ),
	//                          array(
	//                              'user_item_id' => $latest_user_item_id,
	//                          ),
	//                          array( '%d', '%s', '%s', '%s', '%s' ),
	//                          array( '%d' )
	//                      );
	//
	//                      if ( $update ) {
	//                          $user_item_id = false;
	//                      }
	//                  } elseif ( $allow_repurchase_type === 'reset' ) {
	//                      /** Delete user_item_id in table learnpress_user_items */
	//                      $wpdb->delete(
	//                          $wpdb->learnpress_user_items,
	//                          array(
	//                              'user_item_id' => $latest_user_item_id,
	//                          ),
	//                          array( '%d' )
	//                      );
	//
	//                      /** Get list user_item_id for lesson, quiz... by course user_item_id in table learnpress_user_items */
	//                      $user_item_ids = $wpdb->get_col(
	//                          $wpdb->prepare(
	//                              "SELECT user_item_id FROM $wpdb->learnpress_user_items
	//                              WHERE parent_id=%d
	//                              ",
	//                              $latest_user_item_id
	//                          )
	//                      );
	//
	//                      /** Delete all lesson, quiz... by course parent user_item_id */
	//                      $wpdb->delete(
	//                          $wpdb->learnpress_user_items,
	//                          array(
	//                              'parent_id' => absint( $latest_user_item_id ),
	//                          ),
	//                          array( '%d' )
	//                      );
	//
	//                      /** Delete all user_item_meta for lesson, quiz... */
	//                      if ( ! empty( $user_item_ids ) ) {
	//                          foreach ( $user_item_ids as $user_item_id ) {
	//                              $wpdb->delete(
	//                                  $wpdb->learnpress_user_itemmeta,
	//                                  array(
	//                                      'learnpress_user_item_id' => absint( $user_item_id ),
	//                                  ),
	//                                  array( '%d' )
	//                              );
	//
	//                              LP_User_Items_Result_DB::instance()->delete( absint( $user_item_id ) );
	//                          }
	//                      }
	//
	//                      /**
	//                       * Insert new item(row) on table learnpress_user_items
	//                       */
	//                      $user_item_id = $user->enroll_course( $item['course_id'], $order->get_id() );
	//                  }
	//
	//                  learn_press_delete_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type' );
	//              } else {
	//                  /**
	//                   * Insert new item(row) on table learnpress_user_items
	//                   */
	//                  $user_item_id = $user->enroll_course( $item['course_id'], $order->get_id() );
	//              }
	//
	//              if ( $user_item_id && ! is_wp_error( $user_item_id ) ) {
	//                  // $last_status = $curd->get_user_item_meta( $user_item_id, '_last_status' );
	//                  $item        = $curd->get_user_item_by_id( $user_item_id );
	//                  $course_id   = $item['item_id'];
	//                  $can_enroll  = $user->can_enroll_course( $course_id );
	//                  $auto_enroll = LP()->settings()->get( 'auto_enroll' ) == 'yes';
	//                  $course      = learn_press_get_course( $course_id );
	//
	//                  $args = array(
	//                      'status'     => LP_COURSE_PURCHASED,
	//                      'start_time' => current_time( 'mysql', true ),
	//                  );
	//
	//                  if ( $new_status == 'completed' && $can_enroll && $auto_enroll ) {
	//                      $args['status'] = LP_COURSE_ENROLLED;
	//                      // Send mail when course enrolled
	//                      // $user->enrolled_sendmail( $user_id, $course_id );
	//                  }
	//
	//                  if ( $course->is_free() && $can_enroll ) {
	//                      $args['status'] = LP_COURSE_ENROLLED;
	//                  }
	//
	//                  if ( $args['status'] === LP_COURSE_PURCHASED ) {
	//                      $args['start_time'] = null;
	//                  }
	//
	//                  $curd->update_user_item_by_id( $user_item_id, $args );
	//              }
	//          }
	//      }
	//
	//  }

	/**
	 * Enroll course if Order completed
	 *
	 * @param LP_Order $order
	 * @param string $old_status
	 * @param string $new_status
	 * @throws Exception
	 * @editor tungnx
	 * @modify 4.1.2
	 */
	protected static function _update_user_item_order_completed( LP_Order $order, string $old_status, string $new_status ) {
		$items = $order->get_items();

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

				$user_item_id = LP_Course_DB::getInstance()->get_user_item_id( $order->get_id(), $item['course_id'], $user_id );

				if ( $user_item_id ) {
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
	 */
	protected static function handle_item_order_completed( LP_Order $order, LP_User $user, $item ) {
		global $wpdb;
		$lp_user_items_db = LP_User_Items_DB::getInstance();

		try {
			$course      = learn_press_get_course( $item['course_id'] );
			$auto_enroll = LP_Settings::is_auto_start_course();

			/** Get the newest user_item_id of course for allow_repurchase */
			$filter          = new LP_User_Items_Filter();
			$filter->user_id = $user->get_id();
			$filter->item_id = $item['course_id'];
			$user_course     = $lp_user_items_db->get_last_user_course( $filter );

			$latest_user_item_id   = 0;
			$allow_repurchase_type = '';

			// Data user_item for save database
			$user_item_data = [
				'user_id' => $user->get_id(),
				'item_id' => $course->get_id(),
				'ref_id'  => $order->get_id(),
			];

			if ( $user_course && isset( $user_course->user_item_id ) ) {
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
					/** Delete user_item_id in table learnpress_user_items */
					$wpdb->delete(
						$wpdb->learnpress_user_items,
						array(
							'user_item_id' => $latest_user_item_id,
						),
						array( '%d' )
					);

					/** Get list user_item_id for lesson, quiz... by course user_item_id in table learnpress_user_items */
					$user_item_ids = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT user_item_id FROM $wpdb->learnpress_user_items
								WHERE parent_id=%d
								",
							$latest_user_item_id
						)
					);

					/** Delete all lesson, quiz... by course parent user_item_id */
					$wpdb->delete(
						$wpdb->learnpress_user_items,
						array(
							'parent_id' => absint( $latest_user_item_id ),
						),
						array( '%d' )
					);

					/** Delete all user_item_meta for lesson, quiz... */
					if ( ! empty( $user_item_ids ) ) {
						foreach ( $user_item_ids as $user_item_id ) {
							$wpdb->delete(
								$wpdb->learnpress_user_itemmeta,
								array(
									'learnpress_user_item_id' => absint( $user_item_id ),
								),
								array( '%d' )
							);

							LP_User_Items_Result_DB::instance()->delete( absint( $user_item_id ) );
						}
					}

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
			} elseif ( ! $course->is_free() ) {
				$user_item_data['status'] = LP_COURSE_PURCHASED;
			}

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
	 * @editor tungnx
	 * @reason move to LP_Course_DB
	 */
	/*
	protected static function _get_course_item( $order_id, $course_id, $user_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE ref_id = %d
				AND ref_type = %s
				AND item_id = %d
				AND user_id = %d
		",
			$order_id,
			LP_ORDER_CPT,
			$course_id,
			$user_id
		);

		return $wpdb->get_var( $query );
	}*/

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

	/**
	 * Query all ids of temp users has created.
	 *
	 * @return array
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*protected static function _get_temp_user_ids() {
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->users} u
			INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s AND um.meta_value = %s
			LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = %s
		",
			'_lp_temp_user',
			'yes',
			'_lp_expiration'
		);

		return $wpdb->get_col( $query );
	}*/

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
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*public static function get_temp_user() {
		global $wpdb;

		$id = LP()->session->get( 'temp_user' );

		// If temp user is not set or is not exists
		if ( ! $id || ! get_user_by( 'id', $id ) ) {

			// Force to enable cookie
			LP()->session->set_customer_session_cookie( true );

			// Find temp user is not used
			$query = $wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->users} u
				INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id AND um.meta_key = %s AND um.meta_value = %s
				LEFT JOIN {$wpdb->usermeta} um2 ON u.ID = um2.user_id AND um2.meta_key = %s
				WHERE um2.meta_value IS NULL
				LIMIT 0, 1
			",
				'_lp_temp_user',
				'yes',
				'_lp_expiration'
			);

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
	}*/

	/**
	 * Register schedule event to cleanup data of temp users who have took quiz
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*public static function register_event() {
		if ( ! wp_next_scheduled( 'learn_press_schedule_cleanup_temp_users' ) ) {
			wp_schedule_event( time(), 'every_three_minutes', 'learn_press_schedule_cleanup_temp_users' );
		}
	}*/

	/**
	 * Clear schedule event while deactivating LP
	 *
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*public static function deregister_event() {
		wp_clear_scheduled_hook( 'learn_press_schedule_cleanup_temp_users' );
	}*/

	/**
	 * Call this function hourly
	 *
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*public static function schedule_cleanup_temp_users() {
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT user_id
			FROM {$wpdb->prefix}learnpress_user_items c
			INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta d ON c.user_item_id = d.learnpress_user_item_id AND d.meta_key = %s
			INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta e ON c.user_item_id = e.learnpress_user_item_id AND e.meta_key = %s AND e.meta_value < TIMESTAMPADD(SECOND, %d, NOW())
		",
			'temp_user_id',
			'temp_user_time',
			self::$_guest_transient
		);

		if ( $uids = $wpdb->get_col( $query ) ) {
			$query = $wpdb->prepare(
				"
				DELETE a.*, b.*
				FROM {$wpdb->prefix}learnpress_user_items a
				INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta b
				WHERE %d
				AND a.user_item_id = b.learnpress_user_item_id
				AND a.user_id IN (" . join( ',', $uids ) . ')
			',
				1
			);
			$wpdb->query( $query );
		}
	}*/

	/**
	 * Generate unique id for anonymous user
	 */
	/*public static function generate_guest_id() {
		$id = self::get_guest_id();
		if ( ! $id ) {
			$id = time();
			if ( ! is_user_logged_in() ) {
				learn_press_setcookie( 'learn_press_user_guest_id', $id, time() + self::$_guest_transient );
				set_transient( 'learn_press_user_guest_' . $id, $id, self::$_guest_transient );
			}
		}

		return $id;
	}*/

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
	 * Clear temp data for guest user
	 *
	 * @param $user_login
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*public static function clear_temp_user_data( $user_login ) {
		if ( $temp_id = self::get_guest_id() ) {
			learn_press_remove_cookie( 'learn_press_user_guest_id' );
			delete_transient( 'learn_press_user_guest_' . $temp_id );

			global $wpdb;

			$query = $wpdb->prepare(
				"
				SELECT user_item_id
				FROM {$wpdb->prefix}learnpress_user_items a
				INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta b ON a.user_item_id = b.learnpress_user_item_id AND b.meta_key = %s And b.meta_value = %s
			",
				'temp_user_id',
				'yes'
			);

			$user_item_ids = $wpdb->get_row( $query );
			if ( $user_item_ids ) {
				$query = $wpdb->prepare(
					"
					DELETE a.*, b.*
					FROM {$wpdb->prefix}learnpress_user_items a
					INNER JOIN {$wpdb->prefix}learnpress_user_itemmeta b
					WHERE a.user_item_id = b.learnpress_user_item_id
					AND a.user_id = %d
				",
					$temp_id
				);
				$wpdb->query( $query );
			}
		}
	}*/

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

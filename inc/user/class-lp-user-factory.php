<?php

use LearnPress\Background\LPBackgroundAjax;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;

defined( 'ABSPATH' ) || exit;

/**
 * Class LP_User_Factory
 */
class LP_User_Factory {
	/**
	 * Init hooks
	 */
	public static function init() {
		add_action( 'learn-press/order/status-changed', array( __CLASS__, 'update_user_items' ), 10, 3 );
	}

	/**
	 * Handle when order changed status
	 *
	 * @param $the_id
	 * @param $old_status
	 * @param $new_status
	 *
	 * @Todo tungnx - should write on class LP_Order
	 */
	public static function update_user_items( $the_id, $old_status, $new_status ) {
		ini_set( 'max_execution_time', 0 );
		$order = learn_press_get_order( $the_id );
		if ( ! $order ) {
			return;
		}

		try {
			switch ( $new_status ) {
				case LP_ORDER_PENDING:
				case LP_ORDER_PROCESSING:
				case LP_ORDER_CANCELLED:
				case LP_ORDER_FAILED:
					self::_update_user_item_order_pending( $order, $old_status, $new_status );
					break;
				case LP_ORDER_COMPLETED:
					self::_update_user_item_order_completed( $order, $old_status, $new_status );
					break;
			}
		} catch ( Exception $ex ) {
			error_log( __METHOD__ . ': ' . $ex->getMessage() );
		}
		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );
	}

	/**
	 * Update lp_user_items has Order
	 * Only handle when change status LP Order from Completed to another status
	 *
	 * @param LP_Order $order
	 * @param string $old_status
	 * @param string $new_status
	 *
	 * @throws Exception
	 * @author Nhamdv <email@email.com>
	 * @editor tungnx
	 * @modify 4.1.4
	 * @version 1.0.4
	 */
	protected static function _update_user_item_order_pending( $order, $old_status, $new_status ) {
		$items = $order->get_all_items();
		if ( ! $items ) {
			return;
		}

		if ( $old_status !== LP_ORDER_COMPLETED ) {
			return;
		}

		foreach ( $order->get_users() as $user_id ) {
			$user = learn_press_get_user( $user_id );

			foreach ( $items as $item ) {
				if ( isset( $item['item_id'] ) && LP_COURSE_CPT === $item['item_type'] ) {
					$course_id = $item['item_id'];

					if ( $user_id ) {
						$userCourse = UserCourseModel::find( $user_id, $course_id, true );
						// Check course is learning is sample order_id with order which is changing status
						if ( ! $userCourse || $userCourse->ref_id != $order->get_id() ) {
							continue;
						}

						// Only change status of user_item to cancel, not delete user_item and user_item_results.
						$userCourse->status = UserItemModel::STATUS_CANCEL;
						$userCourse->save();
					} else {
						$userCourseGuest = self::get_user_course_guest( $course_id, $order->get_user_email() );
						// Check course is learning is sample order_id with order which is changing status
						if ( ! $userCourseGuest || $userCourseGuest->ref_id != $order->get_id() ) {
							continue;
						}

						$userCourseGuest->status = UserItemModel::STATUS_CANCEL;
						$userCourseGuest->save();
					}
				} else {
					// For buy other item type (not course)
					// For case item is Certificate, when update code of Certificate, should remove this code
					if ( $item['item_type'] === 'lp_cert' ) {
						$item['_lp_cert_id'] = $item['item_id'];
					}
					do_action( 'lp/order-pending/update/user-item', $item, $order, $user );
				}
			}
		}
	}

	/**
	 * Enroll course if Order completed
	 * 1. Check if order change is the latest order of user for course
	 * 2. Check if user_course was canceled, only update status to Enrolled
	 * 3. Handle repurchase course if allow repurchase
	 * 4. Create new user_course if first purchase course
	 * 5. Enroll course free or no enroll requirement
	 * 6. Enroll course for guest if auto enroll enable
	 * 7. Send email background when user enroll course
	 *
	 * @param LP_Order $order
	 * @param string $old_status
	 * @param string $new_status
	 *
	 * @throws Exception
	 * @editor tungnx
	 * @modify 4.1.2
	 * @version 1.0.6
	 */
	protected static function _update_user_item_order_completed( LP_Order $order, string $old_status, string $new_status ) {
		$lp_order_db = LP_Order_DB::getInstance();
		$items       = $order->get_all_items();
		if ( ! $items ) {
			return;
		}

		$usersCourses = [];
		foreach ( $order->get_users() as $user_id ) {
			$user = learn_press_get_user( $user_id );

			foreach ( $items as $item ) {
				if ( isset( $item['item_id'] ) && LP_COURSE_CPT === $item['item_type'] ) {
					$course_id = $item['item_id'];

					// Check order_id of user_item current must < new order_id
					$userCourse = UserCourseModel::find( $user_id, $course_id, true );
					if ( $user_id && $userCourse && $userCourse->ref_id > $order->get_id() ) {
						continue;
					} elseif ( ! $user_id ) {
						$userCourseGuest = self::get_user_course_guest( $course_id, $order->get_user_email() );
						if ( $userCourseGuest && $userCourseGuest->ref_id > $order->get_id() ) {
							continue;
						}
					}

					// Check user course was canceled? If yes, only update status to Enrolled
					if ( $user_id && $userCourse && $userCourse->ref_id == $order->get_id() ) {
						if ( $userCourse->get_status() === UserItemModel::STATUS_CANCEL ) {
							if ( ! empty( $userCourse->get_end_time() ) ) {
								$userCourse->status = UserItemModel::STATUS_FINISHED;
							} else {
								$userCourse->status = UserItemModel::STATUS_ENROLLED;
							}
							$userCourse->save();
						}

						continue;
					}

					if ( $order->is_manual() ) {
						$usersCourses[] = self::handle_item_manual_order_completed( $order, $user, $item );
					} else {
						$usersCourses[] = self::handle_item_order_completed( $order, $user, $item );
					}
				} else {
					// For buy other item type (not course)
					// For case item is Certificate, when update code of Certificate, should remove this code
					if ( $item['item_type'] === 'lp_cert' ) {
						$item['_lp_cert_id'] = $item['item_id'];
						// Fixed for old Certificate <= v4.1.2
						$item['_lp_course_id_of_cert'] = learn_press_get_order_item_meta(
							$item['order_item_id'],
							'_lp_course_id_of_cert'
						);
					}
					do_action( 'lp/order-completed/update/user-item', $item, $order, $user );
				}
			}
		}

		// Run background send emails when users enroll courses (send for instructor, admin, student...)
		if ( ! empty( $usersCourses ) ) {
			$userCourseIds = [];
			foreach ( $usersCourses as $userCourse ) {
				if ( ! $userCourse instanceof UserCourseModel ) {
					continue;
				}

				if ( $userCourse->get_status() !== UserItemModel::STATUS_ENROLLED ) {
					continue;
				}

				$userCourseIds[] = [
					'user_id'   => $userCourse->user_id,
					'course_id' => $userCourse->item_id,
				];
			}

			if ( empty( $userCourseIds ) ) {
				return;
			}

			/**
			 * @uses SendEmailAjax::send_mail_users_enrolled_courses
			 */
			$data_send = [
				'user_course_ids' => $userCourseIds,
				'lp-load-ajax'    => 'send_mail_users_enrolled_courses',
			];
			LPBackgroundAjax::handle( $data_send );
		}
	}

	/**
	 * Handle something when Order completed
	 *
	 * @author  tungnx
	 * @since   4.1.3
	 * @version 1.1.1
	 */
	protected static function handle_item_order_completed( LP_Order $order, $user, $item ) {
		$lp_user_items_db   = LP_User_Items_DB::getInstance();
		$userCourseResponse = null;

		try {
			$course_id   = intval( $item['course_id'] ?? $item['item_id'] ?? 0 );
			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				return null;
			}

			$auto_enroll                = LP_Settings::is_auto_start_course();
			$keep_progress_items_course = false;

			$user_id = $user->get_id();
			if ( $user instanceof LP_User_Guest ) {
				$user_id = 0;
			}

			/** Get the newest user_item_id of course for allow_repurchase */
			$userCourse = UserCourseModel::find( $user_id, $course_id, true );

			$latest_user_item_id     = 0;
			$allow_repurchase_option = $courseModel->get_type_repurchase();
			$allow_repurchase_type   = '';

			// Data user_item for save database
			$user_item_data = [
				'user_id'    => $user_id,
				'item_id'    => $course_id,
				'ref_id'     => $order->get_id(),
				'start_time' => gmdate( LP_Datetime::$format, time() ),
				'graduation' => UserItemModel::GRADUATION_IN_PROGRESS,
			];

			if ( $user_id && $userCourse ) {
				$latest_user_item_id = $userCourse->get_user_item_id();

				/** Get allow_repurchase_type for reset, update. Add in: rest-api/v1/frontend/class-lp-courses-controller.php: purchase_course */
				$allow_repurchase_type = learn_press_get_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type' );
			}

			$is_no_required_enroll = $courseModel->has_no_enroll_requirement();
			$is_in_stock           = $courseModel->is_in_stock();

			// If > 1 time purchase same course and allow repurchase
			if ( $courseModel->enable_allow_repurchase() && ! empty( $latest_user_item_id )
				&& ! $courseModel->is_free() && ! $is_no_required_enroll ) {
				if ( $allow_repurchase_option !== 'popup' ) {
					$allow_repurchase_type = $allow_repurchase_option;
				} elseif ( empty( $allow_repurchase_type ) ) {
					// For case course set repurchase Popup but buy via Upsell, PMS, Woo can't set allow_repurchase_type
					$allow_repurchase_type = 'keep';
				}

				/**
				 * If keep course progress will reset start_time, end_time, status, graduation
				 * where user_item_id = $latest_user_item_id
				 */
				if ( $allow_repurchase_type === 'keep' ) {
					$keep_progress_items_course = true;
					// Set data for update user item
					$user_item_data['user_item_id'] = $latest_user_item_id;
					$user_item_data['end_time']     = null;
					$user_item_data['status']       = UserItemModel::STATUS_ENROLLED;

					do_action( 'lp/allow_repurchase_options/continue/db/update', $user_item_data, $latest_user_item_id );
				} elseif ( $allow_repurchase_type === 'reset' ) {
					$user_item_data['end_time'] = null;
					$user_item_data['status']   = UserItemModel::STATUS_ENROLLED;
				}

				learn_press_delete_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type' );
			} elseif ( ! $courseModel->is_free() && ! $is_no_required_enroll && $is_in_stock ) { // First purchase course
				// Set data for create user_item
				if ( $auto_enroll ) {
					$user_item_data['status'] = UserItemModel::STATUS_ENROLLED;
				} else {
					$user_item_data['status']     = UserItemModel::STATUS_PURCHASED;
					$user_item_data['graduation'] = '';
				}
			} elseif ( $user_id && ( $is_in_stock || $is_no_required_enroll ) ) { // Enroll course free or No enroll requirement.
				// Set data for create user_item
				$user_item_data['status'] = UserItemModel::STATUS_ENROLLED;
			} elseif ( LP_Checkout::instance()->is_enable_guest_checkout()
				&& $auto_enroll && ( $is_in_stock || $is_no_required_enroll ) ) {
				$user_item_data['status'] = UserItemModel::STATUS_ENROLLED;
			} else {
				return null;
			}

			$user_item_data = apply_filters(
				'learn-press/order/user-course-data',
				$user_item_data,
				$order,
				$item,
				$courseModel,
				$user_id
			);

			$another_case = apply_filters(
				'learn-press/order/completed/update-user-item/another-case/bool',
				false,
				$user_item_data,
				$order,
				$item,
				$courseModel,
				$user_id
			);

			if ( $another_case ) { // Handle another case to handle user_item
				$userCourseResponse = apply_filters(
					'learn-press/order/completed/update-user-item/another-case',
					null,
					$user_item_data,
					$order,
					$item,
					$courseModel,
					$user_id
				);
			} elseif ( $keep_progress_items_course ) { // Update user_item to keep course progress
				$userCourse->ref_id     = $order->get_id();
				$userCourse->status     = $user_item_data['status'];
				$userCourse->graduation = $user_item_data['graduation'];
				$userCourse->start_time = $user_item_data['start_time'];
				$userCourse->end_time   = null;
				$userCourse->save();
				$userCourseResponse = $userCourse;
			} else { // Create new user_item
				// Delete items old
				if ( ! $user_id ) {
					$userGuestCourse = self::get_user_course_guest( $course_id, $order->get_user_email() );
					if ( $userGuestCourse ) {
						$userGuestCourse->delete();
					}
				} elseif ( $userCourse ) {
					//$lp_user_items_db->delete_user_items_old( $user_id, $course_id );
					$userCourse->delete();
				}

				$userCourseNew = new UserCourseModel( $user_item_data );
				$userCourseNew->save();
				$userCourseResponse = $userCourseNew;
			}

			if ( isset( $user_item_data['status'] ) && UserItemModel::STATUS_ENROLLED == $user_item_data['status'] ) {
				do_action( 'learnpress/user/course-enrolled', $order->get_id(), $user_item_data['item_id'], $user_item_data['user_id'] );
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $userCourseResponse;
	}

	/**
	 * Handle something when Manual Order completed
	 *
	 * @author tungnx
	 * @since 4.1.3
	 * @version 1.0.4
	 */
	protected static function handle_item_manual_order_completed( LP_Order $order, $user, $item ) {
		$userCourseResponse = null;

		try {
			$courseModel = CourseModel::find( $item['course_id'] ?? $item['item_id'] ?? 0, true );
			if ( ! $courseModel ) {
				return $userCourseResponse;
			}

			if ( $user instanceof LP_User_Guest ) {
				return $userCourseResponse;
			}

			// Data user_item for save database
			$user_item_data = [
				'user_id' => $user->get_id(),
				'item_id' => $courseModel->get_id(),
				'ref_id'  => $order->get_id(),
			];

			$auto_enroll = LP_Settings::is_auto_start_course();
			if ( $auto_enroll ) {
				$user_item_data['status']     = UserItemModel::STATUS_ENROLLED;
				$user_item_data['graduation'] = UserItemModel::GRADUATION_IN_PROGRESS;
			} else {
				$user_item_data['status'] = UserItemModel::STATUS_PURCHASED;
			}

			$user_item_data = apply_filters(
				'learn-press/order/manual/user-course-data',
				$user_item_data,
				$order,
				$item,
				$courseModel
			);

			// Delete lp_user_items old
			LP_User_Items_DB::getInstance()->delete_user_items_old( $user->get_id(), $courseModel->get_id() );
			// End

			$userCourseModelNew = new UserCourseModel( $user_item_data );
			$userCourseModelNew->save();
			$userCourseResponse = $userCourseModelNew;

			if ( UserItemModel::STATUS_ENROLLED == $userCourseResponse->get_status() ) {
				do_action( 'learnpress/user/course-enrolled', $order->get_id(), $user_item_data['item_id'], $user_item_data['user_id'] );
			}
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}

		return $userCourseResponse;
	}

	/**
	 * Get user_course of user Guest
	 *
	 * @param $course_id
	 * @param $email_guest
	 *
	 * @return UserCourseModel|false
	 * @throws Exception
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public static function get_user_course_guest( $course_id, $email_guest ) {
		$lp_user_items_db = LP_User_Items_DB::getInstance();
		$filter           = new LP_User_Items_Filter();
		$filter->user_id  = 0;
		$filter->item_id  = $course_id;
		$filter->join[]   = "INNER JOIN {$lp_user_items_db->tb_postmeta} pm ON pm.post_id = ref_id";
		$filter->join[]   = "INNER JOIN {$lp_user_items_db->tb_postmeta} pm2 ON pm2.post_id = ref_id";
		$filter->where[]  = "AND pm.meta_key = '_checkout_email'";
		$filter->where[]  = $lp_user_items_db->wpdb->prepare( 'AND pm2.meta_value = %s', $email_guest );

		return UserCourseModel::get_user_item_model_from_db( $filter );
	}

	/**
	 * Hook into wp users list to exclude our temp users.
	 *
	 * @param array $args
	 *
	 * @return mixed
	 * @deprecated 4.2.7.3
	 */
	/*public static function exclude_temp_users( $args ) {
		if ( LP_Request::get_string( 'lp-action' ) == 'pending-request' ) {
			$args['include'] = self::get_pending_requests();
		}

		return $args;
	}*/

	/**
	 * Get pending requests be come a Teacher.
	 *
	 * @return array
	 * @deprecated 4.2.7.3
	 */
	/*public static function get_pending_requests() {
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

		return $wpdb->get_col( $query );
	}*/

	/**
	 * @deprecated 4.2.7.3
	 */
	/*public static function get_guest_id() {
		return 0;// empty( $_COOKIE['learn_press_user_guest_id'] ) ? false : $_COOKIE['learn_press_user_guest_id'];
	}*/

	/**
	 * @param      $the_user
	 * @param bool $force
	 *
	 * @return LP_Abstract_User
	 * @deprecated 4.2.7.3
	 */
	/*public static function get_user( $the_user, $force = false ) {
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
	}*/

	/**
	 * Get class name for User Object
	 *
	 * @param int
	 *
	 * @return string
	 * @deprecated 4.2.7.3
	 */
	//  public static function get_user_class( $the_id = 0 ) {
	//      $deleted     = in_array( $the_id, self::$_deleted_users );
	//      $exists_user = ! $deleted ? get_userdata( $the_id ) : false;
	//      if ( $exists_user ) {
	//          $class = 'LP_User';
	//      } else {
	//          if ( ! $deleted ) {
	//              self::$_deleted_users[] = $the_id;
	//              /**
	//               * Prevent loading user does not exists in database
	//               */
	//              $user = new LP_User_Guest( $the_id );
	//              wp_cache_add( $the_id, $user, 'users' );
	//              wp_cache_add( '', $the_id, 'userlogins' );
	//              wp_cache_add( '', $the_id, 'useremail' );
	//              wp_cache_add( '', $the_id, 'userslugs' );
	//          }
	//          $is_logged_in = function_exists( 'is_user_logged_in' ) && is_user_logged_in();
	//          $class        = $is_logged_in ? 'LP_User' : 'LP_User_Guest';
	//      }
	//
	//      return apply_filters( 'learn_press_user_class', $class );
	//  }
}

LP_User_Factory::init();

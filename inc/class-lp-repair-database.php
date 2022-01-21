<?php

/**
 * Class LP_Repair_Database
 *
 * Repair database tool
 *
 * @since 3.1.0
 */
class LP_Repair_Database {
	/**
	 * @var LP_Repair_Database
	 */
	protected static $instance = null;

	/**
	 * Posts are being deleted.
	 *
	 * @var array
	 */
	protected $deleting_posts = array();

	/**
	 * LP_Repair_Database constructor.
	 *
	 * @access protected
	 */
	protected function __construct() {
		//add_action( 'save_post', array( $this, 'save_post' ), 0 );
		// add_action( 'deleted_post', array( $this, 'save_post' ), 0 );
		// add_action( 'learn-press/added-item-to-section', array( $this, 'added_item_to_section' ), 5000, 3 );
		// add_action( 'learn-press/removed-item-from-section', array( $this, 'removed_item_from_course' ), 5000, 2 );
		//add_action( 'learn-press/save-course', array( $this, 'save_course' ), 5000, 1 );
		//add_action( 'learn-press/added-course-item', array( $this, 'added_course_item' ), 10, 2 );
		//add_action( 'learn-press/removed-course-item', array( $this, 'removed_course_item' ), 10 );
		//add_action( 'learn-press/transition-course-item-status', array( $this, 'transition_course_item_status' ), 10, 4 );

		//add_action( 'before_delete_post', array( $this, 'before_delete_post' ) );
		// add_action( 'deleted_post', array( $this, 'deleted_post' ) );
		// add_action( 'save_post', array( $this, 'save_post' ) );
	}

	/**
	 * Stores some information of the post is being deleted.
	 * Eg post-type will be lost after deleted post.
	 *
	 * @param int $post_id
	 *
	 * @since 3.1.0
	 * @editor 4.1.4
	 * @modify comment - not use
	 */
	/*public function before_delete_post( $post_id ) {
		global $wpdb;
		// LP_Debug::startTransaction();
		$post_type = get_post_type( $post_id );
		$data      = array(
			'post_type' => $post_type,
		);

		switch ( $post_type ) {
			case LP_ORDER_CPT:
				$order         = learn_press_get_order( $post_id );
				$data['users'] = $order->get_users();
				$data['child'] = $order->get_child_orders();

				break;
		}

		$this->deleting_posts[ $post_id ] = $data;
	}*/

	/**
	 * Do stuff after post deleted.
	 *
	 * @param int $post_id
	 *
	 * @since 3.1.0
	 */
	/*public function deleted_post( $post_id ) {
		try {
			if ( ! empty( $this->deleting_posts[ $post_id ] ) ) {
				$data      = $this->deleting_posts[ $post_id ];
				$post_type = ! empty( $data['post_type'] ) ? $data['post_type'] : '';

				switch ( $post_type ) {
					case LP_ORDER_CPT:
						if ( ! empty( $data['users'] ) ) {
							$this->remove_order_from_user_meta( $data['users'], $post_id );
						}

						$this->remove_order_items( $post_id );
						$this->remove_user_items_by_order_id( $post_id );

						if ( ! empty( $data['child'] ) ) {
							$this->remove_child_orders( $data['child'] );
						}
						break;
				}
			}
		} catch ( Exception $ex ) {
			echo $ex->getMessage();
		}

		// LP_Debug::rollbackTransaction();
		// die();
	}*/

	/**
	 * Remove order items and it meta data from tables
	 * learnpress_user_items and learnpress_user_itemmeta
	 * by order id.
	 *
	 * @param int $order_id
	 *
	 * @return bool|int[]
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 comment - not use
	 */
	/*public function remove_order_items( $order_id ) {
		global $wpdb;

		if ( is_array( $order_id ) ) {
			foreach ( $order_id as $oid ) {
				$this->remove_order_items( $oid );
			}

			return true;
		}

		$order_item_id = $wpdb->get_col( $wpdb->prepare( "SELECT order_item_id FROM {$wpdb->learnpress_order_items} WHERE order_id = %d", $order_id ) );

		if ( ! $order_item_id ) {
			return false;
		}

		$format = array_fill( 0, sizeof( $order_item_id ), '%d' );

		// Delete rows from order-items and order-itemmeta
		$query = $wpdb->prepare(
			"
			DELETE
			FROM {$wpdb->learnpress_order_itemmeta}
			WHERE learnpress_order_item_id IN(" . join( ',', $format ) . ')
		',
			$order_item_id
		);

		$wpdb->query( $query );

		$query = $wpdb->prepare(
			"
			DELETE
			FROM {$wpdb->learnpress_order_items}
			WHERE order_item_id  IN(" . join( ',', $format ) . ')
		', $order_item_id ); // phpcs:ignore

		$wpdb->query( $query );

		return $order_item_id;
	}*/

	/**
	 * @param int[] $order_ids
	 * @editor tungnx
	 * @modify 4.1.4 comment - not use
	 */
	/*public function remove_child_orders( $order_ids ) {
		global $wpdb;

		foreach ( $order_ids as $order_id ) {
			wp_delete_post( $order_id );
		}

	}*/

	/**
	 * Remove user items from table learnpress_user_items
	 * and it's meta data from table learnpress_user_itemmeta
	 * by order id. Uses this function when we want to remove
	 * a course-item and all child-items reference to a an order.
	 *
	 * @param int[] $order_id
	 *
	 * @return bool
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function remove_user_items_by_order_id( $order_id ) {
		if ( is_array( $order_id ) ) {
			foreach ( $order_id as $oid ) {
				$this->remove_user_items_by_order_id( $oid );
			}

			return true;
		}

		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE ref_id = %d AND ref_type = %s
		",
			$order_id,
			LP_ORDER_CPT
		);

		$user_item_ids = $wpdb->get_col( $query );

		if ( ! $user_item_ids ) {
			return false;
		}

		$this->remove_user_items_by_user_item_id( $user_item_ids, true );

		return true;
	}*/

	/**
	 * Remove user item from table user-items by primary key user_item_id.
	 * This function also remove row's meta data and/or child rows.
	 *
	 * @param int[] $user_item_id
	 * @param bool  $remove_child - Optional. TRUE will remove it's child
	 *
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function remove_user_items_by_user_item_id( $user_item_id, $remove_child = true ) {
		global $wpdb;

		settype( $user_item_id, 'array' );

		$format              = array_fill( 0, sizeof( $user_item_id ), '%d' );
		$child_user_item_ids = $this->get_user_items_by_parent_id( $user_item_id );

		// Remove child
		if ( $remove_child && $child_user_item_ids ) {
			$this->remove_user_items_by_user_item_id( $child_user_item_ids, $remove_child );
		}

		$query = $wpdb->prepare(
			"
			DELETE
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id IN(" . join( ',', $format ) . ')
		',
			$user_item_id
		);

		$wpdb->query( $query );

		$query = $wpdb->prepare(
			"
			DELETE
			FROM {$wpdb->learnpress_user_items}
			WHERE user_item_id IN(" . join( ',', $format ) . ')
		',
			$user_item_id
		);

		$wpdb->query( $query );

		// Remove all result in table user_item_results.
		foreach ( $user_item_id as $delete_id ) {
			LP_User_Items_Result_DB::instance()->delete( $delete_id );
		}
	}*/

	/**
	 * Remove user item from table user-items by specific user-id and item-id.
	 *
	 * @param int $user_id
	 * @param int $item_id
	 *
	 * @return array|bool
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify comment - not use
	 */
	/*public function remove_user_item( $user_id, $item_id ) {
		global $wpdb;

		// Find user-item-id for passed user-id and item-id
		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE user_id = %d
				AND item_id = %d
		",
			$user_id,
			$item_id
		);

		$user_item_ids = $wpdb->get_col( $query );

		if ( $user_item_ids ) {
			return false;
		}

		// Remove by user-item-ids
		$this->remove_user_items_by_user_item_id( $user_item_ids );

		return $user_item_ids;
	}*/

	/**
	 * Remove user item from table user-items by user-id.
	 * Uses this function in case we want to remove all item
	 * from an user (such as when an user will be deleted).
	 *
	 * @param int[] $user_id
	 *
	 * @return bool
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function remove_user_item_by_user( $user_id ) {
		global $wpdb;

		// If multi users passed, loop through each user
		if ( is_array( $user_id ) ) {
			foreach ( $user_id as $uid ) {
				$this->remove_user_item_by_user( $uid );
			}

			return true;
		}

		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE user_id = %d
		",
			$user_id
		);

		$user_item_ids = $wpdb->get_col( $query );

		if ( ! $user_item_ids ) {
			return false;
		}

		$this->remove_user_items_by_user_item_id( $user_item_ids );

		return true;
	}*/

	/**
	 * Remove user item from table user-items by item-id.
	 * Uses this function in case want to remove an item
	 * from all users (such as an item like course/lesson/quiz will be deleted).
	 *
	 * @param int $item_id
	 *
	 * @return array|bool
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function remove_user_item_by_item_id( $item_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE item_id = %d
		",
			$item_id
		);

		$user_item_ids = $wpdb->get_col( $query );

		if ( ! $user_item_ids ) {
			return false;
		}

		$this->remove_user_items_by_user_item_id( $user_item_ids );

		return $user_item_ids;
	}*/

	/**
	 * Retrieve child items from an user-item-id.
	 *
	 * @param int[] $parent_ids
	 *
	 * @return array
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function get_user_items_by_parent_id( $parent_ids ) {
		global $wpdb;
		settype( $parent_ids, 'array' );
		$format = array_fill( 0, sizeof( $parent_ids ), '%d' );

		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE parent_id IN(" . join( ',', $format ) . ')
		',
			$parent_ids
		);

		return $wpdb->get_col( $query );
	}*/

	/**
	 * Remove an order from user-meta.
	 *
	 * @param int[] $user_ids
	 * @param int   $order_id
	 *
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
//	public function remove_order_from_user_meta( $user_ids, $order_id ) {
//		if ( ! $user_ids ) {
//			return;
//		}
//
//		settype( $user_ids, 'array' );
//		foreach ( $user_ids as $user_id ) {
//			delete_user_meta( $user_id, 'orders' );
//
//			/*if ( $user_orders ) {
//				foreach ( $user_orders as $course_id => $course_orders ) {
//					$course_orders = array_unique( $course_orders );
//					if ( false !== ( $in_pos = array_search( $order_id, $course_orders ) ) ) {
//						unset( $course_orders[ $in_pos ] );
//					}
//
//					if ( ! $course_orders ) {
//						unset( $user_orders[ $course_id ] );
//					} else {
//						$user_orders[ $course_id ] = $course_orders;
//					}
//				}
//			}
//
//			update_user_meta( $user_id, 'order', $user_orders );*/
//		}
//	}

	/**
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function add_order_to_user_meta( $order_id ) {
		$order = learn_press_get_order( $order_id );

		if ( ! $order ) {
			return false;
		}

		if ( $order->is_multi_users() ) {

			$child = $order->get_child_orders();
			if ( ! $child ) {
				return false;
			}

			foreach ( $child as $child_order_id ) {
				$child_order = learn_press_get_order( $child_order_id );

			}

			return true;
		}

		$user_id     = $order->get_user_id();
		$user_orders = get_user_meta( $user_id, 'orders', true );

		if ( $user_orders ) {
			foreach ( $user_orders as $course_id => $course_orders ) {
				$course_orders = array_unique( $course_orders );
				$in_pos        = array_search( $order_id, $course_orders );

				if ( false !== $in_pos ) {
					unset( $course_orders[ $in_pos ] );
				}

				if ( ! $course_orders ) {
					unset( $user_orders[ $course_id ] );
				} else {
					$user_orders[ $course_id ] = $course_orders;
				}
			}
		}

		return true;
	}*/

	/**
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	/*public function save_course( $course_id ) {
		$this->sync_course_data( $course_id );
	}*/

	/**
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	/*public function removed_course_item( $course_id ) {
		$this->sync_course_data( $course_id );
	}*/

	/**
	 * @param int $item_id
	 * @param int $course_id
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	/*public function added_course_item( $item_id, $course_id ) {
		$this->sync_course_data( $course_id );
	}*/

	/**
	 * @param int $item_id
	 * @param int $course_id
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	/*public function removed_item_from_course( $item_id, $course_id ) {
		$this->sync_course_data( $course_id );
	}*/

	/**
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
	/*public function transition_course_item_status( $item_id, $course_id, $old, $new ) {
		if ( $old === $new ) {
			return;
		}
		$this->sync_course_data( $course_id );
	}*/

	/**
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function get_user_item_type( $item_id ) {
		global $wpdb;
		if ( ! $item_type = get_post_type( $item_id ) ) {
			$query     = $wpdb->prepare(
				"
				SELECT item_type
				FROM {$wpdb->learnpress_user_items}
				WHERE item_id = %d
				LIMIT 0,1
			",
				$item_id
			);
			$item_type = $wpdb->get_var( $query );
		}

		return $item_type;
	}*/

	/**
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function remove_user_itemx( $item_id ) {
		global $wpdb;

		$query = "
			DELETE items, meta
			FROM {$wpdb->learnpress_user_items} items
			INNER JOIN {$wpdb->learnpress_user_itemmeta} meta ON items.user_item_id = meta.learnpress_user_item_id
		";

		$where = '';

		if ( $this->get_user_item_type( $item_id ) === LP_COURSE_CPT ) {
			$_query = $wpdb->prepare(
				"
				SELECT user_item_id
				FROM {$wpdb->learnpress_user_items}
				WHERE item_id = %d
				AND parent_id = 0
			",
				$item_id
			);

			$user_item_ids = $wpdb->get_col( $_query );
			$format        = array_fill( 0, sizeof( $user_item_ids ), '%d' );

			$where = $wpdb->prepare(
				'
				WHERE parent_id IN(' . join( ',', $format ) . ')
			',
				$user_item_ids
			);

			$where .= $wpdb->prepare( 'AND ref_id = %d', $item_id );
		} else {
			$where = $wpdb->prepare( 'item_id = %d', $item_id );
		}

		$query .= $where;
	}*/

	/**
	 * @param int $post_id
	 * @editor tungnx
	 * @modify 4.1.4.1 - comment - not use
	 */
//	public function save_post( $post_id ) {
//		global $wpdb;
//		$post_type   = get_post_type( $post_id );
//		$course_curd = new LP_Course_CURD();
//
//		switch ( $post_type ) {
//			/*case LP_ORDER_CPT:
//				$order = learn_press_get_order( $post_id );
//				if ( $order ) {
//					$user_ids   = $order->get_users();
//					$course_ids = $order->get_item_ids();
//
//					//$this->sync_course_orders( $course_ids );
//					//$this->sync_user_orders( $user_ids );
//				}
//
//				break;*/
//			case LP_COURSE_CPT: //Todo: tungnx should review code to rewrite
//			default:
//				// Course is support type of this item?
//				if ( learn_press_is_support_course_item_type( $post_type ) ) {
//
//					// Find it course
//					$course_ids = $course_curd->get_course_by_item( $post_id );
//				} else {
//					$course_ids = array( $post_id );
//				}
//
//				foreach ( $course_ids as $course_id ) {
//					$this->sync_course_data( $course_id );
//				}
//		}
//	}

	/**
	 * Sync course data when saving post.
	 *
	 * @param int $course_id
	 * @editor tungnx
	 * @version 3.1.1
	 * @since 3.1.0
	 */
	/*public function sync_course_data( int $course_id ) {
		$user_curd   = new LP_User_CURD();
		$course_curd = new LP_Course_CURD();

		$count_items = 0;
		$counts      = $course_curd->count_items( $course_id );
		if ( $counts ) {
			$count_items = array_sum( $counts );
		}

		update_post_meta( $course_id, 'count_items', $count_items );
	}*/

	/**
	 * Sync all
	 *
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function sync_all() {
		$this->sync_course_orders();
		$this->sync_user_courses();
	}*/

	public function call( $func ) {
		$func = preg_replace( '~[-]+~', '_', $func );

		if ( ! is_callable( array( $this, $func ) ) ) {
			throw new Exception( sprintf( __( 'The method %s is not callable.', 'learnpress' ), $func ) );
		}

		$args = func_get_args();
		unset( $args[0] );

		return sizeof( $args ) ?
			call_user_func_array( array( $this, $func ), $args ) :
			call_user_func( array( $this, $func ) );
	}

	/***
	 * @editor tungnx
	 * @deprecated 4.0.7
	 * @reason not use on LP4, make CPU run high.
	 */
	/*public function queue_sync_user_course_results( $course_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT DISTINCT user_id
			FROM {$wpdb->learnpress_user_items}
			WHERE item_id = %d
		",
			$course_id
		);

		if ( $user_ids = $wpdb->get_col( $query ) ) {
			$queue_user_ids = get_option( 'sync-user-course-results' );
			$first_time     = ! $queue_user_ids;
			$n              = time();
			for ( $i = $n; $i < $n + 100; $i ++ ) {
				$user_ids[] = $i;
			}

			if ( $first_time ) {
				$queue_user_ids = $user_ids;
			} else {
				settype( $queue_user_ids, 'array' );
				$queue_user_ids = array_merge( $queue_user_ids, $user_ids );
				$queue_user_ids = array_unique( $queue_user_ids );
			}

			$option_key = 'sync-user-course-results';
			update_option( $option_key, $queue_user_ids, 'no' );

			if ( $first_time || ! get_option( 'doing-sync-user-course-results' ) ) {
				$bg = LP_Background_Sync_Data::instance();
				$bg->is_safe( false );
				$bg->push_to_queue(
					array(
						'action'     => 'sync-user-course-results',
						'course_id'  => $course_id,
						'option_key' => $option_key,
					)
				)->save()->dispatch();
				$bg->reset_safe();

				update_option( 'doing-sync-user-course-results', 'yes' );
			}
		}
	}*/

	/**
	 * Sync orders for each course
	 *
	 * @param array|string $courses
	 *
	 * @return bool|array
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function sync_course_orders( $courses = '*' ) {
		global $wpdb;

		if ( empty( $courses ) ) {
			return false;
		}

		if ( $courses === '*' ) {
			$query = $wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->posts}
				WHERE post_type = %s
					AND post_status = %s
			",
				LP_COURSE_CPT,
				'publish'
			);

			$courses = $wpdb->get_col( $query );

			if ( $courses ) {
				return false;
			}
		}

		$statuses = learn_press_get_order_statuses( true, true );
		settype( $courses, 'array' );

		$statuses_format = array_fill( 0, sizeof( $statuses ), '%s' );
		$courses_format  = array_fill( 0, sizeof( $courses ), '%d' );
		$statuses_format = $wpdb->prepare( join( ',', $statuses_format ), $statuses );
		$courses_format  = $wpdb->prepare( join( ',', $courses_format ), $courses );
		$wpdb->query( 'SET SESSION group_concat_max_len = 18446744073709551615' );
		$query = $wpdb->prepare(
			"
				SELECT cid, status, orders
				FROM(
					SELECT oim.meta_value cid, concat(oim.meta_value, ' ', o.post_status)  a, post_status `status`, GROUP_CONCAT(o.ID) orders
					FROM {$wpdb->learnpress_order_itemmeta} oim
					INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = %s
					INNER JOIN {$wpdb->posts} o ON o.ID = oi.order_id
					INNER JOIN {$wpdb->postmeta} om ON o.ID = om.post_id AND `om`.`meta_key`='_user_id'
					INNER JOIN {$wpdb->users} `u` ON u.ID = `om`.`meta_value`
					WHERE o.post_type = %s
					AND o.post_status IN ($statuses_format)
					AND oim.meta_value IN ($courses_format)
					GROUP BY a, cid
				) X
			",
			'_course_id',
			'lp_order'
		);

		foreach ( $courses as $course_id ) {
			foreach ( $statuses as $status ) {
				update_post_meta( $course_id, 'order-' . str_replace( 'lp-', '', $status ), array() );
			}
		}

		if ( $results = $wpdb->get_results( $query ) ) {
			foreach ( $results as $result ) {
				update_post_meta( $result->cid, 'order-' . str_replace( 'lp-', '', $result->status ), explode( ',', $result->orders ) );
			}
		}

		return $courses;
	}*/

	/**
	 * Sync orders for each user
	 *
	 * @param array $users
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function sync_user_orders( $users = array() ) {
		$api = new LP_User_CURD();
		settype( $users, 'array' );

		foreach ( $users as $user ) {
			$orders = $api->read_orders( $user );
			if ( ! $orders ) {
				continue;
			}
			$orders = array_map( 'array_unique', $orders );
			update_user_meta( $user, 'orders', $orders );
		}
	}*/

	/**
	 * Sync courses for each user
	 *
	 * @since 3.1.0
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function sync_user_courses() {
		// echo __FUNCTION__;
	}*/


	/**
	 * Sync final quiz for each course.
	 *
	 * @param array $courses
	 * @editor tungnx
	 * @deprecated 4.1.5
	 */
//	public function sync_course_final_quiz( $courses = array() ) {
//		settype( $courses, 'array' );
//
//		foreach ( $courses as $course_id ) {
//			$course = learn_press_get_course( $course_id );
//
//			if ( ! $course ) {
//				continue;
//			}
//
//			/**
//			 * If course result is not set to final-quiz
//			 */
//			if ( $course->get_data( 'course_result' ) !== 'evaluate_final_quiz' ) {
//				delete_post_meta( $course_id, '_lp_final_quiz' );
//				continue;
//			}
//
//			$items = $course->get_item_ids();
//			if ( $items ) {
//				foreach ( $items as $item ) {
//					if ( learn_press_get_post_type( $item ) === LP_QUIZ_CPT ) {
//						$final_quiz = $item;
//					}
//				}
//			}
//
//			if ( isset( $final_quiz ) ) {
//				update_post_meta( $course_id, '_lp_final_quiz', $final_quiz );
//			} else {
//				delete_post_meta( $course_id, '_lp_final_quiz' );
//				update_post_meta( $course_id, '_lp_course_result', 'evaluate_lesson' );
//			}
//		}
//	}

	/**
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function calculate_course_results( $user_id ) {
		settype( $user_id, 'array' );

		global $wpdb;

		foreach ( $user_id as $uid ) {
			$query = $wpdb->prepare(
				"
				SELECT DISTINCT item_id
				FROM {$wpdb->learnpress_user_items} ui
				INNER JOIN {$wpdb->posts} p ON p.ID = ui.item_id
				WHERE user_id = %d
				AND p.post_type = %s
			",
				$uid,
				LP_COURSE_CPT
			);

			$course_ids = $wpdb->get_col( $query );
			if ( $course_ids ) {
				$user = learn_press_get_user( $uid );
				foreach ( $course_ids as $course_id ) {
					$item_course     = $user->get_course_data( $course_id );
					$course_exceeded = $item_course->is_exceeded();

					if ( $course_exceeded <= 0 && ( learn_press_is_enrolled_slug( $item_course->get_status() ) ) ) {
						$item_course->finish();

						$start_time = $item_course->get_start_time()->getTimestamp();
						$duration   = $item_course->get_course()->get_duration();

						learn_press_update_user_item_meta( $item_course->get_user_item_id(), 'via', 'schedule' );
						learn_press_update_user_item_meta( $item_course->get_user_item_id(), 'exceeded', $course_exceeded );
					} else {
						$item_course->calculate_course_results();

					}
				}
			}
		}
	}*/

	/**
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	/*public function remove_older_post_meta() {
		global $wpdb;
		$query = $wpdb->prepare(
			"
			DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s
		",
			$wpdb->esc_like( '_lpr_' ) . '%'
		);
		$wpdb->query( $query );

		$query = $wpdb->prepare(
			"
			DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s
		",
			'%' . $wpdb->esc_like( '_lpr_' ) . '%'
		);
		$wpdb->query( $query );
	}*/

	/**
	 * Get all ids of existing courses
	 *
	 * @return array
	 * @deprecated 4.1.5
	 */
	public function get_all_courses() {
		global $wpdb;
		$query = $wpdb->prepare(
			"
            SELECT ID
            FROM {$wpdb->posts}
            WHERE post_type = %s
                AND post_status = %s
        ",
			LP_COURSE_CPT,
			'publish'
		);

		return $wpdb->get_col( $query );
	}

	/**
	 * @return LP_Repair_Database
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

LP_Repair_Database::instance();

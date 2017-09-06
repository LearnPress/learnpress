<?php

class LP_User_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

	/**
	 * @var string
	 */
	protected $_meta_type = 'user';

	/**
	 * LP_User_CURD constructor.
	 *
	 * @param int $the_user
	 * @param int $the_course
	 */
	public function __construct( $the_user = 0, $the_course = 0 ) {
		if ( $the_user || $the_course ) {
			if ( ! $the_course ) {
				$the_course = get_the_ID();
			}

			if ( ! $the_user ) {
				$the_user = get_current_user_id();
			}
		}
		if ( $user = learn_press_get_user( $the_user ) ) {
			$this->load( $user );
			$this->read_course( $user->get_id(), $the_course );
		}
	}

	/**
	 * @param LP_User $user
	 */
	public function create( &$user ) {

	}

	/**
	 * @param LP_User $user
	 */
	public function update( &$user ) {
		// TODO: Implement update() method.
	}

	/**
	 * @param LP_User $user
	 */
	public function delete( &$user ) {
		// TODO: Implement delete() method.
	}

	/**
	 * Load user data
	 *
	 * @param LP_User $user
	 *
	 * @return mixed;
	 */
	public function load( &$user ) {
		$user_id = $user->get_id();
		if ( false !== ( $user_object = get_user_by( 'id', $user_id ) ) ) {
			$user->set_data(
				array(
					'email'         => $user_object->user_email,
					'user_login'    => $user_object->user_login,
					'description'   => $user_object->description,
					'first_name'    => isset( $user_object->first_name ) ? $user_object->first_name : '',
					'last_name'     => isset( $user_object->last_name ) ? $user_object->last_name : '',
					'nickname'      => isset( $user_object->nickname ) ? $user_object->nickname : '',
					'display_name'  => $user_object->display_name,
					'date_created'  => $user_object->user_registered,
					'date_modified' => get_user_meta( $user_id, 'last_update', true ),
					'role'          => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'student',
				), null, true
			);
		}

		return true;
	}

	/**
	 * Get all orders of an user grouped by id of course.
	 *
	 * A course can have multi orders, each element is an array of order ids
	 * with the key is id of the course. The orders is sorted from highest
	 * to lowest. With the highest value is the latest order user placed (current order).
	 *
	 * Format:
	 *  array(
	 *      course_id_1 => array( order_id_1, order_id_2, ...),
	 *      course_id_2 => array( order_id_3, order_id_4, ...),
	 *      ...
	 *  )
	 *
	 * @param int  $user_id
	 * @param bool $group_by_order - Optional. Group by order id instead of by course id
	 *
	 * @return array|mixed
	 */
	public function get_orders( $user_id, $group_by_order = false ) {

		// If user does not exists
		if ( ! learn_press_get_user( $user_id ) ) {
			return false;
		}

		global $wpdb;

		//$data = LP_Cache::get_user_course_order( false, array() );

		// Get orders for the user from cache
		$orders = wp_cache_get( 'user-' . $user_id, 'lp-user-orders' );

		if ( false === $orders ) {
			$orders                = array();
			$post_status_in        = learn_press_get_order_statuses( true, true );
			$post_status_in_format = array_fill( 0, sizeof( $post_status_in ), '%s' );

			$query = $wpdb->prepare( "
				SELECT * 
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = %s
				WHERE p.post_type = %s
				AND meta_value = %d
				AND p.post_status IN(" . join( ',', $post_status_in_format ) . ")
				ORDER BY ID DESC
			", array_merge( array( '_user_id', LP_ORDER_CPT, $user_id ), $post_status_in ) );

			if ( $order_posts = $wpdb->get_results( $query ) ) {
				$order_ids = array();
				foreach ( $order_posts as $order_post ) {

					// Put post into cache to user later ... maybe.
					$_post = sanitize_post( $order_post, 'raw' );
					wp_cache_add( $_post->ID, $_post, 'posts' );

					$order_ids[] = $_post->ID;
				}

				$order_ids_format = array_fill( 0, sizeof( $order_ids ), '%d' );
				$query            = $wpdb->prepare( "
					SELECT meta_value as course_id, order_id
					FROM {$wpdb->learnpress_order_items} oi 
					INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = %s
					WHERE oi.order_id IN (" . join( ',', $order_ids_format ) . ")
					ORDER BY FIELD(order_id, " . join( ',', $order_ids_format ) . ")
				", array_merge( array( '_course_id' ), $order_ids, $order_ids ) );

				if ( $results = $wpdb->get_results( $query ) ) {
					foreach ( $results as $result ) {
						if ( empty( $orders[ $result->course_id ] ) ) {
							$orders[ $result->course_id ] = array();
						}
						$orders[ $result->course_id ][] = $result->order_id;
					}
				}
			}
//
//			echo $query = $wpdb->prepare( "
//				SELECT o.ID, oim.meta_value as course_id
//				FROM {$wpdb->learnpress_order_items} oi
//				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id = oi.order_item_id AND meta_key = %s
//				INNER JOIN {$wpdb->postmeta} om ON om.post_id = oi.order_id AND om.meta_key = %s AND om.meta_value = %d
//				INNER JOIN {$wpdb->posts} o ON o.ID = om.post_id AND o.post_status <> %s
//				WHERE o.post_type = %s ORDER BY ID ASC
//			", '_course_id', '_user_id', $user_id, 'trash', LP_ORDER_CPT );
//
//			if ( $rows = $wpdb->get_results( $query ) ) {
//				foreach ( $rows as $row ) {
//					if ( empty( $orders[ $row->course_id ] ) ) {
//						$orders[ $row->course_id ] = array();
//					}
//					$orders[ $row->course_id ][] = $row->ID;
//				}
//
//				// Sort the order ids from highest to lowest
//				foreach ( $orders as $course_id => $order_ids ) {
//					if ( $order_ids ) {
//						rsort( $orders[ $course_id ] );
//					}
//				}
//			}

			// Store to cache
			wp_cache_set( 'user-' . $user_id, $orders, 'lp-user-orders' );
		}

		if ( $orders && $group_by_order ) {
			$this->_group_orders( $orders );
		}

		return $orders;
	}

	protected function _group_orders( &$orders ) {
		$groups = array();
		foreach ( $orders as $course_id => $order_ids ) {
			foreach ( $order_ids as $order_id ) {
				if ( empty( $groups[ $order_id ] ) ) {
					$groups[ $order_id ] = array();
				}
				$groups[ $order_id ][] = $course_id;
			}
		}
		$orders = $groups;
		krsort( $orders );

		return $orders;
	}

	/**
	 * Read course data for an user.
	 *
	 * @param int       $user_id
	 * @param int|array $course_id
	 * @param bool      $force - Optional. Force to read new data from DB (ignore caching).
	 *
	 * @return bool
	 */
	public function read_course( $user_id = null, $course_id = null, $force = false ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( is_null( $course_id ) ) {
			$course_id = get_the_ID();
		}

		if ( is_numeric( $course_id ) ) {
			settype( $course_id, 'array' );
		}

		$fetch_ids = array();

		/**
		 * Get course's data from cache and if it is already existed
		 * then ignore that course.
		 */
		foreach ( $course_id as $id ) {

			// Refresh
			if ( $force ) {
				wp_cache_delete( 'course-' . $user_id . '-' . $id, 'lp-user-courses' );
			}

			if ( false === wp_cache_get( 'course-' . $user_id . '-' . $id, 'lp-user-courses' ) ) {
				$fetch_ids[] = $id;
				//wp_cache_set( 'course-' . $user_id . '-' . $id, array( 'items' => array() ), 'lp-user-courses' );
			}
		}

		// There is no course ids to read
		if ( ! $fetch_ids ) {
			return false;
		}

		global $wpdb;

		$format = array_fill( 0, sizeof( $fetch_ids ), '%d' );
		$args   = $fetch_ids;
		array_unshift( $args, LP_COURSE_CPT );
		$args[] = $user_id;
		$query  = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE item_type = %s
			AND item_id IN(" . join( ',', $format ) . ")
			AND user_id = %d
			ORDER BY item_id, user_item_id DESC
		", $args );

		if ( $results = $wpdb->get_results( $query, ARRAY_A ) ) {
			foreach ( $results as $result ) {
				// Refresh
				if ( $force ) {
					wp_cache_delete( 'course-' . $user_id . '-' . $result['item_id'], 'lp-user-courses' );
				}

				/**
				 * Ignore row if it is already added. We sort the rows by newest user_item_id
				 * therefore the first row in a group of item_id is row we need.
				 */
				if ( false !== wp_cache_get( 'course-' . $user_id . '-' . $result['item_id'], 'lp-user-courses' ) ) {
					continue;
				}
				$result['items'] = array();
				$this->_read_course_items( $result, $force );
				wp_cache_set( 'course-' . $user_id . '-' . $result['item_id'], $result, 'lp-user-courses' );

				// Remove the course has already read!
				$fetch_ids = array_diff( $fetch_ids, array( $result['item_id'] ) );
			}
		}

		// Cache the courses is not read
		if ( $fetch_ids ) {
			foreach ( $fetch_ids as $fetch_id ) {
				//wp_cache_set( 'course-' . $user_id . '-' . $fetch_id, array( 'items' => array() ), 'lp-user-courses' );
			}
		}

		return true;
	}

	/**
	 * Load user items by item_id of course item
	 *
	 * @param object $parent_item
	 * @param bool   $force - Optional. Force to read new data from DB (ignore caching).
	 *
	 * @return bool
	 */
	protected function _read_course_items( &$parent_item, $force = false ) {
		global $wpdb;

		$item_types = learn_press_get_course_item_types();
		$args       = array_merge( $item_types, array( $parent_item['user_item_id'] ) );
		$format     = array_fill( 0, sizeof( $item_types ), '%s' );

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE item_type IN(" . join( ',', $format ) . ")
			AND parent_id = %d
			ORDER BY item_id, user_item_id DESC
		", $args );

		if ( $results = $wpdb->get_results( $query, ARRAY_A ) ) {
			$items    = array();
			$meta_ids = array();
			foreach ( $results as $result ) {
				$user_item_id = $result['item_id'];
				if ( empty( $items[ $user_item_id ] ) ) {
					$items[ $user_item_id ]                = array();
					$parent_item['items'][ $user_item_id ] = $user_item_id;
				}
				//$this->_read_item_meta( $result );
				$meta_ids[] = $result['user_item_id'];

				$items[ $user_item_id ][ $result['user_item_id'] ] = $result;
			}

			// Batch updating user item meta
			update_meta_cache( 'learnpress_user_item', $meta_ids );

			foreach ( $items as $user_item_id => $_items ) {
				$cache_name = sprintf( 'course-item-%d-%d-%d', $parent_item['user_id'], $parent_item['item_id'], $user_item_id );
				// Refresh caching
				if ( $force ) {
					wp_cache_delete( $cache_name, 'lp-user-course-items' );
				}

				wp_cache_set( $cache_name, $_items, 'lp-user-course-items' );
			}
		}

		return true;
	}

	/**
	 * Get all items user has already started/completed.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return mixed
	 */
	public function get_user_items( $user_id, $course_id ) {
		if ( false === ( $course_data = wp_cache_get( 'course-' . $user_id . '-' . $course_id, 'lp-user-courses' ) ) ) {
			return false;
		}

		return $course_data['items'];
	}

	public function get_user_completed_items( $user_id, $course_id ) {
		if ( ! $items = $this->get_user_items( $user_id, $course_id ) ) {
			return false;
		}
		foreach ( $items as $item ) {

		}
	}

	/**
	 * Read meta data of an user item.
	 *
	 * @param $item
	 */
	protected function _read_item_meta( &$item ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
		", $item['user_item_id'] );
		update_meta_cache( 'learnpress_user_item', $item['user_item_id'] );

		//learn_press_debug($wpdb);die();
		if ( $meta = $wpdb->get_results( $query, ARRAY_A ) ) {
			$item['meta'] = array();
			foreach ( $meta as $k => $v ) {
				$v['meta_value']               = maybe_unserialize( $v['meta_value'] );
				$item['meta'][ $v['meta_id'] ] = $v;
			}
		}
	}

	/**
	 * @param int   $user_id
	 * @param int   $item_id
	 * @param array $item_data
	 * @param int   $course_id
	 *
	 * @return mixed
	 */
	public function update_user_item( $user_id, $item_id, $item_data = array(), $course_id = 0 ) {
		global $wpdb;
		if ( ! $course_id ) {
			$this->read_course( $user_id, $item_id );
		} else {
			$this->read_course( $user_id, $course_id );
		}

		$item = $this->get_user_item( $user_id, $item_id, $course_id );
		// Table fields
		$table_fields = array(
			'user_id'        => '%d',
			'item_id'        => '%d',
			'ref_id'         => '%d',
			'start_time'     => '%s',
			'start_time_gmt' => '%s',
			'end_time'       => '%s',
			'end_time_gmt'   => '%s',
			'item_type'      => '%s',
			'status'         => '%s',
			'ref_type'       => '%s',
			'parent_id'      => '%d'
		);

		// Data and format
		$data        = array();
		$data_format = array();

		// Update it later...
		$new_status = false;
		if ( array_key_exists( 'status', $item_data ) && $item_data['status'] != $item['status'] ) {
			$new_status = $item_data['status'];
			unset( $item_data['status'] );
		}

		// Build data and data format
		foreach ( $item_data as $field => $value ) {
			if ( ! empty( $table_fields[ $field ] ) ) {
				$data[ $field ]        = $value;
				$data_format[ $field ] = $table_fields[ $field ];
			}
		}

		$data['user_id'] = $user_id;
		$data['item_id'] = $item_id;

		if ( $course_id ) {
			$data['ref_id']   = $course_id;
			$data['ref_type'] = LP_COURSE_CPT;
		} else {
			$data['item_type'] = LP_COURSE_CPT;
		}

		foreach ( $data as $k => $v ) {
			$data_format[ $k ] = $table_fields[ $k ];
		}

		$data_format = array_values( $data_format );

		if ( ! $item ) {
			if ( $data['ref_type'] === LP_COURSE_CPT && empty( $data['parent_id'] ) ) {
				return false;
			}
			$wpdb->insert(
				$wpdb->learnpress_user_items,
				$data,
				$data_format
			);
			$user_item_id = $wpdb->insert_id;
			$item         = $this->get_user_item_by_id( $user_item_id );
		} else {
			$user_item_id = $item['user_item_id'];
			$wpdb->update(
				$wpdb->learnpress_user_items,
				$data,
				array( 'user_item_id' => $user_item_id ),
				$data_format,
				array( '%d' )
			);
			$item = array_merge( $item, $data );
		}

		if ( $user_item_id ) {
			// Track last status if it is updated new status.
			if ( $new_status !== false ) {
				if ( $this->update_user_item_status( $user_item_id, $new_status ) ) {
					$item['status'] = $new_status;
				}
			}

			// Update cache
			$existed = false !== ( $items = wp_cache_get( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, 'lp-user-course-items' ) );

			if ( false === $items || empty( $items[ $user_item_id ] ) ) {
				settype( $items, 'array' );
				$items[ $user_item_id ] = $item;
			} else {
				$items = array( $user_item_id => $item ) + $items;
			}

			if ( $existed ) {
				wp_cache_replace( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, $items, 'lp-user-course-items' );
			} else {
				wp_cache_add( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, $items, 'lp-user-course-items' );
			}

		}

		return $user_item_id;
	}

	/**
	 * Get user item from user_items tables.
	 * If course_id is not passed then return course,
	 * otherwise return item in that course.
	 *
	 * @param int $user_id
	 * @param int $item_id
	 * @param int $course_id - Optional. If passed then $item_id should be id of a course's item (such as lesson, quiz, etc...)
	 *
	 * @return bool|mixed
	 */
	public function get_user_item( $user_id, $item_id, $course_id = 0, $last = true ) {
		$this->read_course( $user_id, $course_id );
		$item = wp_cache_get( 'course-' . $user_id . '-' . $course_id, 'lp-user-courses' );
		if ( ! $item_id ) {

		} elseif ( $item ) {
			//$cache_name = sprintf( 'course-item-%d-%d-%d', $user_id, $course_id, $item['item_id'] );
			$cache_name = sprintf( 'course-item-%d-%d-%d', $user_id, $course_id, $item_id );
			$item       = wp_cache_get( $cache_name, 'lp-user-course-items' );
		}
		if ( $last && $item ) {
			$item = reset( $item );
		}

		return $item;
	}

	public function get_user_item_by_id( $user_item_id ) {
		global $wpdb;

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->learnpress_user_items} WHERE user_item_id = %d", $user_item_id ), ARRAY_A );
	}

	public function get_user_item_meta( $user_item_id, $meta_key, $single = true ) {
		return learn_press_get_user_item_meta( $user_item_id, $meta_key, $single );
	}

	public function update_user_item_meta( $user_item_id, $meta_key, $meta_value, $prev = '' ) {
		return learn_press_update_user_item_meta( $user_item_id, $meta_key, $meta_value, $prev );
	}

	/**
	 * Update user item data by id.
	 *
	 * @param int   $user_item_id
	 * @param array $item_data
	 *
	 * @return bool
	 */
	public function update_user_item_by_id( $user_item_id, $item_data = array() ) {
		global $wpdb;

		$item = $this->get_user_item_by_id( $user_item_id );

		if ( ! $item ) {
			return false;
		}

		// Table fields
		$table_fields = array(
			'user_item_id' => '%d',
			'user_id'      => '%d',
			'item_id'      => '%d',
			'ref_id'       => '%d',
			'start_time'   => '%s',
			'end_time'     => '%s',
			'item_type'    => '%s',
			'status'       => '%s',
			'ref_type'     => '%s',
			'parent_id'    => '%d'
		);

		// Data and format
		$data        = array();
		$data_format = array();

		// Update it later...
		$new_status = false;
		if ( array_key_exists( 'status', $item_data ) && $item_data['status'] != $item['status'] ) {
			$new_status = $item_data['status'];
			unset( $item_data['status'] );
		}

		// Build data and data format
		foreach ( $item_data as $field => $value ) {
			if ( ! empty( $table_fields[ $field ] ) ) {
				$data[ $field ]        = $value;
				$data_format[ $field ] = $table_fields[ $field ];
			}
		}

		$updated = $wpdb->update(
			$wpdb->learnpress_user_items,
			$data,
			array( 'user_item_id' => $user_item_id ),
			$data_format,
			array( '%d' )
		);

		// Track last status if it is updated new status.
		if ( $new_status !== false ) {
			$this->update_user_item_status( $user_item_id, $new_status );
		}

		return $updated;
	}

	/**
	 * Update status of an user item by id.
	 *
	 * @param int    $user_item_id
	 * @param string $new_status
	 *
	 * @return mixed
	 */
	public function update_user_item_status( $user_item_id, $new_status ) {
		global $wpdb;
		$item = $this->get_user_item_by_id( $user_item_id );

		if ( ! $item ) {
			return false;
		}

		// No need to update if it is not change
		if ( $item['status'] === $new_status ) {
			return false;
		}

		$updated = $wpdb->update( $wpdb->learnpress_user_items, array( 'status' => $new_status ), array( 'user_item_id' => $user_item_id ), array( '%s' ), array( '%d' ) );

		if ( $updated ) {
			$this->update_user_item_meta( $user_item_id, '_last_status', $item['status'] );
			$this->update_user_item_meta( $user_item_id, '_current_status', $new_status );
		}

		return $updated;
	}

	/**
	 * Delete user item by fields.
	 *
	 * @param string $args
	 *
	 * @return bool
	 */
	public function delete_user_item( $args = '' ) {
		global $wpdb;
		$args  = wp_parse_args(
			$args,
			array(
				'user_id'   => 0,
				'item_id'   => 0,
				'ref_id'    => 0,
				'parent_id' => 0
			)
		);
		$where = array();
		foreach ( $args as $k => $v ) {
			if ( $v ) {
				$where[ $k ] = $v;
			}
		}

		if ( ! $where ) {
			return false;
		}
		$query_where = array();
		foreach ( $where as $k => $v ) {
			$query_where[] = "{$k} = %d";
		}

		$query         = $wpdb->prepare( "SELECT user_item_id FROM {$wpdb->learnpress_user_items} WHERE " . join( ' AND ', $query_where ), array_values( $where ) );
		$user_item_ids = $wpdb->get_col( $query );
		$wpdb->delete(
			$wpdb->learnpress_user_items,
			$where,
			array_fill( 0, sizeof( $where ), '%d' )
		);

		foreach ( $user_item_ids as $user_item_id ) {
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->learnpress_user_itemmeta} WHERE learnpress_user_item_id = %d", $user_item_id ) );
			do_action( 'learn-press/deleted-user-item', $user_item_id );
		}

		return true;
	}

	public function query_courses( $user_id, $args = '' ) {
		global $wpdb;

		$args = wp_parse_args(
			$args, array(
				'paged' => 1,
				'limit' => 10,
			)
		);

		$cache_key = sprintf( 'courses-%d-%s', $user_id, md5( build_query( $args ) ) );

		if ( false === ( $courses = wp_cache_get( $cache_key, 'lp-user-courses' ) ) ) {

			$orders = $this->get_orders( $user_id );
			$query  = array( 'total' => 0, 'pages' => 0, 'items' => false );
			if ( ! $orders ) {
				return $query;
			}

			$course_ids = array_keys( $orders );
			$format     = array_fill( 0, sizeof( $course_ids ), '%d' );

			$sql = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->learnpress_user_items}
				WHERE item_id IN(" . join( ',', $format ) . ")
				ORDER BY user_item_id DESC
			", $course_ids );

			$items = $wpdb->get_results( $sql, ARRAY_A );
			if ( $items ) {
				$courses = array();
				foreach ( $items as $item ) {
					$courses[] = $this->read_course_info( $item );
				}
			}
		}
		learn_press_debug( $courses );
	}

	public function read_course_info( $course ) {
		$data = $course;
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
		", $course['user_item_id'] );

		if ( $itemmeta = $wpdb->get_results( $query, ARRAY_A ) ) {
			foreach ( $itemmeta as $item ) {
				$data[ $item['meta_key'] ] = $item['meta_value'];
			}
		}

		return $data;
	}

	public function update_item( $item ) {
		global $wpdb;
		if ( empty( $item['user_item_id'] ) ) {
			$wpdb->insert(
				$wpdb->learnpress_user_items,
				$item
			);
		}
	}

	public function get_current_user_order( $user_id, $course_id ) {
		global $wpdb;
		$sql      = $wpdb->prepare( "
			SELECT MAX(ID) AS order_id
			FROM {$wpdb->posts} p 
			INNER JOIN {$wpdb->postmeta} pm_u ON pm_u.post_id = p.ID AND pm_u.meta_key = %s AND pm_u.meta_value = %d
			INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_id = p.id
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.meta_key = %s and oim.meta_value = %d
		", '_user_id', $user_id, '_course_id', $course_id );
		$order_id = $wpdb->get_var( $sql );

		return $order_id;
	}

	public function evaluate_course_results() {

	}
}
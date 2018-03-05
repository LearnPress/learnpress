<?php

/**
 * Class LP_User_CURD
 *
 * @since 3.0
 */
class LP_User_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

	/**
	 * @var string
	 */
	protected $_meta_type = 'user';

	/**
	 * @var int
	 */
	protected $_user_id = 0;

	/**
	 * @var int
	 */
	protected $_course_id = 0;

	/**
	 * LP_User_CURD constructor.
	 *
	 * @param int $the_user
	 * @param int $the_course
	 */
	public function __construct( $the_user = 0, $the_course = 0 ) {
		$this->_user_id   = $the_user;
		$this->_course_id = $the_course;
		add_action( 'init', array( $this, 'init' ) );
	}

	public function init() {
		if ( $this->_user_id || $this->_course_id ) {
			if ( ! $this->_course_id ) {
				$this->_course_id = get_the_ID();
			}

			if ( ! $this->_user_id ) {
				$this->_user_id = get_current_user_id();
			}
		}


		if ( $user = learn_press_get_user( $this->_user_id ) ) {
			$this->load( $user );
			$this->read_course( $user->get_id(), $this->_course_id );
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
	 * @param LP_User|LP_Abstract_User $user
	 *
	 * @return mixed;
	 */
	public function load( &$user ) {
		$user_id = $user->get_id();
		if ( false !== ( $user_object = get_user_by( 'id', $user_id ) ) ) {
			$user->set_data(
				array(
					'email'             => $user_object->user_email,
					'user_login'        => $user_object->user_login,
					'description'       => $user_object->description,
					'first_name'        => isset( $user_object->first_name ) ? $user_object->first_name : '',
					'last_name'         => isset( $user_object->last_name ) ? $user_object->last_name : '',
					'nickname'          => isset( $user_object->nickname ) ? $user_object->nickname : '',
					'display_name'      => $user_object->display_name,
					'date_created'      => $user_object->user_registered,
					'date_modified'     => get_user_meta( $user_id, 'last_update', true ),
					'role'              => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'student',
					'roles'             => ! empty( $user_object->roles ) ? $user_object->roles : array( 'student' ),
					'profile_picture'   => get_user_meta( $user_id, '_lp_profile_picture', true ),
					'profile_publicity' => get_user_meta( $user_id, '_lp_profile_publicity', true ),
				)
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
	 * @param int   $user_id
	 * @param bool  $group_by_order - Optional. Group by order id instead of by course id
	 * @param array $args
	 *
	 * @return array|mixed
	 */
	public function get_orders( $user_id, $args = array() ) {

		// If user does not exists
		if ( ! $user = learn_press_get_user( $user_id ) ) {
			return false;
		}

		$cache_key = false;
		if ( $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'group_by_order' => false,
					'status'         => ''
				)
			);

			ksort( $args );
			$cache_key = md5( serialize( $args ) );

			/**
			 * Get orders from cache by args
			 */
			if ( false !== ( $orders = wp_cache_get( "user-{$user_id}-" . $cache_key, 'lp-user-orders' ) ) ) {
				LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

				return $orders;
			}
		}
		// Get orders for the user from cache
		$orders = wp_cache_get( 'user-' . $user_id, 'lp-user-orders' );

		if ( false === $orders ) {
			global $wpdb;

			$orders                = array();
			$post_status_in        = learn_press_get_order_statuses( true, true );
			$post_status_in_format = array_fill( 0, sizeof( $post_status_in ), '%s' );

			// Get order by user
			$sql_orders = $wpdb->prepare( "
				SELECT p.* 
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = %s AND meta_value = %d
			", '_user_id', $user_id );

			/**
			 * Get order checked out by Guest but with the email of the user are getting
			 */
			$sql_guest_orders = $wpdb->prepare( "
				SELECT p.* 
				FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = %s AND meta_value = %s
				LEFT JOIN {$wpdb->postmeta} pmu ON p.ID = pmu.post_id AND pmu.meta_key = %s AND pmu.meta_value IS NULL
			", '_checkout_email', $user->get_email(), '_user_id' );

			/**
			 * The rest
			 */
			$sql_rest = $wpdb->prepare( "
				HAVING p.post_type = %s
				AND p.post_status IN(" . join( ',', $post_status_in_format ) . ")
				ORDER BY ID DESC
			", array_merge( array(
				LP_ORDER_CPT
			), $post_status_in ) );

			$sql = $sql_orders . " UNION " . $sql_guest_orders . $sql_rest;

			if ( $order_posts = $wpdb->get_results( $sql ) ) {
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
			// Store to cache
			wp_cache_set( 'user-' . $user_id, $orders, 'lp-user-orders' );
		}

		if ( $orders ) {
			if ( array_key_exists( 'status', $args ) && $args['status'] ) {
				LP_Helper::sanitize_order_status( $args['status'] );
				foreach ( $orders as $course_id => $order_ids ) {
					$orders[ $course_id ] = array();
					foreach ( $order_ids as $order_id ) {
						if ( in_array( get_post_status( $order_id ), $args['status'] ) ) {
							$orders[ $course_id ][] = $order_id;
						}
					}
				}
			}

			if ( array_key_exists( 'group_by_order', $args ) && $args['group_by_order'] ) {
				$this->_group_orders( $orders );
			}
		}

		if ( $cache_key ) {
			wp_cache_set( "user-{$user_id}-" . $cache_key, $orders, 'lp-user-orders' );
		}

		LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

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
		LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( is_null( $course_id ) ) {
			$course_id = get_the_ID();
		}

		if ( is_numeric( $course_id ) ) {
			settype( $course_id, 'array' );
		}

		if ( ! $user_id ) {
			LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

			return false;
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

		for ( $n = sizeof( $fetch_ids ), $i = $n - 1; $i >= 0; $i -- ) {
			if ( ! $fetch_ids[ $i ] ) {
				unset( $fetch_ids[ $i ] );
			}
		}

		// There is no course ids to read
		if ( ! $fetch_ids ) {
			LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

			return false;
		}

		global $wpdb;

		$format = array_fill( 0, sizeof( $fetch_ids ), '%d' );
		$args   = array( '_status', LP_COURSE_CPT );
		$args   = array_merge( $args, $fetch_ids );
		$args[] = $user_id;


//		$user         = learn_press_get_user( $user_id );
//		$order_format = array();
//		if ( $user_orders = $user->get_orders( false ) ) {
//			if ( isset( $user_orders[ $course_id ] ) ) {
//				$order_ids    = $user_orders[ $course_id ];
//				$order_format = array_fill( 0, sizeof( $order_ids ), '%d' );
//				$args         = array_merge( $args, $order_ids );
//			}
//		}
//		print_r( $args );
//		echo "
//		SELECT *
//			FROM(
//				SELECT ui.*, uim.meta_value as `ext_status`
//				FROM {$wpdb->learnpress_user_items} ui
//				LEFT JOIN {$wpdb->learnpress_user_itemmeta} uim ON uim.learnpress_user_item_id = ui.user_item_id AND uim.meta_key = %s
//				WHERE item_type = %s AND item_id IN(" . join( ',', $format ) . ") AND user_id = %d
//				" . ( $order_format ? "OR ref_id IN(" . join( ',', $order_format ) . ")" : '' ) . "
//			) X
//		";
//		echo

		$query = $wpdb->prepare( "
			SELECT *
			FROM(
				SELECT ui.*, uim.meta_value as `ext_status`
				FROM {$wpdb->learnpress_user_items} ui
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} uim ON uim.learnpress_user_item_id = ui.user_item_id AND uim.meta_key = %s
				WHERE item_type = %s AND item_id IN(" . join( ',', $format ) . ") AND user_id = %d
			) X
			ORDER BY item_id, user_item_id DESC
		", $args );
		/*	SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE item_type = %s
			AND item_id IN()
			AND user_id = %d
			ORDER BY item_id, user_item_id DESC
		", $args );*/

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
//		if ( $fetch_ids ) {
//			$defaults = array(
//				'user_item_id'   => 0,
//				'user_id'        => $user_id,
//				'item_id'        => 0,
//				'start_time'     => '0000-00-00 00:00',
//				'start_time_gmt' => '0000-00-00 00:00',
//				'end_time'       => '0000-00-00 00:00',
//				'end_time_gmt'   => '0000-00-00 00:00',
//				'item_type'      => '',
//				'status'         => '',
//				'ref_type'       => '',
//				'ref_id'         => '',
//				'parent_id'      => 0,
//				'ext_status'     => '',
//				'items'          => array()
//			);
//			foreach ( $fetch_ids as $fetch_id ) {
//				wp_cache_set( 'course-' . $user_id . '-' . $fetch_id, $defaults, 'lp-user-courses' );
//			}
//		}
		LP_Debug::log_function( __CLASS__ . '::' . __FUNCTION__ );

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

		if ( $results = $wpdb->get_results( $query ) ) {
			$items    = array();
			$meta_ids = array();
			foreach ( $results as $result ) {
				$result       = (array) $result;
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
			///LP_Helper_CURD::update_meta_cache( 'learnpress_user_item', $meta_ids );

			foreach ( $items as $user_item_id => $_items ) {
				$cache_name = sprintf( 'course-item-%d-%d-%d', $parent_item['user_id'], $parent_item['item_id'], $user_item_id );
				// Refresh caching
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

		if ( $meta = $wpdb->get_results( $query, ARRAY_A ) ) {
			$item['meta'] = array();
			foreach ( $meta as $k => $v ) {
				$v['meta_value']               = maybe_unserialize( $v['meta_value'] );
				$item['meta'][ $v['meta_id'] ] = $v;
			}
		}
	}

	public function update_user_item_course() {

	}

	public function update_user_item_course_item() {

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

		$user = learn_press_get_user( $user_id );
		if ( ! $user || ( $user->get_id() != $user_id ) ) {
			return false;
		}

		if ( array_key_exists( 'user_item_id', $item_data ) && empty( $item_data['user_item_id'] ) ) {
			$item = false;
		} else {
			if ( func_num_args() == 4 ) {
				$item = $this->get_user_item( $user_id, $item_id, $course_id );
			} else {
				$item = $this->get_user_item( $user_id, $item_id );
			}
		}

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

		if ( ! empty( $item_data['start_time'] ) && empty( $item_data['start_time_gmt'] ) ) {
			$start_time = new LP_Datetime( $item_data['start_time'] );

			$item_data['start_time_gmt'] = $start_time->toSql( false );
		}

		if ( ! empty( $item_data['end_time'] ) && empty( $item_data['end_time_gmt'] ) ) {
			$start_time = new LP_Datetime( $item_data['end_time'] );

			$item_data['end_time_gmt'] = $start_time->toSql( false );
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

		if ( ! $item || empty( $item['user_item_id'] ) ) {
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

		$is_course = ( ! $course_id ) && ( get_post_type( $item['item_id'] ) === LP_COURSE_CPT );

		if ( $user_item_id ) {

			// Track last status if it is updated new status.
			if ( $new_status !== false ) {
				if ( $this->update_user_item_status( $user_item_id, $new_status ) ) {
					$item['status'] = $new_status;
				}
			}

			if ( $is_course ) {
				$course_id = $item['item_id'];
				// Update cache to effect the change right way!
				wp_cache_set( 'course-' . $user_id . '-' . $course_id, $item, 'lp-user-courses' );
			} else {

				$user        = learn_press_get_user( $user_id );
				$user_course = $user->get_course_data( $course_id );

				$user_course->set_item( $item );

				// Update cache
				$existed = false !== ( $items = wp_cache_get( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, 'lp-user-course-items' ) );

				if ( false === $items || ! empty( $items[ $user_item_id ] ) ) {
					if ( is_array( $items ) ) {
						$items[ $user_item_id ] = $item;
					} else {
						$items = array( $user_item_id => $item );
					}
				} else {
					$items = array( $user_item_id => $item ) + $items;
				}

				wp_cache_set( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, $items, 'lp-user-course-items' );

			}

			wp_cache_delete( 'course-' . $user_id . '-' . $course_id, 'lp-user-course-data' );

			/*if ( $existed ) {
				wp_cache_replace( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, $items, 'lp-user-course-items' );
			} else {
				wp_cache_add( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, $items, 'lp-user-course-items' );
			}*/
		}

		return $user_item_id;
	}

	/**
	 * Get user item from user_items tables.
	 * If course_id is not passed then return course,
	 * otherwise return item in that course.
	 *
	 * @param int  $user_id
	 * @param int  $item_id
	 * @param int  $course_id - Optional. If passed then $item_id should be id of a course's item (such as lesson, quiz, etc...)
	 * @param bool $last
	 *
	 * @return bool|mixed
	 */
	public function get_user_item( $user_id, $item_id, $course_id = 0, $last = true ) {

		$num_args = func_num_args();

		if ( $num_args == 2 ) {
			$this->read_course( $user_id, $item_id );
			$item = wp_cache_get( 'course-' . $user_id . '-' . $item_id, 'lp-user-courses' );
		} else {
			$this->read_course( $user_id, $course_id );
			$cache_name = sprintf( 'course-item-%d-%d-%d', $user_id, $course_id, $item_id );
			$item       = wp_cache_get( $cache_name, 'lp-user-course-items' );

			if ( $last && $item ) {
				$item = reset( $item );
			}
		}

		return $item;
	}

	/**
	 * @param int    $user_item_id
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_user_item_by_id( $user_item_id, $type = '' ) {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT * 
			FROM {$wpdb->learnpress_user_items} 
			WHERE user_item_id = %d
			" . ( $type ? $wpdb->prepare( "AND item_type = %s", $type ) : '' ) . "
		", $user_item_id );
		$item  = $wpdb->get_row( $query, ARRAY_A );

		return $item;
	}

	/**
	 * @param int $user_item_id
	 *
	 * @return bool|LP_User_Item_Course
	 */
	public function get_user_item_course( $user_item_id ) {
		if ( $item = $this->get_user_item_by_id( $user_item_id, LP_COURSE_CPT ) ) {
			return new LP_User_Item_Course( $item );
		}

		return false;
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
			'user_item_id'   => '%d',
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

		if ( $data ) {
			$updated = $wpdb->update(
				$wpdb->learnpress_user_items,
				$data,
				array( 'user_item_id' => $user_item_id ),
				$data_format,
				array( '%d' )
			);
		}

		// Track last status if it is updated new status.
		if ( $new_status !== false ) {
			$this->update_user_item_status( $user_item_id, $new_status );
		}

		return isset( $updated ) ? $updated : $user_item_id;
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

	public function restore_last_status( $user_item_id ) {
		$item = $this->get_user_item_by_id( $user_item_id );
		if ( ! $item ) {
			return false;
		}

		if ( $last_status = $this->get_user_item_meta( $item['user_item_id'], '_last_status', true ) ) {
			$this->update_user_item_status( $user_item_id, $last_status );
			learn_press_delete_user_item_meta( $user_item_id, '_last_status', '', true );
		}

		return $last_status;
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

	/**
	 * Delete a row from user items by user_item_id key.
	 *
	 * @param int $user_item_id
	 *
	 * @return mixed
	 */
	public function delete_by_user_item_id( $user_item_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE parent_id = %d
		", $user_item_id );

		// Delete child items (lessons, quizzes, etc) in case the item being deleted is a course
		if ( $child = $wpdb->get_col( $query ) ) {
			foreach ( $child as $child_id ) {
				$this->delete_by_user_item_id( $child_id );
			}
		}

		// Delete origin item
		$query = $wpdb->prepare( "
			DELETE
			FROM ui, uim
			USING {$wpdb->prefix}learnpress_user_items AS ui
			LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta AS uim ON ui.user_item_id = uim.learnpress_user_item_id
			WHERE user_item_id = %d
		", $user_item_id );

		return $wpdb->query( $query );
	}

	/**
	 * Query own courses of an user.
	 *
	 * @param int    $user_id
	 * @param string $args
	 *
	 * @return LP_Query_List_Table
	 */
	public function query_own_courses( $user_id, $args = '' ) {
		global $wpdb, $wp;
		$paged = 1;

		if ( ! empty( $wp->query_vars['view_id'] ) ) {
			$paged = absint( $wp->query_vars['view_id'] );
		}

		$paged = max( $paged, 1 );
		$args  = wp_parse_args(
			$args, array(
				'paged'  => $paged,
				'limit'  => 10,
				'status' => ''
			)
		);

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$cache_key = sprintf( 'own-courses-%d-%s', $user_id, md5( build_query( $args ) ) );

		if ( false === ( $courses = wp_cache_get( $cache_key, 'lp-user-courses' ) ) ) {

			$courses = array(
				'total' => 0,
				'paged' => $args['paged'],
				'limit' => $args['limit'],
				'pages' => 0,
				'items' => array()
			);

			try {

				//$orders = $this->get_orders( $user_id );
				$query = array( 'total' => 0, 'pages' => 0, 'items' => false );

//				//if ( ! $orders ) {
//				//	throw new Exception( "Error", 0 );
//				//}
//
//				//$course_ids   = array_keys( $orders );
//				//$query_args   = $course_ids;
//				$query_args[] = $user_id;
				$limit  = $args['limit'];
				$where  = "WHERE 1";
				$offset = ( $args['paged'] - 1 ) * $limit;

				if ( ! empty( $args['status'] ) ) {
					if ( is_array( $args['status'] ) ) {
						$a     = array_fill( 0, sizeof( $where ), '%d' );
						$where .= $wpdb->prepare( " AND post_status IN(" . join( ',', $where ) . ")", $a );
					} else {
						if ( 'pending' === $args['status'] ) {
							$where .= $wpdb->prepare( " AND post_status IN( %s, %s )", array( 'draft', 'pending' ) );
						} else {
							$where .= $wpdb->prepare( " AND post_status = %s", $args['status'] );
						}
					}
				} else {
					$where .= $wpdb->prepare( " AND post_status NOT IN (%s, %s)", array( 'trash', 'auto-draft' ) );
				}

				$where = $where . $wpdb->prepare( " AND post_type = %s AND post_author = %d", LP_COURSE_CPT, $user_id );
				$sql   = "
					SELECT SQL_CALC_FOUND_ROWS ID
					FROM {$wpdb->posts} c
					{$where} 
					LIMIT {$offset}, {$limit}
				";

				$items = $wpdb->get_results( $sql );

				if ( $items ) {
					$count      = $wpdb->get_var( "SELECT FOUND_ROWS()" );
					$course_ids = wp_list_pluck( $items, 'ID' );
					LP_Helper::cache_posts( $course_ids );

					$courses['total'] = $count;
					$courses['pages'] = ceil( $count / $args['limit'] );
					foreach ( $items as $item ) {
						$courses['items'][] = $item->ID;
					}
				}
			}
			catch ( Exception $ex ) {
				learn_press_add_message( $ex->getMessage() );
			}

			wp_cache_set( $cache_key, $courses, 'lp-user-courses' );
		}

		$courses['single'] = __( 'course', 'learnpress' );
		$courses['plural'] = __( 'courses', 'learnpress' );

		return new LP_Query_List_Table( $courses );
	}

	/**
	 * Query courses by user
	 *
	 * @param int    $user_id
	 * @param string $args
	 *
	 * @return LP_Query_List_Table
	 */
	public function query_purchased_courses( $user_id = 0, $args = '' ) {
		global $wpdb, $wp;
		$paged = 1;

		if ( ! empty( $wp->query_vars['view_id'] ) ) {
			$paged = absint( $wp->query_vars['view_id'] );
		}

		$args = wp_parse_args(
			$args,
			array(
				'paged'  => $paged,
				'limit'  => 10,
				'status' => ''
			)
		);

		$args['paged'] = max( $args['paged'], 1 );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$cache_key = sprintf( 'purchased-courses-%d-%s', $user_id, md5( build_query( $args ) ) );

		if ( false === ( $courses = wp_cache_get( $cache_key, 'lp-user-courses' ) ) ) {

			$courses = array(
				'total' => 0,
				'paged' => $args['paged'],
				'limit' => $args['limit'],
				'pages' => 0,
				'items' => array()
			);

			try {

				$orders = $this->get_orders( $user_id, array( 'status' => 'completed processing cancelled' ) );
				$query  = array( 'total' => 0, 'pages' => 0, 'items' => false );

				$orders = $this->get_orders( $user_id, array(
					'status'         => 'completed processing cancelled',
					'group_by_order' => true
				) );

				if ( ! $orders ) {
					throw new Exception( "", 0 );
				}

				$valid_orders = array_keys( $orders );
				$course_ids   = array_keys( $orders );
				$query_args   = $course_ids;
				$query_args[] = $user_id;
				$limit        = $args['limit'];
				$offset       = ( $args['paged'] - 1 ) * $limit;

				$order_format = array_fill( 0, sizeof( $valid_orders ), '%d' );

				$select = "SELECT ui.* ";
				$from   = "FROM {$wpdb->learnpress_user_items} ui";
				$join   = $wpdb->prepare( "INNER JOIN {$wpdb->posts} c ON c.ID = ui.item_id AND c.post_type = %s", LP_COURSE_CPT );
				//$where   = $wpdb->prepare( "WHERE 1 AND user_id = %d AND ref_id IN(" . join( ',', $order_format ) . ")", array_merge( array( $user_id ), $valid_orders ) );
				$where   = $wpdb->prepare( "WHERE 1 AND user_id = %d", $user_id );
				$having  = "HAVING 1";
				$orderby = "ORDER BY item_id, user_item_id DESC";

				if ( ! empty( $args['status'] ) ) {
					switch ( $args['status'] ) {
						case 'finished':
						case 'passed':
						case 'failed':

							$where .= $wpdb->prepare( " AND ui.status IN( %s )", array(
								'finished'
							) );

							if ( $args['status'] !== 'finished' ) {
								$select .= ", uim.meta_value AS grade";
								$join   .= $wpdb->prepare( "
									LEFT JOIN {$wpdb->learnpress_user_itemmeta} uim ON uim.learnpress_user_item_id = ui.user_item_id AND uim.meta_key = %s
								", 'grade' );

								if ( 'passed' === $args['status'] ) {
									$having .= $wpdb->prepare( " AND grade = %s", 'passed' );
								} else {
									$having .= $wpdb->prepare( " AND ( grade IS NULL OR grade = %s )", 'failed' );
								}
							}

							break;
						case 'not-enrolled':
							$where .= $wpdb->prepare( " AND ui.status NOT IN( %s, %s, %s )", array(
								'enrolled',
								'finished',
								'pending'
							) );
					}
				}

				$where .= $wpdb->prepare( " AND ui.status NOT IN(%s)", 'pending' );

				$query_parts = apply_filters(
					'learn-press/query/user-purchased-courses',
					compact( 'select', 'from', 'join', 'where', 'having', 'orderby' ),
					$user_id,
					$args
				);
				list( $select, $from, $join, $where, $having, $orderby ) = array_values( $query_parts );

				$sql = "
					SELECT SQL_CALC_FOUND_ROWS *
					FROM
					(	
						{$select}
						{$from}
						{$join}
						{$where}
						{$having}
						{$orderby}
					) X GROUP BY item_id
					LIMIT {$offset}, {$limit}
				";

				$items = $wpdb->get_results( $sql, ARRAY_A );

				if ( $items ) {
					$count      = $wpdb->get_var( "SELECT FOUND_ROWS()" );
					$course_ids = wp_list_pluck( $items, 'item_id' );
					LP_Helper::cache_posts( $course_ids );

					$courses['total'] = $count;
					$courses['pages'] = ceil( $count / $args['limit'] );
					foreach ( $items as $item ) {
						$courses['items'][] = new LP_User_Item_Course( $item );
					}
				}
			}
			catch ( Exception $ex ) {

			}

			wp_cache_set( $cache_key, $courses, 'lp-user-courses' );
		}

		$courses['single'] = __( 'course', 'learnpress' );
		$courses['plural'] = __( 'courses', 'learnpress' );

		return new LP_Query_List_Table( $courses );
	}

	/**
	 * Query quizzes by user.
	 *
	 * @param int    $user_id
	 * @param string $args
	 *
	 * @return LP_Query_List_Table
	 */
	public function query_quizzes( $user_id = 0, $args = '' ) {
		global $wpdb, $wp;
		$paged = 1;
		if ( ! empty( $wp->query_vars['view_id'] ) ) {
			$paged = absint( $wp->query_vars['view_id'] );
		}
		$paged = max( $paged, 1 );
		$args  = wp_parse_args(
			$args, array(
				'paged'  => $paged,
				'limit'  => 10,
				'status' => ''
			)
		);

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$cache_key = sprintf( 'quizzes-%d-%s', $user_id, md5( build_query( $args ) ) );

		if ( false === ( $quizzes = wp_cache_get( $cache_key, 'lp-user-quizzes' ) ) ) {

			$orders = $this->get_orders( $user_id );
			$query  = array( 'total' => 0, 'pages' => 0, 'items' => false );

			$quizzes = array(
				'total' => 0,
				'paged' => $args['paged'],
				'limit' => $args['limit'],
				'pages' => 0,
				'items' => array()
			);

			try {
				if ( ! $orders ) {
					throw new Exception( "", 0 );
				}

				$course_ids   = array_keys( $orders );
				$query_args   = $course_ids;
				$query_args[] = $user_id;

				$select  = "SELECT ui.* ";
				$from    = "FROM {$wpdb->learnpress_user_items} ui";
				$join    = $wpdb->prepare( "INNER JOIN {$wpdb->posts} c ON c.ID = ui.item_id AND c.post_type = %s", LP_QUIZ_CPT );
				$where   = $wpdb->prepare( "WHERE 1 AND user_id = %d", $user_id );
				$having  = "HAVING 1";
				$orderby = "ORDER BY item_id, user_item_id DESC";

				if ( ! empty( $args['status'] ) ) {
					switch ( $args['status'] ) {
						case 'completed':
						case 'passed':
						case 'failed':

							$where .= $wpdb->prepare( " AND ui.status IN( %s )", array(
								'completed'
							) );

							if ( $args['status'] !== 'completed' ) {
								$select .= ", uim.meta_value AS grade";
								$join   .= $wpdb->prepare( "
									LEFT JOIN {$wpdb->learnpress_user_itemmeta} uim ON uim.learnpress_user_item_id = ui.user_item_id AND uim.meta_key = %s
								", 'grade' );

								if ( 'passed' === $args['status'] ) {
									$having .= $wpdb->prepare( " AND grade = %s", 'passed' );
								} else {
									$having .= $wpdb->prepare( " AND ( grade IS NULL OR grade <> %s )", 'passed' );
								}
							}

							break;
						case 'not-started':
							$where .= $wpdb->prepare( " AND ui.status NOT IN( %s, %s )", array(
								'started',
								'completed'
							) );
					}
				}

				$limit  = $args['limit'];
				$offset = ( $args['paged'] - 1 ) * $limit;

				$query_parts = apply_filters(
					'learn-press/query/user-quizzes',
					compact( 'select', 'from', 'join', 'where', 'having', 'orderby' ),
					$user_id,
					$args
				);

				list( $select, $from, $join, $where, $having, $orderby ) = array_values( $query_parts );

				$sql = "
					SELECT SQL_CALC_FOUND_ROWS *
					FROM
					(
						{$select}
						{$from}
						{$join}
						{$where}
						{$having}
						{$orderby}
					) X GROUP BY item_id
					LIMIT {$offset}, {$limit}
				";

				$items = $wpdb->get_results( $sql, ARRAY_A );

				if ( $items ) {
					$count      = $wpdb->get_var( "SELECT FOUND_ROWS()" );
					$course_ids = wp_list_pluck( $items, 'item_id' );
					LP_Helper::cache_posts( $course_ids );

					$quizzes['total'] = $count;
					$quizzes['pages'] = ceil( $count / $args['limit'] );
					foreach ( $items as $item ) {
						$quizzes['items'][] = new LP_User_Item_Quiz( $item );
					}
				}
			}
			catch ( Exception $ex ) {

			}
			wp_cache_set( $cache_key, $quizzes, 'lp-user-course' );
		}

		$quizzes['single'] = __( 'quiz', 'learnpress' );
		$quizzes['plural'] = __( 'quizzes', 'learnpress' );

		return new LP_Query_List_Table( $quizzes );
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

	public function duplicate( &$user, $args = array() ) {
		// TODO: Implement duplicate() method.
	}

	public static function create_user( $email, $username, $password ) {
		if ( empty( $email ) || ! is_email( $email ) ) {
			return new WP_Error( 'registration-error-invalid-email', __( 'Please provide a valid email address.', 'learnpress' ) );
		}

		if ( email_exists( $email ) ) {
			return new WP_Error( 'registration-error-email-exists', __( 'An account is already registered with your email address. Please log in.', 'learnpress' ) );
		}

		$username = sanitize_user( $username );

		if ( empty( $username ) || ! validate_username( $username ) ) {
			return new WP_Error( 'registration-error-invalid-username', __( 'Please enter a valid account username.', 'learnpress' ) );
		}

		if ( username_exists( $username ) ) {
			return new WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another.', 'learnpress' ) );
		}

		$new_user = wp_insert_user(
			array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email
			)
		);

		if ( is_wp_error( $new_user ) ) {
			return new WP_Error( 'registration-error', __( 'Failed to create user', 'learnpress' ) );
		}

		return $new_user;
	}
}
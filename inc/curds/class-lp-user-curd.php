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
	}

	/**
	 * Get courses of an user.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @since 3.3.0
	 * @deprecated 4.2.4
	 */
	public function get_courses( $args = array() ) {
		_deprecated_function( __METHOD__, '4.2.4' );

		global $wpdb;

		$no_join_users = isset( $args['no_join_users'] ) && $args['no_join_users'];
		$args          = wp_parse_args(
			$args,
			array(
				'offset'   => 0,
				'limit'    => 10,
				'expired'  => false,
				'status'   => '',
				'user_id'  => 0,
				'paginate' => false,
			)
		);

		// Join
		$join = "
			INNER JOIN {$wpdb->posts} p ON p.ID = X.item_id
		";

		if ( ! $no_join_users ) {
			$join .= "
				INNER JOIN {$wpdb->users} u ON u.ID = X.user_id
			";
		}

		$join .= $wpdb->prepare(
			"LEFT JOIN {$wpdb->learnpress_user_itemmeta} uim ON uim.learnpress_user_item_id = X.user_item_id AND uim.meta_key = %s",
			'grade'
		);

		// Where
		$where = $wpdb->prepare(
			'WHERE 1
		    AND X.item_type = %s
		',
			LP_COURSE_CPT
		);

		if ( $args['user_id'] ) {

			if ( ! is_array( $args['user_id'] ) ) {
				$user__in = preg_split( '!\s*,\s*!', $args['user_id'] );
			} else {
				$user__in = $args['user_id'];
			}

			$where .= 'AND u.ID IN(' . join( ',', $user__in ) . ')';
		}

		// Limitation
		$limit = $args['limit'] > 0 ? '
			LIMIT ' . $args['offset'] . ', ' . $args['limit'] : '';

		if ( $args['status'] ) {

			if ( ! is_array( $args['status'] ) ) {
				$status__in = preg_split( '!\s*,\s*!', $args['status'] );
			} else {
				$status__in = $args['status'];
			}

			$where .= " AND ( X.status IN('" . join( "','", $status__in ) . "')";

			$status__in_1 = array();
			$status__in_2 = array();

			if ( in_array( 'failed', $status__in ) ) {
				$status__in_1[] = 'finished';
				$status__in_2[] = 'failed';
			}

			if ( in_array( 'passed', $status__in ) ) {
				$status__in_1[] = 'finished';
				$status__in_2[] = 'passed';
			}

			if ( in_array( 'in-progress', $status__in ) ) {
				$status__in_1[] = 'enrolled';
				$status__in_2[] = 'in-progress';
			}

			if ( in_array( 'finished', $status__in ) ) {
				$status__in_1[] = 'passed';
				$status__in_1[] = 'failed';
				$status__in_2[] = 'passed';
				$status__in_2[] = 'failed';
			}

			if ( $status__in_1 || $status__in_2 ) {

				$where .= " OR (
					X.status IN('" . join( "','", $status__in_1 ) . "')";

				if ( ! in_array( 'in-progress', $status__in ) ) {
					$where .= "AND ( uim.meta_value IN('" . join( "','", $status__in_2 ) . "')
						" . ( in_array( 'in-progress', $status__in ) ? ' OR uim.meta_value IS NULL' : '' ) . ')';
				}
				$where .= ')';
			}

			$where .= ' )';

		}

		if ( $args['expired'] ) {
			$where .= ' AND expiration_time <= UTC_TIMESTAMP()';
		}

		if ( $args['paginate'] && ! empty( $limit ) ) {
			$found_rows = 'SQL_CALC_FOUND_ROWS';
		} else {
			$found_rows = '';
		}

		$query = $wpdb->prepare(
			"
			SELECT {$found_rows} p.ID course_id, X.*, IF(X.status = %s AND uim.meta_value IS NOT NULL, uim.meta_value, X.status) status
			FROM(
			SELECT ui.*
                FROM {$wpdb->learnpress_user_items} ui
				LEFT JOIN {$wpdb->learnpress_user_items} uix
					ON ui.item_id = uix.item_id
						AND ui.user_id = uix.user_id
						AND ui.user_item_id < uix.user_item_id
			    WHERE uix.user_item_id IS NULL
			) X
			$join
			$where
			$limit
		",
			'finished'
		);

		$rows = $wpdb->get_results( $query );

		// echo nl2br( $query );

		if ( $args['paginate'] && $args['limit'] ) {
			$found_courses = sizeof( $rows ) ? $wpdb->get_var( 'SELECT FOUND_ROWS()' ) : 0;

			$return = array(
				'courses'       => $rows,
				'found_courses' => $found_courses,
				'max_num_pages' => $found_courses ? ceil( $found_courses / $args['limit'] ) : 0,
			);
		} else {
			$return = $rows;
		}

		// $query = $wpdb->prepare( "
		// SELECT X.*
		// FROM(
		// SELECT ui.*
		// FROM {$wpdb->learnpress_user_items} ui
		// LEFT JOIN {$wpdb->learnpress_user_items} uix
		// ON ui.item_id = uix.item_id
		// AND ui.user_id = uix.user_id
		// AND ui.user_item_id < uix.user_item_id
		// WHERE uix.user_item_id IS NULL
		// ) X
		//
		// WHERE
		// AND X.status = %s
		// AND expiration_time_gmt <= UTC_TIMESTAMP()
		// LIMIT 0, 10
		// ", LP_COURSE_CPT, 'enrolled' );

		// echo nl2br( $query );
		// learn_press_debug( $return );

		return $return;
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
		$user_id     = $user->get_id();
		$user_object = get_user_by( 'id', $user_id );

		if ( false !== $user_object ) {
			$user->set_data(
				array(
					'email'           => $user_object->user_email,
					'user_login'      => $user_object->user_login,
					'description'     => $user_object->description,
					'first_name'      => $user_object->first_name ?? '',
					'last_name'       => $user_object->last_name ?? '',
					'nickname'        => $user_object->nickname ?? '',
					'display_name'    => $user_object->display_name,
					'date_created'    => $user_object->user_registered,
					'date_modified'   => get_user_meta( $user_id, 'last_update', true ),
					'role'            => $user_object->roles[0] ?? '',
					'roles'           => $user_object->roles,
					'profile_picture' => get_user_meta( $user_id, '_lp_profile_picture', true ),
					'profile_privacy' => get_user_meta( $user_id, '_lp_profile_privacy', true ),
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
	 * @param int $user_id
	 * @param array $args
	 *
	 * @return array|mixed
	 */
	public function get_orders( $user_id, $args = array() ) {
		$user = learn_press_get_user( $user_id );

		if ( ! $user_id || ! $user ) {
			return false;
		}

		$cache_key = false;
		if ( $args ) {
			$args = wp_parse_args(
				$args,
				array(
					'group_by_order' => false,
					'status'         => '',
				)
			);

			ksort( $args );
			$cache_key = md5( serialize( $args ) );

			/**
			 * Get orders from cache by args
			 */
			$orders = LP_Object_Cache::get( "user-{$user_id}-" . $cache_key, 'lp-user-orders' );

			if ( false !== $orders ) {
				return $orders;
			}
		}

		// Get orders for the user from cache
		$orders = LP_Object_Cache::get( 'user-' . $user_id, 'lp-user-orders' );

		if ( false === $orders ) {
			global $wpdb;

			$orders                = array();
			$post_status_in        = array_keys( LP_Order::get_order_statuses() );
			$post_status_in_format = array_fill( 0, sizeof( $post_status_in ), '%s' );
			$user_id_str           = $wpdb->prepare( '%"%d"%', $user_id );

			// Get order by user
			$sql_orders = $wpdb->prepare(
				"
				SELECT p.*
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = %s AND (meta_value = %d OR meta_value like '%s')
				",
				'_user_id',
				$user_id,
				$user_id_str
			);

			/**
			 * Get order checked out by Guest but with the email of the user are getting
			 * Error when checkout with guest then register error Start Quiz - nhamdv.
			 */
			// $sql_guest_orders = $wpdb->prepare(
			// "
			// SELECT p.*
			// FROM {$wpdb->posts} p
			// INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = %s AND meta_value = %s
			// LEFT JOIN {$wpdb->postmeta} pmu ON p.ID = pmu.post_id AND pmu.meta_key = %s AND pmu.meta_value IS NULL
			// ",
			// '_checkout_email',
			// $user->get_email(),
			// '_user_id'
			// );

			/**
			 * The rest
			 */
			$sql_rest = $wpdb->prepare(
				'
				HAVING p.post_type = %s
				AND p.post_status IN(' . join( ',', $post_status_in_format ) . ')
				ORDER BY ID DESC
			',
				array_merge(
					array(
						LP_ORDER_CPT,
					),
					$post_status_in
				)
			);

			$sql = $sql_orders /* . ' UNION ' . $sql_guest_orders */ . $sql_rest;

			$order_posts = $wpdb->get_results( $sql );

			if ( $order_posts ) {
				$order_ids = array();

				foreach ( $order_posts as $order_post ) {
					$_post = sanitize_post( $order_post, 'raw' );
					wp_cache_add( $_post->ID, $_post, 'posts' );

					$order_ids[] = $_post->ID;
				}

				$order_ids_format = array_fill( 0, sizeof( $order_ids ), '%d' );
				$query            = $wpdb->prepare(
					"
						SELECT meta_value as course_id, order_id
						FROM {$wpdb->learnpress_order_items} oi
						INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = %s
						WHERE oi.order_id IN (" . join( ',', $order_ids_format ) . ')
						ORDER BY FIELD(order_id, ' . join( ',', $order_ids_format ) . ')
					',
					array_merge( array( '_course_id' ), $order_ids, $order_ids )
				);

				$results = $wpdb->get_results( $query );

				if ( $results ) {
					foreach ( $results as $result ) {
						if ( empty( $orders[ $result->course_id ] ) ) {
							$orders[ $result->course_id ] = array();
						}
						$orders[ $result->course_id ][] = $result->order_id;
					}
				}
			}

			LP_Object_Cache::set( 'user-' . $user_id, $orders, 'lp-user-orders' );
		}

		if ( $orders ) {
			if ( array_key_exists( 'status', $args ) && $args['status'] ) {
				LP_Helper::sanitize_order_status( $args['status'] );

				$statuses = (array) $args['status'];
				foreach ( $orders as $course_id => $order_ids ) {
					$orders[ $course_id ] = array();
					foreach ( $order_ids as $order_id ) {
						if ( in_array( get_post_status( $order_id ), $statuses ) ) {
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
			LP_Object_Cache::set( "user-{$user_id}-" . $cache_key, $orders, 'lp-user-orders' );
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
	 * Read meta data of an user item.
	 *
	 * @param $item
	 *
	 * @deprecated 4.2.4
	 */
	protected function _read_item_meta( &$item ) {
		_deprecated_function( __METHOD__, '4.2.4' );
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
		",
			$item['user_item_id']
		);

		update_meta_cache( 'learnpress_user_item', $item['user_item_id'] );

		if ( $meta = $wpdb->get_results( $query, ARRAY_A ) ) {
			$item['meta'] = array();
			foreach ( $meta as $k => $v ) {
				$v['meta_value']               = LP_Helper::maybe_unserialize( $v['meta_value'] );
				$item['meta'][ $v['meta_id'] ] = $v;
			}
		}
	}

	/**
	 * @param int $user_item_id
	 * @param string $type
	 *
	 * @return array
	 */
	public function get_user_item_by_id( $user_item_id, $type = '' ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE user_item_id = %d
			" . ( $type ? $wpdb->prepare( 'AND item_type = %s', $type ) : '' ) . '
		',
			$user_item_id
		);
		$item  = $wpdb->get_row( $query, ARRAY_A );

		return $item;
	}

	/**
	 * Update status of an user item by id.
	 *
	 * @param int $user_item_id
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

		$updated = $wpdb->update(
			$wpdb->learnpress_user_items,
			array( 'status' => $new_status ),
			array( 'user_item_id' => $user_item_id ),
			array( '%s' ),
			array( '%d' )
		);
		// if ( $updated ) {
		// 	$this->update_user_item_meta( $user_item_id, '_last_status', $item['status'] );
		// 	$this->update_user_item_meta( $user_item_id, '_current_status', $new_status );
		// }

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
				'parent_id' => 0,
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

		$query         = $wpdb->prepare(
			"SELECT user_item_id FROM {$wpdb->learnpress_user_items} WHERE " . join(
				' AND ',
				$query_where
			),
			array_values( $where )
		);
		$user_item_ids = $wpdb->get_col( $query );

		$wpdb->delete(
			$wpdb->learnpress_user_items,
			$where,
			array_fill( 0, sizeof( $where ), '%d' )
		);

		foreach ( $user_item_ids as $user_item_id ) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->learnpress_user_itemmeta} WHERE learnpress_user_item_id = %d",
					$user_item_id
				)
			);
			do_action( 'learn-press/deleted-user-item', $user_item_id );
		}

		return true;
	}

	/**
	 * Get courses user has purchased via orders are completed
	 * but it is not already added to user-items table.
	 *
	 * @param int $user_id
	 *
	 * @return array|bool|mixed
	 * @deprecated 4.2.4
	 */
	public function query_courses_by_order( $user_id ) {
		_deprecated_function( __METHOD__, '4.2.4' );

		return false;
		global $wpdb;

		$query         = $wpdb->prepare(
			"
			SELECT item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE user_id = %d
			AND item_type = %s
		",
			$user_id,
			LP_COURSE_CPT
		);
		$user_item_ids = $wpdb->get_col( $query );

		$orders = $this->get_orders(
			$user_id,
			array(
				'status'         => 'completed',
				'group_by_order' => true,
			)
		);

		if ( ! $orders ) {
			return false;
		}

		$course_ids = array_shift( $orders );

		foreach ( $orders as $ids ) {
			if ( ! $ids ) {
				continue;
			}
			$course_ids = array_merge( $course_ids, $ids );
		}

		if ( $user_item_ids ) {
			$course_ids = array_diff( $course_ids, $user_item_ids );
		}

		return $course_ids;
	}

	/**
	 * @deprecated 4.2.4
	 */
	public function read_course_info( $course ) {
		_deprecated_function( __METHOD__, '4.2.4' );

		return [];
		$data = $course;
		global $wpdb;
		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_user_itemmeta}
			WHERE learnpress_user_item_id = %d
		",
			$course['user_item_id']
		);

		$itemmeta = $wpdb->get_results( $query, ARRAY_A );

		if ( $itemmeta ) {
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

	/**
	 * @deprecated 4.2.4
	 */
	public function get_current_user_order( $user_id, $course_id ) {
		_deprecated_function( __METHOD__, '4.2.4' );

		return 0;
		global $wpdb;
		$sql      = $wpdb->prepare(
			"
			SELECT MAX(ID) AS order_id
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm_u ON pm_u.post_id = p.ID AND pm_u.meta_key = %s AND pm_u.meta_value = %d
			INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_id = p.id
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.meta_key = %s and oim.meta_value = %d
		",
			'_user_id',
			$user_id,
			'_course_id',
			$course_id
		);
		$order_id = $wpdb->get_var( $sql );

		return $order_id;
	}

	public function duplicate( &$user, $args = array() ) {
		// TODO: Implement duplicate() method.
	}
}

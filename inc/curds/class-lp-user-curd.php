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

		// add_action( 'init', array( $this, 'init' ) );
	}

	/**
	 * Get courses of an user.
	 *
	 * @param array $args
	 *
	 * @return array
	 * @since 3.3.0
	 */
	public function get_courses( $args = array() ) {

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
	 * @return void
	 * @deprecated 4.1.6.1
	 */
	/*public function init() {
		if ( $this->_user_id || $this->_course_id ) {
			if ( ! $this->_course_id ) {
				$this->_course_id = get_the_ID();
			}

			if ( ! $this->_user_id ) {
				$this->_user_id = get_current_user_id();
			}
		}

		$user = learn_press_get_user( $this->_user_id );

		if ( $user ) {
			$this->load( $user );
			$this->read_course( $user->get_id(), $this->_course_id );
		}
	}*/

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
					'first_name'      => isset( $user_object->first_name ) ? $user_object->first_name : '',
					'last_name'       => isset( $user_object->last_name ) ? $user_object->last_name : '',
					'nickname'        => isset( $user_object->nickname ) ? $user_object->nickname : '',
					'display_name'    => $user_object->display_name,
					'date_created'    => $user_object->user_registered,
					'date_modified'   => get_user_meta( $user_id, 'last_update', true ),
					'role'            => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'student',
					'roles'           => ! empty( $user_object->roles ) ? $user_object->roles : array( 'student' ),
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
	 * @param int   $user_id
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

	/**
	 * @param       $user_id
	 * @param array   $args
	 *
	 * @return array|bool|mixed
	 * @editor tungnx
	 * @modify 4.1.4 - comment - not use
	 */
	//  public function read_orders( $user_id, $args = array() ) {
	//      $user = learn_press_get_user( $user_id );
	//
	//      if ( ! $user_id || ! $user ) {
	//          return false;
	//      }
	//
	//      $cache_key = false;
	//
	//      if ( $args ) {
	//          $args = wp_parse_args(
	//              $args,
	//              array(
	//                  'group_by_order' => false,
	//                  'status'         => '',
	//              )
	//          );
	//
	//          ksort( $args );
	//          $cache_key = md5( serialize( $args ) );
	//
	//          /**
	//           * Get orders from cache by args
	//           */
	//          $orders = LP_Object_Cache::get( "user-{$user_id}-" . $cache_key, 'learn-press/user-orders' );
	//
	//          if ( false !== $orders ) {
	//              return $orders;
	//          }
	//      }
	//
	//      $orders = LP_Object_Cache::get( 'user-' . $user_id, 'learn-press/user-orders' );
	//
	//      if ( false === $orders ) {
	//          global $wpdb;
	//
	//          $orders                = array();
	//          $post_status_in        = learn_press_get_order_statuses( true, true );
	//          $post_status_in_format = array_fill( 0, sizeof( $post_status_in ), '%s' );
	//
	//          // Get order by user
	//          $sql_orders = $wpdb->prepare(
	//              "
	//              SELECT ID, post_title, post_name, post_status, post_type, post_parent, post_date
	//              FROM {$wpdb->posts} p
	//              INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = %s AND meta_value = %d
	//          ",
	//              '_user_id',
	//              $user_id
	//          );
	//
	//          /**
	//           * Get order checked out by Guest but with the email of the user are getting
	//           */
	//          $sql_guest_orders = $wpdb->prepare(
	//              "
	//              SELECT ID, post_title, post_name, post_status, post_type, post_parent, post_date
	//              FROM {$wpdb->posts} p
	//              INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND meta_key = %s AND meta_value = %s
	//              LEFT JOIN {$wpdb->postmeta} pmu ON p.ID = pmu.post_id AND pmu.meta_key = %s AND pmu.meta_value IS NULL
	//          ",
	//              '_checkout_email',
	//              $user->get_email(),
	//              '_user_id'
	//          );
	//
	//          /**
	//           * The rest
	//           */
	//          $sql_rest = $wpdb->prepare(
	//              '
	//              HAVING p.post_type = %s
	//              AND p.post_status IN(' . join( ',', $post_status_in_format ) . ')
	//              ORDER BY ID DESC
	//          ',
	//              array_merge(
	//                  array(
	//                      LP_ORDER_CPT,
	//                  ),
	//                  $post_status_in
	//              )
	//          );
	//
	//          $sql = $sql_orders . ' UNION ' . $sql_guest_orders . $sql_rest;
	//
	//          $order_posts = $wpdb->get_results( $sql );
	//
	//          if ( $order_posts ) {
	//              $order_ids = array();
	//              foreach ( $order_posts as $order_post ) {
	//
	//                  // Put post into cache to user later ... maybe.
	//                  $_post = sanitize_post( $order_post, 'raw' );
	//                  wp_cache_add( $_post->ID, $_post, 'posts' );
	//
	//                  $order_ids[] = $_post->ID;
	//              }
	//
	//              $order_ids_format = array_fill( 0, sizeof( $order_ids ), '%d' );
	//              $query            = $wpdb->prepare(
	//                  "
	//                      SELECT meta_value as course_id, order_id
	//                      FROM {$wpdb->learnpress_order_items} oi
	//                      INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = %s
	//                      WHERE oi.order_id IN (" . join( ',', $order_ids_format ) . ')
	//                      ORDER BY FIELD(order_id, ' . join( ',', $order_ids_format ) . ')
	//                  ',
	//                  array_merge( array( '_course_id' ), $order_ids, $order_ids )
	//              );
	//
	//              if ( $results = $wpdb->get_results( $query ) ) {
	//                  foreach ( $results as $result ) {
	//                      if ( empty( $orders[ $result->course_id ] ) ) {
	//                          $orders[ $result->course_id ] = array();
	//                      }
	//                      $orders[ $result->course_id ][] = $result->order_id;
	//                  }
	//              }
	//          }
	//          // Store to cache
	//          LP_Object_Cache::set( 'user-' . $user_id, $orders, 'learn-press/user-orders' );
	//      }
	//
	//      if ( $orders ) {
	//
	//          /*** TEST CACHE */
	//          $all_orders = call_user_func_array( 'array_merge', $orders );
	////            $curd       = new LP_Object_Data_CURD();
	////            $curd->read_meta_by_ids( $all_orders, 'post' );
	//
	//          if ( array_key_exists( 'status', $args ) && $args['status'] ) {
	//              LP_Helper::sanitize_order_status( $args['status'] );
	//
	//              $statuses = (array) $args['status'];
	//              foreach ( $orders as $course_id => $order_ids ) {
	//                  $orders[ $course_id ] = array();
	//                  foreach ( $order_ids as $order_id ) {
	//                      if ( in_array( get_post_status( $order_id ), $statuses ) ) {
	//                          $orders[ $course_id ][] = $order_id;
	//                      }
	//                  }
	//              }
	//          }
	//
	//          if ( array_key_exists( 'group_by_order', $args ) && $args['group_by_order'] ) {
	//              $this->_group_orders( $orders );
	//          }
	//      }
	//
	//      if ( $cache_key ) {
	//          LP_Object_Cache::set( "user-{$user_id}-" . $cache_key, $orders, 'learn-press/user-orders' );
	//      }
	//
	//      return $orders;
	//  }

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
	 * @param int $user_id
	 * @param int $course_id
	 * @param bool $force - ignore caching.
	 *
	 * @return bool
	 * @editor tungnx
	 * @modify 4.1.2
	 * @Todo should rewrite
	 * @deprecated 4.2.2
	 */
	public function read_course( int $user_id = 0, int $course_id = null, bool $force = false ) {
		_deprecated_function( __METHOD__, '4.2.2' );
		$lp_user_items_db = LP_User_Items_DB::getInstance();

		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		if ( is_null( $course_id ) ) {
			$course_id = get_the_ID();
		}

		if ( is_numeric( $course_id ) && LP_COURSE_CPT !== learn_press_get_post_type( $course_id ) ) {
			return false;
		}

		/*$result = LP_Object_Cache::get( 'course-' . $user_id . '-' . $course_id, 'learn-press/user-item-courses' );
		if ( false !== $result ) {
			return $result;
		}*/
		$last_user_course = false;

		try {
			/**
			 * Get newest item-course in table user-items
			 */
			$filter           = new LP_User_Items_Filter();
			$filter->item_id  = $course_id;
			$filter->user_id  = $user_id;
			$last_user_course = $lp_user_items_db->get_last_user_course( $filter );

			if ( ! $last_user_course ) {
				return false;
			}

			$last_user_course = (array) $last_user_course;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		//LP_Object_Cache::set( 'course-' . $user_id . '-' . $course_id, $result, 'learn-press/user-item-courses' );

		return $last_user_course;
	}

	/**
	 * Load user items by item_id of course item
	 *
	 * @param int $user_item_id
	 *
	 * @return array|bool
	 * @editor tungnx
	 * @deprecated 4.1.5
	 */
	/*public function read_course_items_by_user_item_id( $user_item_id ) {
		global $wpdb;

		if ( ! $user_item_id ) {
			return false;
		}

		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_user_items} ui
			WHERE ui.parent_id = %d
			",
			$user_item_id
		);

		$items = $wpdb->get_results( $query );

		return $items;
	}*/

	/**
	 * @editor tungnx
	 * @deprecated 4.1.5
	 */
	/*public function read_course_data( $course_id, $user_id ) {
		global $wpdb;

		$course                 = learn_press_get_course( $course_id );
		$course_item_ids        = $course->get_items();
		$course_item_ids_format = LP_Helper::db_format_array( $course_item_ids, '%d' );

		$query = LP_Helper::prepare(
			"
			SELECT MAX(user_item_id) user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE user_id = %d
				AND item_id IN (" . $course_item_ids_format . ')
			GROUP BY item_id
		',
			$user_id,
			$course_item_ids
		);

		if ( $user_item_ids = $wpdb->get_col( $query ) ) {

			$item_types        = learn_press_get_course_item_types();
			$item_types_format = LP_Helper::db_format_array( $item_types, '%s' );
			$query             = LP_Helper::prepare(
				"
				SELECT ui.*, p.post_type AS item_type, grade.meta_value as grade, data.meta_value as data, results.meta_value as results, version.meta_value as version
				FROM {$wpdb->learnpress_user_items} ui
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} grade ON ui.user_item_id = grade.learnpress_user_item_id AND grade.meta_key = '%s'
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} data ON ui.user_item_id = data.learnpress_user_item_id AND data.meta_key = '%s'
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} results ON ui.user_item_id = results.learnpress_user_item_id AND results.meta_key = '%s'
				LEFT JOIN {$wpdb->learnpress_user_itemmeta} version ON ui.user_item_id = version.learnpress_user_item_id AND version.meta_key = '%s'
				INNER JOIN {$wpdb->posts} p ON p.ID = ui.item_id
				WHERE user_item_id IN(" . LP_Helper::db_format_array( $user_item_ids ) . ')
					AND  p.post_type IN(' . $item_types_format . ')
			',
				'grade',
				'data',
				'results',
				'version',
				$user_item_ids,
				$item_types
			);

			return $wpdb->get_results( $query );
		}

		return false;
	}*/

	/**
	 * Get a row in table user-items by user_item_id key
	 *
	 * @param int $user_item_id
	 *
	 * @return object
	 * @editor tungnx
	 * @deprecated 4.1.5
	 */
	/*public function get_item_by_user_item_id( $user_item_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"SELECT * FROM {$wpdb->learnpress_user_items} WHERE user_item_id = %d",
			$user_item_id
		);

		return $wpdb->get_row( $query );
	}*/

	/**
	 * Load user items by item_id of course item
	 *
	 * @param object $parent_item
	 * @param bool   $force - Optional. Force to read new data from DB (ignore caching).
	 *
	 * @return bool
	 * @editor tungnx
	 * @deprecated 4.1.5
	 */
	/*public function read_course_items_bak( &$parent_item, $force = false ) {
		global $wpdb;

		$item_types = learn_press_get_course_item_types();
		$args       = array_merge( $item_types, array( $parent_item['user_item_id'] ) );
		$format     = array_fill( 0, sizeof( $item_types ), '%s' );

		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE item_type IN(" . join( ',', $format ) . ')
			AND parent_id = %d
			ORDER BY item_id, user_item_id DESC
		',
			$args
		);

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
				// $this->_read_item_meta( $result );
				$meta_ids[] = $result['user_item_id'];

				$items[ $user_item_id ][ $result['user_item_id'] ] = $result;
			}

			// Batch updating user item meta
			update_meta_cache( 'learnpress_user_item', $meta_ids );
			// LP_Helper_CURD::update_meta_cache( 'learnpress_user_item', $meta_ids );

			foreach ( $items as $user_item_id => $_items ) {
				$cache_name = sprintf(
					'course-item-%d-%d-%d',
					$parent_item['user_id'],
					$parent_item['item_id'],
					$user_item_id
				);
				// Refresh caching
				LP_Object_Cache::set( $cache_name, $_items, 'learn-press/user-course-items' );
			}
		}

		return true;
	}*/

	/**
	 * Get all items user has already started/completed.
	 *
	 * @param int $user_id
	 * @param int $course_id
	 *
	 * @return mixed
	 * @deprecated 4.1.6.9.1
	 */
	/*public function get_user_items( $user_id, $course_id ) {
		if ( false === ( $course_data = LP_Object_Cache::get(
			'course-' . $user_id . '-' . $course_id,
			'learn-press/user-item-courses'
		) ) ) {
			return false;
		}

		return $course_data['items'];
	}*/

	/**
	 * @deprecated 4.1.6.9.1
	 */
	/*public function get_user_completed_items( $user_id, $course_id ) {
		if ( ! $items = $this->get_user_items( $user_id, $course_id ) ) {
			return false;
		}
		foreach ( $items as $item ) {

		}
	}*/

	/**
	 * Read meta data of an user item.
	 *
	 * @param $item
	 */
	protected function _read_item_meta( &$item ) {
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
	 * @deprecated 4.1.6.9
	 */
	//  public function update_user_item( $user_id, $item_id, $item_data = array(), $course_id = 0 ) {
	//      global $wpdb;
	//
	//      $user = learn_press_get_user( $user_id );
	//      if ( ! $user || ( $user->get_id() != $user_id ) ) {
	//          return false;
	//      }
	//
	//      if ( array_key_exists( 'user_item_id', $item_data ) && empty( $item_data['user_item_id'] ) ) {
	//          $item = false;
	//      } else {
	//          if ( func_num_args() == 4 ) {
	//              // $item = $this->get_user_item( $user_id, $item_id, $course_id );
	//              $item = $this->read_course( $user_id, $course_id );
	//          } else {
	//              $item = $this->get_user_item( $user_id, $item_id );
	//          }
	//      }
	//
	//      // Table fields
	//      $table_fields = array(
	//          'user_id'    => '%d',
	//          'item_id'    => '%d',
	//          'ref_id'     => '%d',
	//          'start_time' => '%s',
	//          'end_time'   => '%s',
	//          'item_type'  => '%s',
	//          'status'     => '%s',
	//          'ref_type'   => '%s',
	//          'parent_id'  => '%d',
	//      );
	//
	//      // Data and format
	//      $data        = array();
	//      $data_format = array();
	//
	//      // Update it later...
	//      $new_status = false;
	//      if ( array_key_exists( 'status', $item_data ) && $item_data['status'] != $item['status'] ) {
	//          $new_status = $item_data['status'];
	//          unset( $item_data['status'] );
	//      }
	//
	//      // Build data and data format
	//      foreach ( $item_data as $field => $value ) {
	//          if ( ! empty( $table_fields[ $field ] ) ) {
	//              $data[ $field ]        = $value;
	//              $data_format[ $field ] = $table_fields[ $field ];
	//          }
	//      }
	//
	//      $data['user_id'] = $user_id;
	//      $data['item_id'] = $item_id;
	//
	//      if ( $course_id ) {
	//          $data['ref_id']   = $course_id;
	//          $data['ref_type'] = LP_COURSE_CPT;
	//      } else {
	//          $data['item_type'] = LP_COURSE_CPT;
	//      }
	//
	//      foreach ( $data as $k => $v ) {
	//          $data_format[ $k ] = $table_fields[ $k ];
	//      }
	//
	//      $data_format = array_values( $data_format );
	//
	//      if ( ! $item || empty( $item['user_item_id'] ) ) {
	//          if ( $data['ref_type'] === LP_COURSE_CPT && empty( $data['parent_id'] ) ) {
	//              return false;
	//          }
	//
	//          $wpdb->insert(
	//              $wpdb->learnpress_user_items,
	//              $data,
	//              $data_format
	//          );
	//          $user_item_id = $wpdb->insert_id;
	//          $item         = $this->get_user_item_by_id( $user_item_id );
	//      } else {
	//          $user_item_id = $item['user_item_id'];
	//          $wpdb->update(
	//              $wpdb->learnpress_user_items,
	//              $data,
	//              array( 'user_item_id' => $user_item_id ),
	//              $data_format,
	//              array( '%d' )
	//          );
	//
	//          $item = array_merge( $item, $data );
	//      }
	//
	//      $is_course = ( ! $course_id ) && ( learn_press_get_post_type( $item['item_id'] ) === LP_COURSE_CPT );
	//
	//      if ( $user_item_id ) {
	//
	//          // Track last status if it is updated new status.
	//          if ( $new_status !== false ) {
	//              if ( $this->update_user_item_status( $user_item_id, $new_status ) ) {
	//                  $item['status'] = $new_status;
	//              }
	//          }
	//
	//          if ( $is_course ) {
	//              $course_id = $item['item_id'];
	//              // Update cache to effect the change right way!
	//              LP_Object_Cache::set( 'course-' . $user_id . '-' . $course_id, $item, 'learn-press/user-dcourses' );
	//          } else {
	//
	//              $user        = learn_press_get_user( $user_id );
	//              $user_course = $user->get_course_data( $course_id );
	//
	//              $user_course->set_item( $item );
	//
	//              // Update cache
	//              $existed = false !== ( $items = LP_Object_Cache::get(
	//                  'course-item-' . $user_id . '-' . $course_id . '-' . $item_id,
	//                  'learn-press/user-course-items'
	//              ) );
	//
	//              if ( false === $items || ! empty( $items[ $user_item_id ] ) ) {
	//                  if ( is_array( $items ) ) {
	//                      $items[ $user_item_id ] = $item;
	//                  } else {
	//                      $items = array( $user_item_id => $item );
	//                  }
	//              } else {
	//                  $items = array( $user_item_id => $item ) + $items;
	//              }
	//
	//              LP_Object_Cache::set(
	//                  'course-item-' . $user_id . '-' . $course_id . '-' . $item_id,
	//                  $items,
	//                  'learn-press/user-course-items'
	//              );
	//
	//          }
	//
	//          wp_cache_delete( 'course-' . $user_id . '-' . $course_id, 'learn-press/user-item-object-courses' );
	//
	//          /*
	//          if ( $existed ) {
	//              wp_cache_replace( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, $items, 'learn-press/user-course-items' );
	//          } else {
	//              wp_cache_add( 'course-item-' . $user_id . '-' . $course_id . '-' . $item_id, $items, 'learn-press/user-course-items' );
	//          }*/
	//      }
	//
	//      return $user_item_id;
	//  }

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
	 * @deprecated 4.1.7.2
	 */
	/*public function get_user_item( $user_id, $item_id, $course_id = 0, $last = true ) {
		$num_args = func_num_args();

		if ( $num_args == 2 ) {
			$this->read_course( $user_id, $item_id );
			$item = LP_Object_Cache::get( 'course-' . $user_id . '-' . $item_id, 'learn-press/user-item-courses' );
		} else {
			$this->read_course( $user_id, $course_id );
			$cache_name = sprintf( 'course-item-%d-%d-%d', $user_id, $course_id, $item_id );
			$item       = LP_Object_Cache::get( $cache_name, 'learn-press/user-course-items' );

			if ( $last && $item ) {
				$item = reset( $item );
			}
		}

		return $item;
	}*/

	/**
	 * @param int    $user_item_id
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
	 * @param int $user_item_id
	 *
	 * @return bool|LP_User_Item_Course
	 * @deprecated 4.1.6.9
	 */
	/*public function get_user_item_course( $user_item_id ) {
		if ( $item = $this->get_user_item_by_id( $user_item_id, LP_COURSE_CPT ) ) {
			return new LP_User_Item_Course( $item );
		}

		return false;
	}*/

	/**
	 * @deprecated 4.1.6.9
	 */
	/*public function get_user_item_meta( $user_item_id, $meta_key, $single = true ) {
		return learn_press_get_user_item_meta( $user_item_id, $meta_key, $single );
	}*/

	/**
	 * @deprecated 4.1.6.9
	 */
	/*public function update_user_item_meta( $user_item_id, $meta_key, $meta_value, $prev = '' ) {
		return learn_press_update_user_item_meta( $user_item_id, $meta_key, $meta_value, $prev );
	}*/

	/**
	 * Update user item data by id.
	 *
	 * @param int   $user_item_id
	 * @param array $item_data
	 *
	 * @return bool
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*public function update_user_item_by_id( $user_item_id, $item_data = array() ) {
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
			'parent_id'    => '%d',
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
	}*/

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

	// public function restore_last_status( $user_item_id ) {
	// 	$item = $this->get_user_item_by_id( $user_item_id );
	// 	if ( ! $item ) {
	// 		return false;
	// 	}

	// 	if ( $last_status = $this->get_user_item_meta( $item['user_item_id'], '_last_status', true ) ) {
	// 		$this->update_user_item_status( $user_item_id, $last_status );
	// 		learn_press_delete_user_item_meta( $user_item_id, '_last_status', '', true );
	// 	}

	// 	return $last_status;
	// }

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
	 * Delete a row from user items by user_item_id key.
	 *
	 * @param int $user_item_id
	 *
	 * @return mixed
	 */
	/*public function delete_by_user_item_id( $user_item_id ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE parent_id = %d
		",
			$user_item_id
		);

		// Delete child items (lessons, quizzes, etc) in case the item being deleted is a course
		if ( $child = $wpdb->get_col( $query ) ) {
			foreach ( $child as $child_id ) {
				$this->delete_by_user_item_id( $child_id );
			}
		}

		// Delete origin item
		$query = $wpdb->prepare(
			"
			DELETE
			FROM ui, uim
			USING {$wpdb->prefix}learnpress_user_items AS ui
			LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta AS uim ON ui.user_item_id = uim.learnpress_user_item_id
			WHERE user_item_id = %d
		",
			$user_item_id
		);

		return $wpdb->query( $query );
	}*/

	/**
	 * Query own courses of an user.
	 *
	 * @param int $user_id
	 * @param array $args
	 *
	 * @return LP_Query_List_Table
	 * @editor tungnx
	 * @modify 4.1.5 - comment
	 * @deprecated 4.1.5
	 */
	/*public function query_own_courses( int $user_id = 0, array $args = array() ): LP_Query_List_Table {
		global $wpdb, $wp;
		$paged = 1;

		if ( ! empty( $wp->query_vars['view_id'] ) ) {
			$paged = absint( $wp->query_vars['view_id'] );
		}

		$limit = LP_Settings::get_option( 'archive_course_limit', 6 );
		if ( empty( $limit ) ) {
			$limit = 6;
		}

		$paged = max( $paged, 1 );
		$args  = wp_parse_args(
			$args,
			array(
				'paged'  => $paged,
				'limit'  => $limit,
				'status' => '',
			)
		);

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$cache_key = sprintf( 'own-courses-%d-%s', $user_id, md5( build_query( $args ) ) );
		$courses   = LP_Object_Cache::get( $cache_key, 'learn-press/user-courses' );

		if ( false === $courses ) {
			$courses = array(
				'total' => 0,
				'paged' => $args['paged'],
				'limit' => $args['limit'],
				'pages' => 0,
				'items' => array(),
			);

			try {
				$query = array(
					'total' => 0,
					'pages' => 0,
					'items' => false,
				);

				$limit  = $args['limit'];
				$where  = 'WHERE 1';
				$offset = ( $args['paged'] - 1 ) * $limit;

				if ( ! empty( $args['status'] ) ) {
					if ( is_array( $args['status'] ) ) {
						$a      = array_fill( 0, sizeof( $where ), '%d' );
						$where .= $wpdb->prepare( ' AND post_status IN(' . join( ',', $where ) . ')', $a );
					} else {
						if ( 'pending' === $args['status'] ) {
							$where .= $wpdb->prepare( ' AND post_status IN( %s, %s )', array( 'draft', 'pending' ) );
						} elseif ( $args['status'] !== '*' ) {
							$where .= $wpdb->prepare( ' AND post_status = %s', $args['status'] );
						}
					}
				} else {
					$where .= $wpdb->prepare( ' AND post_status NOT IN (%s, %s)', array( 'trash', 'auto-draft' ) );
				}

				$where = $where . $wpdb->prepare( ' AND post_type = %s AND post_author = %d', LP_COURSE_CPT, $user_id );

				$join = '';

				do_action( 'learnpress/user-curd/query_where_join_own_courses', $where, $join );

				$sql = "
					SELECT SQL_CALC_FOUND_ROWS ID
					FROM {$wpdb->posts} c
					{$join}
					{$where}
					ORDER BY ID DESC
					LIMIT {$offset}, {$limit}
				";

				do_action( 'learnpress/user-curd/query_string_own_courses', $sql );

				$items       = $wpdb->get_results( $sql );
				$count       = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
				$all_courses = learnpress_get_count_by_user( $user_id, 'lp_course' );

				if ( $items ) {
					$course_ids = wp_list_pluck( $items, 'ID' );
					LP_Helper::cache_posts( $course_ids );

					$courses['total'] = $count;
					$courses['pages'] = ceil( $count / $args['limit'] );

					foreach ( $items as $item ) {
						$courses['items'][] = $item->ID;
					}
				}

				$courses['counts'] = array(
					'all'     => $all_courses['all'],
					'publish' => $all_courses['publish'],
					'pending' => $all_courses['pending'],
				);
			} catch ( Exception $ex ) {
				learn_press_add_message( $ex->getMessage() );
			}

			LP_Object_Cache::set( $cache_key, $courses, 'learn-press/user-courses' );
		}

		$courses['single'] = __( 'course', 'learnpress' );
		$courses['plural'] = __( 'courses', 'learnpress' );

		return new LP_Query_List_Table( $courses );
	}*/

	/**
	 * Query courses by user
	 *
	 * @param int $user_id
	 * @param array $args
	 *
	 * @return LP_Query_List_Table
	 * @editor tungnx
	 * @modify 4.1.5 - comment
	 * @deprecated 4.1.5
	 */
	/*public function query_purchased_courses( int $user_id = 0, array $args = array() ): LP_Query_List_Table {
		global $wpdb, $wp;
		$paged = 1;

		if ( ! empty( $wp->query_vars['view_id'] ) ) {
			$paged = absint( $wp->query_vars['view_id'] );
		}

		$limit = LP_Settings::get_option( 'archive_course_limit', 6 );
		if ( empty( $limit ) ) {
			$limit = 6;
		}

		$args = wp_parse_args(
			$args,
			array(
				'paged'  => $paged,
				'limit'  => $limit,
				'status' => '',
			)
		);

		$args['paged'] = max( $args['paged'], 1 );

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$cache_key = sprintf( 'purchased-courses-%d-%s', $user_id, md5( build_query( $args ) ) );
		$courses   = LP_Object_Cache::get( $cache_key, 'learn-press/user-courses' );

		if ( false === $courses ) {
			$courses = array(
				'total' => 0,
				'paged' => $args['paged'],
				'limit' => $args['limit'],
				'pages' => 0,
				'items' => array(),
			);

			try {
				$orders = $this->get_orders( $user_id, array( 'status' => 'completed' ) );

				$course_ids = array();

				if ( $orders ) {
					$course_ids = array_keys( $orders );
				}

				$item_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT item_id FROM {$wpdb->learnpress_user_items} WHERE user_id=%d AND item_type=%s",
						$user_id,
						LP_COURSE_CPT
					)
				);

				if ( $item_ids ) {
					$course_ids = array_merge( $course_ids, $item_ids );
				}

				$query = array(
					'total' => 0,
					'pages' => 0,
					'items' => false,
				);

				if ( ! $course_ids ) {
					throw new Exception( '', 0 );
				}

				$query_args   = $course_ids;
				$query_args[] = $user_id;
				$limit        = $args['limit'];
				$offset       = ( $args['paged'] - 1 ) * $limit;

				// SELECT
				$select = 'SELECT c.ID, c.post_title, ui.*';

				// FROM
				$from = "FROM {$wpdb->learnpress_user_items} ui";

				// JOIN
				$join = $wpdb->prepare(
					"INNER JOIN {$wpdb->posts} c ON ui.item_id = c.ID AND c.post_type = %s",
					LP_COURSE_CPT
				);

				// WHERE - modify by tungnx
				$where       = $wpdb->prepare(
					'
					WHERE ui.user_id = %d
					AND c.ID IN(' . join( ',', $course_ids ) . ')
					AND c.post_status = %s
					',
					$user_id,
					'publish'
				);
				$count_where = $where;

				// HAVING
				$having = 'HAVING 1';

				// ORDER BY
				$orderby = 'ORDER BY start_time DESC';

				if ( ! empty( $args['status'] ) ) {
					switch ( $args['status'] ) {
						case 'finished':
						case 'passed':
						case 'failed':
							$where .= $wpdb->prepare(
								' AND ui.status IN( %s )',
								array(
									'finished',
								)
							);

							if ( $args['status'] !== 'finished' ) {
								if ( 'passed' === $args['status'] ) {
									$having .= $wpdb->prepare( ' AND ui.graduation = %s', 'passed' );
								} else {
									$having .= $wpdb->prepare( ' AND ( ui.graduation = %s )', 'failed' );
								}
							}

							break;
						case 'in-progress':
							$where .= $wpdb->prepare(
								' AND ui.status IN( %s )',
								array(
									'enrolled',
								)
							);

							$having .= $wpdb->prepare( ' AND ui.graduation = %s', 'in-progress' );

							break;
						case 'not-enrolled':
							$where .= $wpdb->prepare(
								' AND ui.status NOT IN( %s, %s, %s )',
								array(
									'enrolled',
									'finished',
									'pending',
								)
							);
					}
				}

				// Comment by tungnx
				//$where .= $wpdb->prepare( ' AND ui.status NOT IN(%s) ', 'pending' );

				$query_parts = apply_filters(
					'learn-press/query/user-purchased-courses',
					compact( 'select', 'from', 'join', 'where', 'having', 'orderby' ),
					$user_id,
					$args
				);

				list( $select, $from, $join, $where, $having, $orderby ) = array_values( $query_parts );

				if ( $args['limit'] > 0 ) {
					$limit = "LIMIT {$offset}, {$limit}";
				} else {
					$limit = '';
				}

				do_action( 'learnpress/user-curd/query_where_join_purchased_courses', $where, $join );

				$sql = "
					SELECT SQL_CALC_FOUND_ROWS *
					FROM
					(
						{$select}
						{$from}
						{$join}
						{$where}
						{$having}
						ORDER BY item_id, user_item_id DESC
					) X
					GROUP BY item_id
					{$orderby}
					$limit
				";

				do_action( 'learnpress/user-curd/query_string_purchased_courses', $sql );

				$items = $wpdb->get_results( $sql );

				if ( $items ) {
					$count      = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
					$course_ids = wp_list_pluck( $items, 'item_id' );
					LP_Helper::cache_posts( $course_ids );

					$courses['total'] = $count;
					$courses['pages'] = $args['limit'] > 0 ? ceil( $count / $args['limit'] ) : 1;
					foreach ( $items as $item ) {
						$item               = (array) $item;
						$course_item        = new LP_User_Item_Course( $item );
						$courses['items'][] = $course_item;
					}
				}

				$counts = array(
					'all'      => 0,
					'enrolled' => 0,
					'finished' => 0,
				);

				$sql = "
					SELECT user_item_id
					FROM
					(
						{$select}
						{$from}
						{$join}
						{$count_where}
					) X
					GROUP BY item_id
				";

				$user_item_ids = $wpdb->get_col( $sql );
				if ( $user_item_ids ) {
					$rows = $wpdb->get_results(
						"
						SELECT status, COUNT(user_item_id) count
						FROM {$wpdb->learnpress_user_items}
						WHERE user_item_id in(" . join( ',', $user_item_ids ) . ')
						GROUP BY status
					'
					);

					if ( $rows ) {
						foreach ( $rows as $row ) {
							$counts[ $row->status ] = $row->count;
							$counts['all']         += $row->count;
						}
					}
				}

				$counts['not-enrolled'] = $counts['all'] - ( $counts['enrolled'] + $counts['finished'] );
				$sql                    = $wpdb->prepare(
					"
					SELECT COUNT(meta_id) count, meta_value `status`
					FROM (
						SELECT user_id, item_id, MAX(user_item_id) max_id
						FROM {$wpdb->learnpress_user_items} GROUP BY user_id, item_id
					) X
					INNER JOIN {$wpdb->learnpress_user_items} ui ON X.max_id = ui.user_item_id
					INNER JOIN {$wpdb->learnpress_user_itemmeta} uim ON ui.user_item_id=uim.learnpress_user_item_id
					WHERE uim.meta_key LIKE '%grade%'
					AND ui.user_id = %d AND ui.item_type = %s
					GROUP BY meta_value
				",
					$user_id,
					LP_COURSE_CPT
				);

				$rows = $wpdb->get_results( $sql );
				if ( $rows ) {
					foreach ( $rows as $row ) {
						$counts[ $row->status ] = $row->count;
					}
				}

				$courses['counts'] = $counts;
			} catch ( Exception $ex ) {

			}

			LP_Object_Cache::set( $cache_key, $courses, 'learn-press/user-courses' );
		}

		$courses['single'] = __( 'course', 'learnpress' );
		$courses['plural'] = __( 'courses', 'learnpress' );

		return new LP_Query_List_Table( $courses );
	}*/

	/**
	 * Get courses user has purchased via orders are completed
	 * but it is not already added to user-items table.
	 *
	 * @param int $user_id
	 *
	 * @return array|bool|mixed
	 */
	public function query_courses_by_order( $user_id ) {
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
	 * Query quizzes by user.
	 *
	 * @param int    $user_id
	 * @param string $args
	 *
	 * @return LP_Query_List_Table
	 * @deprecated 4.2.2.4
	 */
	public function query_quizzes( $user_id = 0, $args = '' ) {
		_deprecated_function( __FUNCTION__, '4.2.2.4' );

		global $wpdb, $wp;

		$paged = 1;

		if ( ! empty( $wp->query_vars['view_id'] ) ) {
			$paged = absint( $wp->query_vars['view_id'] );
		}

		$paged = max( $paged, 1 );
		$args  = wp_parse_args(
			$args,
			array(
				'paged'  => $paged,
				'limit'  => 10,
				'status' => '',
			)
		);

		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		$cache_key = sprintf( 'quizzes-%d-%s', $user_id, md5( build_query( $args ) ) );
		$quizzes   = LP_Object_Cache::get( $cache_key, 'learn-press/user-quizzes' );

		if ( false === $quizzes ) {
			$orders = $this->get_orders( $user_id );
			$query  = array(
				'total' => 0,
				'pages' => 0,
				'items' => false,
			);

			$quizzes = array(
				'total' => 0,
				'paged' => $args['paged'],
				'limit' => $args['limit'],
				'pages' => 0,
				'items' => array(),
			);

			try {
				if ( ! $orders ) {
					throw new Exception( __( 'There are no items in the order', 'learnpress' ), 0 );
				}

				$course_ids   = array_keys( $orders );
				$query_args   = $course_ids;
				$query_args[] = $user_id;

				$select  = 'SELECT * ';
				$from    = "FROM {$wpdb->learnpress_user_items} ui";
				$join    = '';
				$where   = 'WHERE 1=1 ';
				$where  .= $wpdb->prepare( 'AND user_id = %d ', $user_id );
				$where  .= $wpdb->prepare( 'AND item_type = %s ', LP_QUIZ_CPT );
				$where  .= $wpdb->prepare( 'AND status IN (%s, %s) ', LP_ITEM_COMPLETED, LP_ITEM_STARTED );
				$having  = 'HAVING 1';
				$orderby = 'ORDER BY item_id, user_item_id DESC';

				if ( ! empty( $args['status'] ) ) {
					switch ( $args['status'] ) {
						case 'completed':
						case 'passed':
						case 'failed':
							$having .= $wpdb->prepare(
								' AND X.status IN( %s )',
								array(
									'completed',
								)
							);

							if ( $args['status'] !== 'completed' ) {
								if ( 'passed' === $args['status'] ) {
									$having .= $wpdb->prepare( ' AND X.graduation = %s', 'passed' );
								} elseif ( 'failed' === $args['status'] ) {
									$having .= $wpdb->prepare( ' AND X.graduation = %s', 'failed' );
								} else {
									$having .= $wpdb->prepare( ' AND ( X.graduation IS NULL OR X.graduation <> %s )', 'passed' );
								}
							}
							break;

						case 'in-progress':
							$having .= $wpdb->prepare(
								' AND X.status IN( %s ) AND X.graduation NOT IN ( %s, %s )',
								array(
									'started',
									'passed',
									'failed',
								)
							);
							break;

						case 'not-started':
							$having .= $wpdb->prepare(
								' AND X.status NOT IN( %s, %s )',
								array(
									'started',
									'completed',
								)
							);
							break;
					}
				} else {

					$having .= $wpdb->prepare(
						' AND X.status IN( %s, %s )',
						array(
							'started',
							'completed',
						)
					);
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

				$sql = $wpdb->prepare(
					"
					SELECT * FROM (
				    SELECT *
				    FROM
						(
							{$select}
							{$from}
							{$join}
							{$where}
							ORDER BY user_item_id DESC
						) AS G_Item
				    GROUP BY G_Item.item_id
					) AS G_User_Item
					ORDER BY G_User_Item.user_item_id DESC
					LIMIT %d, %d
				",
					$offset,
					$limit
				);

				$sql = "SELECT DISTINCT item_id, MAX(user_item_id) as user_item_id , user_id, item_type, start_time, end_time, status, graduation, parent_id
FROM $wpdb->learnpress_user_items
WHERE user_id = $user_id
  AND item_type = 'lp_quiz'
  AND status IN ('completed', 'started')
GROUP BY item_id
ORDER BY MAX(user_item_id) DESC";

				$items = $wpdb->get_results( $sql, ARRAY_A );

				if ( $items ) {
					$count      = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
					$course_ids = wp_list_pluck( $items, 'item_id' );
					// LP_Helper::cache_posts( $course_ids );

					$quizzes['total'] = $count;
					$quizzes['pages'] = ceil( $count / $args['limit'] );

					foreach ( $items as $item ) {
						$quizzes['items'][] = new LP_User_Item_Quiz( $item );
					}
				}
			} catch ( Exception $ex ) {

			}
			LP_Object_Cache::set( $cache_key, $quizzes, 'learn-press/user-course' );
		}

		$quizzes['single'] = __( 'quiz', 'learnpress' );
		$quizzes['plural'] = __( 'quizzes', 'learnpress' );

		return new LP_Query_List_Table( $quizzes );
	}

	public function read_course_info( $course ) {
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

	public function get_current_user_order( $user_id, $course_id ) {
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
			return new WP_Error( 'registration-error-username-exists', __( 'An account is already registered with that username. Please choose another one.', 'learnpress' ) );
		}

		$new_user = wp_insert_user(
			array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email,
			)
		);

		if ( is_wp_error( $new_user ) ) {
			return new WP_Error( 'registration-error', __( 'Failed to create the user', 'learnpress' ) );
		}

		return $new_user;
	}
}

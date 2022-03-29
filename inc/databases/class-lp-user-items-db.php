<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_User_Items_DB
 *
 * @since 3.2.8.6
 * @version 1.0.3
 * @author tungnx
 */
class LP_User_Items_DB extends LP_Database {
	private static $_instance;
	public static $user_item_id_col = 'learnpress_user_item_id';
	public static $extra_value_col  = 'extra_value';

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get items by user_item_id | this is id where item_id = course_id
	 *
	 * @param LP_User_Items_Filter $filter
	 * @param bool $force_cache
	 *
	 * @return object
	 * @throws Exception
	 * Todo: tungnx need set paginate - apply when do load API
	 */
	public function get_user_course_items( LP_User_Items_Filter $filter, bool $force_cache = false ) {
		$key_first_cache    = 'course_items/' . $filter->user_id . '/' . $filter->parent_id;
		$course_items_cache = LP_Cache::cache_load_first( 'get', $key_first_cache );
		if ( false !== $course_items_cache && ! $force_cache ) {
			return $course_items_cache;
		}

		$query = $this->wpdb->prepare(
			"SELECT * FROM $this->tb_lp_user_items
			WHERE parent_id = %d
			AND ref_type = %s
			AND user_id = %d
			",
			$filter->parent_id,
			LP_COURSE_CPT,
			$filter->user_id
		);

		$course_items = $this->wpdb->get_results( $query );

		$this->check_execute_has_error();

		LP_Cache::cache_load_first( 'set', $key_first_cache, $course_items );

		return $course_items;
	}

	/**
	 * Remove items' of course and user learned
	 *
	 * @param LP_User_Items_Filter $filter .
	 *
	 * @return bool|int
	 * @throws Exception .
	 * @TODO tungnx - recheck this function
	 */
	public function remove_items_of_user_course( LP_User_Items_Filter $filter ) {
		$query_extra = '';

		// Check valid user.
		if ( ! is_user_logged_in() || ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $filter->user_id ) ) {
			throw new Exception( __( 'User invalid!', 'learnpress' ) . ' | ' . __FUNCTION__ );
		}

		if ( - 1 < $filter->limit ) {
			$query_extra .= " LIMIT $filter->limit";
		}

		$query = $this->wpdb->prepare(
			"DELETE FROM {$this->tb_lp_user_items}
			WHERE parent_id = %d
			$query_extra;
			",
			$filter->parent_id
		);

		return $this->wpdb->query( $query );
	}

	/*public function get_item_status( $item_id, $course_id ) {
		$query = $this->wpdb->prepare(
			"
			SELECT status FROM {$this->tb_lp_user_items}
			WHERE ref_id = %d
			AND ref_type = %s
			AND item_id = %d
			",
			$course_id,
			'lp_course',
			$item_id
		);

		return $this->wpdb->get_var( $query );
	}*/

	/**
	 * Insert/Update extra value
	 *
	 * @param int    $user_item_id
	 * @param string $meta_key
	 * @param string $value
	 * @since 4.0.0
	 * @version 1.0.0
	 * @author tungnx
	 */
	public function update_extra_value( $user_item_id = 0, $meta_key = '', $value = '' ) {
		$data   = array(
			'learnpress_user_item_id' => $user_item_id,
			'meta_key'                => $meta_key,
			'extra_value'             => $value,
		);
		$format = array( '%s', '%s' );

		$check_exist_data = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"
				SELECT meta_id FROM $this->tb_lp_user_itemmeta
				WHERE " . self::$user_item_id_col . ' = %d
				AND meta_key = %s
				',
				$user_item_id,
				$meta_key
			)
		);

		if ( $check_exist_data ) {
			$this->wpdb->update(
				$this->tb_lp_user_itemmeta,
				$data,
				array(
					self::$user_item_id_col => $user_item_id,
					'meta_key'              => $meta_key,
				),
				$format
			);
		} else {
			$this->wpdb->insert( $this->tb_lp_user_itemmeta, $data, $format );
		}
	}

	/**
	 * Get extra value
	 *
	 * @param int    $user_item_id
	 * @param string $meta_key
	 */
	public function get_extra_value( $user_item_id = 0, $meta_key = '' ) {
		return $this->wpdb->get_var(
			$this->wpdb->prepare(
				'
				SELECT ' . self::$extra_value_col . " FROM $this->tb_lp_user_itemmeta
				WHERE " . self::$user_item_id_col . ' = %d
				AND meta_key = %s
				',
				$user_item_id,
				$meta_key
			)
		);
	}

	/**
	 * Re-set current item
	 * @param $course_id
	 * @param $item_id
	 * @editor hungkv
	 */
	public function reset_course_current_item( $course_id, $item_id ) {
		// Select all course enrolled
		$query         = $this->wpdb->prepare(
			"
						SELECT user_item_id
						FROM {$this->wpdb->prefix}learnpress_user_items
						WHERE status = %s AND item_id = %d AND graduation = %s
						",
			'enrolled',
			$course_id,
			'in-progress'
		);
		$user_item_ids = $this->wpdb->get_col( $query );
		if ( ! empty( $user_item_ids ) ) {
			foreach ( $user_item_ids as $user_item_id ) {
				// Check item is current item of all course
				$query         = $this->wpdb->prepare(
					"
							SELECT meta_value
							FROM {$this->wpdb->prefix}learnpress_user_itemmeta
							WHERE learnpress_user_item_id = %d
							",
					$user_item_id
				);
				$meta_value_id = $this->wpdb->get_var( $query );
				// Check if the deleted item is current item or not
				if ( $meta_value_id == $item_id ) {
					$course = learn_press_get_course( $course_id );
					// update _curent_item to database
					learn_press_update_user_item_meta( $user_item_id, '_current_item', $course->get_first_item_id() );
				}
			}
		}
	}

	/**
	 * Get total courses is has graduation is 'in_progress'
	 *
	 * @param int $user_id
	 * @param string $status
	 * @return int
	 * @throws Exception
	 */
	public function get_total_courses_has_status( int $user_id, string $status ): int {
		$query = $this->wpdb->prepare(
			"
			SELECT COUNT(DISTINCT(ui.item_id)) total
			FROM $this->tb_lp_user_items AS ui
			WHERE ui.item_type = %s
			AND ui.user_id = %d
			AND ui.graduation = %s
			",
			LP_COURSE_CPT,
			$user_id,
			$status
		);

		$this->check_execute_has_error();

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get number status by status, graduation...
	 *
	 * @param LP_User_Items_Filter $filter {user_id, item_type}
	 *
	 * @author tungnx
	 * @since 4.1.5
	 * @version 1.0.1
	 * @return object|null
	 * @throws Exception
	 */
	public function count_status_by_items( LP_User_Items_Filter $filter ) {
		$filter->only_fields[] = $this->wpdb->prepare( 'SUM(ui.graduation = %s) AS %s', LP_COURSE_GRADUATION_IN_PROGRESS, LP_COURSE_GRADUATION_IN_PROGRESS );
		$filter->only_fields[] = $this->wpdb->prepare( 'SUM(ui.graduation = %s) AS %s', LP_COURSE_GRADUATION_FAILED, LP_COURSE_GRADUATION_FAILED );
		$filter->only_fields[] = $this->wpdb->prepare( 'SUM(ui.graduation = %s) AS %s', LP_COURSE_GRADUATION_PASSED, LP_COURSE_GRADUATION_PASSED );
		$filter->only_fields[] = $this->wpdb->prepare( 'SUM(ui.status = %s) AS %s', LP_COURSE_ENROLLED, LP_COURSE_ENROLLED );
		$filter->only_fields[] = $this->wpdb->prepare( 'SUM(ui.status = %s) AS %s', LP_COURSE_PURCHASED, LP_COURSE_PURCHASED );
		$filter->only_fields[] = $this->wpdb->prepare( 'SUM(ui.status = %s) AS %s', LP_COURSE_FINISHED, LP_COURSE_FINISHED );

		$filter_user_attend_courses                      = new LP_User_Items_Filter();
		$filter_user_attend_courses->only_fields         = array( 'MAX(ui.user_item_id) AS user_item_id' );
		$filter_user_attend_courses->where[]             = $this->wpdb->prepare( 'AND ui.user_id = %s', $filter->user_id );
		$filter_user_attend_courses->group_by            = 'ui.item_id';
		$filter_user_attend_courses->return_string_query = true;
		$query_get_course_attend                         = $this->get_user_courses( $filter_user_attend_courses );

		$filter->where[]             = 'AND ui.user_item_id IN (' . $query_get_course_attend . ')';
		$filter->return_string_query = true;

		$filter = apply_filters( 'lp/user/course/query/count-status', $filter );

		$query = $this->get_user_courses( $filter );

		$this->check_execute_has_error();

		return $this->wpdb->get_row( $query );
	}

	/**
	 * Get the newest item is course of user
	 *
	 * @param LP_User_Items_Filter $filter {course_id, user_id}
	 * @param bool $force_cache Reset first cache
	 *
	 * @return null|object
	 * @throws Exception
	 */
	public function get_last_user_course( LP_User_Items_Filter $filter, bool $force_cache = false ) {
		$key_load_first = 'user_course/' . $filter->user_id . '/' . $filter->item_id;
		$user_course    = LP_Cache::cache_load_first( 'get', $key_load_first );
		if ( false !== $user_course && ! $force_cache ) {
			return $user_course;
		}

		$query = $this->wpdb->prepare(
			"SELECT user_item_id, user_id, item_id, item_type, status, graduation, ref_id, ref_type, start_time, end_time
			FROM $this->tb_lp_user_items
			WHERE item_type = %s
			AND item_id = %d
			AND user_id = %d
			ORDER BY user_item_id DESC
			LIMIT 1
			",
			LP_COURSE_CPT,
			$filter->item_id,
			$filter->user_id
		);

		$result = $this->wpdb->get_row( $query );

		$this->check_execute_has_error();

		LP_Cache::cache_load_first( 'set', $key_load_first, $result );

		return $result;
	}

	/**
	 * Get item of user and course
	 *
	 * @param LP_User_Items_Filter $filter {parent_id, item_id, user_id}
	 * @param bool $force_cache Reset first cache
	 *
	 * @return null|object
	 * @throws Exception
	 */
	public function get_user_course_item( LP_User_Items_Filter $filter, bool $force_cache = false ) {
		$key_load_first = 'user_course_item/' . $filter->user_id . '/' . $filter->item_id;
		$user_course    = LP_Cache::cache_load_first( 'get', $key_load_first );

		if ( false !== $user_course && ! $force_cache ) {
			return $user_course;
		}

		$WHERE = 'WHERE 1=1 ';

		if ( $filter->parent_id ) {
			$WHERE .= $this->wpdb->prepare( 'AND parent_id = %d ', $filter->parent_id );
		}

		if ( $filter->ref_id ) {
			$WHERE .= $this->wpdb->prepare( 'AND ref_id = %d ', $filter->ref_id );
		}

		if ( $filter->ref_type ) {
			$WHERE .= $this->wpdb->prepare( 'AND ref_type = %s ', $filter->ref_type );
		}

		if ( $filter->item_type ) {
			$WHERE .= $this->wpdb->prepare( 'AND item_type = %s ', $filter->item_type );
		}

		$query = $this->wpdb->prepare(
			"SELECT user_item_id, user_id, item_id, item_type, status, graduation, ref_id, ref_type, start_time, end_time, parent_id
			FROM $this->tb_lp_user_items
			$WHERE
			AND item_id = %d
			AND user_id = %d
			ORDER BY user_item_id DESC
			LIMIT 1
			",
			$filter->item_id,
			$filter->user_id
		);

		$result = $this->wpdb->get_row( $query );

		$this->check_execute_has_error();

		LP_Cache::cache_load_first( 'set', $key_load_first, $result );

		return $result;
	}

	/**
	 * Get items of course by item type
	 *
	 * @param LP_User_Items_Filter $filter {$filter->parent_id, $filter->item_type, [$filter->item_id]}
	 * @throws Exception
	 */
	public function get_user_course_items_by_item_type( LP_User_Items_Filter $filter ) {

		$AND = '';

		if ( $filter->item_type ) {
			$AND .= $this->wpdb->prepare( ' AND item_type = %s', $filter->item_type );
		}

		if ( $filter->item_id ) {
			$AND .= $this->wpdb->prepare( ' AND item_id = %d', $filter->item_id );
		}

		$query = $this->wpdb->prepare(
			"SELECT user_item_id, user_id, item_id, item_type, status, graduation, ref_id, ref_type, start_time, end_time, parent_id
			FROM $this->tb_lp_user_items
			WHERE parent_id = %d
			$AND
			",
			$filter->parent_id
		);

		$result = $this->wpdb->{$filter->query_type}( $query );

		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Get user_item_id by course_id
	 *
	 * @param LP_User_Items_Filter $filter $filter->item_id
	 *
	 * @return array
	 */
	public function get_user_items_by_course( LP_User_Items_Filter $filter ): array {
		try {
			// Check valid user.
			if ( ! is_user_logged_in() ) {
				throw new Exception( __( 'User invalid!', 'learnpress' ) . ' | ' . __FUNCTION__ );
			}

			$query = $this->wpdb->prepare(
				"SELECT user_item_id FROM $this->tb_lp_user_items
				WHERE item_id = %d
				AND item_type = %s
				",
				$filter->item_id,
				LP_COURSE_CPT
			);

			return $this->wpdb->get_col( $query );
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ':' . $e->getMessage() );
			return array();
		}
	}

	/**
	 * Get items is course has user
	 *
	 * @param LP_User_Items_Filter $filter $filter->user_id, $filter->item_id
	 *
	 * @throws Exception
	 * @author tungnx
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function get_ids_course_user( LP_User_Items_Filter $filter ): array {
		$query = $this->wpdb->prepare(
			"SELECT user_item_id FROM $this->tb_lp_user_items
			WHERE user_id = %d
			AND item_id = %d
			AND item_type = %s
			",
			$filter->user_id,
			$filter->item_id,
			LP_COURSE_CPT
		);

		return $this->wpdb->get_col( $query );
	}

	/**
	 * Get items of course has user
	 *
	 * @param LP_User_Items_Filter $filter user_item_ids
	 *
	 * @throws Exception
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function get_item_ids_of_user_course( LP_User_Items_Filter $filter ): array {
		if ( empty( $filter->user_item_ids ) ) {
			return [];
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND parent_id IN(' . LP_Helper::db_format_array( $filter->user_item_ids, '%d' ) . ')',
			$filter->user_item_ids
		);

		return $this->wpdb->get_col(
			"SELECT user_item_id FROM {$this->tb_lp_user_items}
			{$where}
			"
		);
	}

	/**
	 * Remove rows IN user_item_ids
	 *
	 * @param LP_User_Items_Filter $filter $filter->user_item_ids, $filter->user_id
	 *
	 * @throws Exception
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function remove_user_item_ids( LP_User_Items_Filter $filter ) {
		// Check valid user.
		/*if ( ! is_user_logged_in() || ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $filter->user_id ) ) {
			throw new Exception( __( 'User invalid!', 'learnpress' ) . ' | ' . __FUNCTION__ );
		}*/

		if ( empty( $filter->user_item_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND user_item_id IN(' . LP_Helper::db_format_array( $filter->user_item_ids, '%d' ) . ')',
			$filter->user_item_ids
		);

		return $this->wpdb->query(
			"DELETE FROM {$this->tb_lp_user_items}
			{$where}
			"
		);
	}

	/**
	 * Remove user_itemmeta has list user_item_ids
	 *
	 * @param LP_User_Items_Filter $filter $filter->user_item_ids, $filter->user_id
	 *
	 * @throws Exception
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function remove_user_itemmeta( LP_User_Items_Filter $filter ) {
		// Check valid user.
		/*if ( ! is_user_logged_in() || ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $filter->user_id ) ) {
			throw new Exception( __( 'User invalid!', 'learnpress' ) . ' | ' . __FUNCTION__ );
		}*/

		if ( empty( $filter->user_item_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND learnpress_user_item_id IN(' . LP_Helper::db_format_array( $filter->user_item_ids, '%d' ) . ')',
			$filter->user_item_ids
		);

		return $this->wpdb->query(
			"DELETE FROM {$this->tb_lp_user_itemmeta}
			{$where}
			"
		);
	}

	/**
	 * Delete user_item_ids by user_id and course_id
	 *
	 * @param int $user_id
	 * @param int $course_id
	 * @author tungnx
	 * @since 4.1.4
	 * @version 1.0.0
	 */
	public function delete_user_items_old( int $user_id = 0, int $course_id = 0 ) {
		$lp_user_items_db     = LP_User_Items_DB::getInstance();
		$lp_user_item_results = LP_User_Items_Result_DB::instance();

		try {
			// Check valid user.
			/*if ( ! is_user_logged_in() || ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $user_id ) ) {
				throw new Exception( __( 'User invalid!', 'learnpress' ) . ' | ' . __FUNCTION__ );
			}*/

			// Get all user_item_ids has user_id and course_id
			$filter          = new LP_User_Items_Filter();
			$filter->user_id = $user_id;
			$filter->item_id = $course_id;

			$user_course_ids = $lp_user_items_db->get_ids_course_user( $filter );

			if ( empty( $user_course_ids ) ) {
				return;
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				return;
			}

			$course->delete_user_item_and_result( $user_course_ids );
		} catch ( Throwable $e ) {
			error_log( __FUNCTION__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Update user_id for lp_user_item with Order buy User Guest
	 *
	 * @param LP_User_Items_Filter $filter
	 *
	 * @return bool|int
	 * @throws Exception
	 */
	public function update_user_id_by_order( LP_User_Items_Filter $filter ) {
		// Check valid user.
		if ( ! is_user_logged_in() || ( ! current_user_can( ADMIN_ROLE ) && get_current_user_id() != $filter->user_id ) ) {
			throw new Exception( __FUNCTION__ . ': User invalid!' );
		}

		$query = $this->wpdb->prepare(
			"UPDATE {$this->tb_lp_user_items}
			SET user_id = %d
			WHERE ref_type = %s
			AND ref_id = %d
			",
			$filter->user_id,
			LP_ORDER_CPT,
			$filter->ref_id
		);

		return $this->wpdb->query( $query );
	}

	/**
	 * Count items by type and total by status
	 * @throws Exception
	 *
	 * @return null|object
	 */
	public function count_items_of_course_with_status( LP_User_Items_Filter $filter ) {
		$item_types       = learn_press_get_course_item_types();
		$count_item_types = count( $item_types );
		$i                = 0;

		//$user_course = $this->get_last_user_course( $filter );

		$query_count  = '';
		$query_count .= $this->wpdb->prepare( 'SUM(ui.status = %s) AS count_status,', $filter->status );

		foreach ( $item_types as $item_type ) {
			$i++;
			$query_count .= $this->wpdb->prepare( 'SUM(ui.status = %s AND ui.item_type = %s) AS %s,', $filter->status, $item_type, $item_type . '_status_' . $filter->status );
			$query_count .= $this->wpdb->prepare( 'SUM(ui.graduation = %s AND ui.item_type = %s) AS %s', $filter->graduation, $item_type, $item_type . '_graduation_' . $filter->graduation );

			if ( $i < $count_item_types ) {
				$query_count .= ',';
			}
		}

		$query = $this->wpdb->prepare(
			'SELECT ' . $query_count . ' FROM ' . $this->tb_lp_user_items . ' ui
			WHERE parent_id = %d
			',
			$filter->parent_id
		);

		$total_items = $this->wpdb->get_row( $query );

		return $total_items;
	}

	/**
	 * Get quizzes of user
	 *
	 * @param LP_User_Items_Filter $filter
	 *
	 * @return null|object
	 * @throws Exception
	 */
	public function get_user_quizzes( LP_User_Items_Filter $filter ) {
		$this->wpdb->query( "SET GLOBAL sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))" );
		$offset = ( absint( $filter->page ) - 1 ) * $filter->limit;

		$WHERE = '';

		if ( ! empty( $filter->graduation ) ) {
			$WHERE .= $this->wpdb->prepare( 'AND graduation = %s ', $filter->graduation );
		}

		if ( ! empty( $filter->status ) ) {
			$WHERE .= $this->wpdb->prepare( 'AND status = %s ', $filter->status );
		}

		$query = $this->wpdb->prepare(
			"SELECT * FROM $this->tb_lp_user_items
			WHERE user_item_id IN (
				SELECT DISTINCT MAX(user_item_id)
				FROM $this->tb_lp_user_items
				WHERE user_id = %d
				AND item_type = %s
				AND status IN (%s, %s)
				GROUP BY item_id
			)
			$WHERE
			ORDER BY user_item_id DESC
			LIMIT %d, %d
			",
			$filter->user_id,
			LP_QUIZ_CPT,
			LP_ITEM_STARTED,
			LP_ITEM_COMPLETED,
			$offset,
			$filter->limit
		);

		$result = $this->wpdb->get_results( $query );

		$this->check_execute_has_error();

		return $result;
	}

	/**
	 * Get courses only by course's user are learning
	 *
	 * @param LP_User_Items_Filter $filter
	 * @param int $total_rows
	 *
	 * @author tungnx
	 * @version 1.0.0
	 * @since 4.1.5
	 * @return null|array|string|int
	 * @throws Exception
	 */
	public function get_user_courses( LP_User_Items_Filter $filter, int &$total_rows = 0 ) {
		$default_fields = $this->get_cols_of_table( $this->tb_lp_user_items );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_user_items;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'ui';
		}

		// Join to table posts check course exists
		$filter->join[] = "INNER JOIN {$this->tb_posts} AS p ON p.ID = $filter->collection_alias.item_id";

		// Get courses publish
		$filter->where[] = $this->wpdb->prepare( 'AND p.post_status = %s', 'publish' );

		$filter->where[] = $this->wpdb->prepare( "AND $filter->collection_alias.item_type = %s", LP_COURSE_CPT );

		// Status
		if ( $filter->status ) {
			$filter->where[] = $this->wpdb->prepare( "AND $filter->collection_alias.status = %s", $filter->status );
		}

		// Graduation
		if ( $filter->graduation ) {
			$filter->where[] = $this->wpdb->prepare( "AND $filter->collection_alias.graduation = %s", $filter->graduation );
		}

		// User
		if ( $filter->user_id ) {
			$filter->where[] = $this->wpdb->prepare( "AND $filter->collection_alias.user_id = %d", $filter->user_id );
		}

		$filter = apply_filters( 'lp/user/course/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Get total users attend courses of Author
	 *
	 * @param int $author_id
	 *
	 * @return LP_User_Items_Filter
	 * @since 4.1.6
	 * @version 1.0.0
	 * @throws Exception
	 */
	public function count_user_attend_courses_of_author( int $author_id ): LP_User_Items_Filter {
		$filter_course                      = new LP_Course_Filter();
		$filter_course->only_fields         = array( 'ID' );
		$filter_course->post_author         = $author_id;
		$filter_course->post_status         = 'publish';
		$filter_course->return_string_query = true;
		$query_courses_str                  = LP_Course_DB::getInstance()->get_courses( $filter_course );

		$filter              = new LP_User_Items_Filter();
		$filter->item_type   = LP_COURSE_CPT;
		$filter->only_fields = array( 'DISTINCT (ui.user_id)' );
		$filter->field_count = 'DISTINCT (ui.user_id)';
		$filter->where[]     = "AND item_id IN ({$query_courses_str})";
		$filter->query_count = true;

		return apply_filters( 'lp/user/course/query/filter/count-users-attend-courses-of-author', $filter );
	}
}

LP_Course_DB::getInstance();


<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_User_Items_DB
 *
 * @since 3.2.8.6
 * @version 1.0.1
 *
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
	 * @param int $user_item_id_by_course_id
	 * @param int $user_id
	 *
	 * @return object
	 * @throws Exception
	 */
	public function get_course_items_by_user_item_id( $user_item_id_by_course_id = 0, $user_id = 0 ) {
		if ( empty( $user_item_id_by_course_id ) || empty( $user_id ) ) {
			return null;
		}

		$query = $this->wpdb->prepare(
			"
			SELECT * FROM {$this->tb_lp_user_items}
			WHERE parent_id = %d
			AND ref_type = %s
			AND user_id = %d
			",
			$user_item_id_by_course_id,
			LP_COURSE_CPT,
			$user_id
		);

		$course_items = $this->wpdb->get_results( $query );

		$this->check_execute_has_error();

		return $course_items;
	}

	/**
	 * Get data user_items by course_id, quiz_id, user_id
	 *
	 * @param [type] $course_id
	 * @param [type] $item_id
	 * @param [type] $user_id
	 * @return array
	 */
	public function get_result_by_item_id( $course_id, $item_id, $user_id ) {
		if ( empty( $course_id ) || empty( $item_id ) ) {
			return false;
		}

		$query = $this->wpdb->prepare(
			"SELECT * FROM {$this->tb_lp_user_items}
			WHERE ref_id = %d
			AND item_id = %d
			AND user_id=%d
			ORDER BY user_item_id DESC",
			$course_id,
			$item_id,
			$user_id
		);

		$results = $this->wpdb->get_row( $query, ARRAY_A );

		return $results;
	}

	/**
	 * Remove items' of course and user learned
	 *
	 * @param LP_User_Items_Filter $filter .
	 *
	 * @return bool|int
	 * @throws Exception .
	 */
	public function remove_items_of_user_course( LP_User_Items_Filter $filter ) {
		$query_extra = '';

		// Check valid user.
		if ( ! is_user_logged_in() || ( ! current_user_can( 'administrator' ) && get_current_user_id() != $filter->user_id ) ) {
			throw new Exception( __( 'User invalid!', 'learnpress' ) );
		}

		if ( - 1 < $filter->limit ) {
			$query_extra .= " LIMIT $filter->limit";
		}

		$query = $this->wpdb->prepare(
			"
			DELETE FROM {$this->tb_lp_user_items}
			WHERE parent_id = %d
			$query_extra;
		",
			$filter->parent_id
		);

		return $this->wpdb->query( $query );
	}

	public function get_item_status( $item_id, $course_id ) {
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
	}

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
			SELECT COUNT(DISTINCT(item_id)) total
			FROM $this->tb_lp_user_items
				INNER JOIN $this->tb_posts AS p
				ON item_id = p.ID
			WHERE item_type = %s
			AND user_id = %d
			AND graduation = %s
			AND p.post_status = 'publish'
			",
			LP_COURSE_CPT,
			$user_id,
			$status
		);

		$this->check_execute_has_error();

		return (int) $this->wpdb->get_var( $query );
	}
}

LP_Course_DB::getInstance();


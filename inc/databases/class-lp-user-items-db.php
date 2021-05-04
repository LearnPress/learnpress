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
	public static $extra_value_col = 'extra_value';

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
	 */
	public function get_course_items_by_user_item_id( $user_item_id_by_course_id = 0, $user_id = 0 ) {
		if ( empty( $user_item_id_by_course_id ) || empty( $user_id ) ) {
			return null;
		}

		/**
		 * Get cache
		 *
		 * Please clear cache when user action vs item. Ex: completed lesson, quiz. Start quiz...
		 */
		$course_items = wp_cache_get(
			'lp-course-items-' . $user_id . '-' . $user_item_id_by_course_id,
			'lp-user-course-items'
		);

		if ( ! $course_items ) {
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

			// Set cache .
			wp_cache_set(
				'lp-course-items-' . $user_id . '-' . $user_item_id_by_course_id,
				$course_items,
				'lp-user-course-items'
			);
		}

		return $course_items;
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
			$course_id, 'lp_course', $item_id
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
		$data   = array( 'learnpress_user_item_id' => $user_item_id, 'meta_key' => $meta_key, 'extra_value' => $value );
		$format = array( '%s', '%s' );

		$check_exist_data = $this->wpdb->get_var(
			$this->wpdb->prepare( "
				SELECT meta_id FROM $this->tb_lp_user_itemmeta
				WHERE " . self::$user_item_id_col . " = %d
				AND meta_key = %s
				",
				$user_item_id, $meta_key
			)
		);

		if ( $check_exist_data ) {
			$this->wpdb->update( $this->tb_lp_user_itemmeta, $data,
				array( self::$user_item_id_col => $user_item_id, 'meta_key' => $meta_key ), $format );
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
			$this->wpdb->prepare( "
				SELECT " . self::$extra_value_col . " FROM $this->tb_lp_user_itemmeta
				WHERE " . self::$user_item_id_col . " = %d
				AND meta_key = %s
				",
				$user_item_id,
				$meta_key
			)
		);
	}
}

LP_Course_DB::getInstance();


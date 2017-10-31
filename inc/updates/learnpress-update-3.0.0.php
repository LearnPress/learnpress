<?php

class LP_Update_30 {
	public static function update() {
		self::add_column_user_items();
		self::update_user_course_items();
	}

	/**
	 * Add columns for storing the time in GMT
	 * @since 3.0.0
	 */
	public static function add_column_user_items() {
		global $wpdb;
		$sql = $wpdb->prepare( "
		ALTER TABLE {$wpdb->learnpress_user_items}
		ADD COLUMN `start_time_gmt` DATETIME NULL DEFAULT %s AFTER `start_time`,
		ADD COLUMN `end_time_gmt` DATETIME NULL DEFAULT %s AFTER `end_time`;
	", '0000-00-00 00:00:00', '0000-00-00 00:00:00' );

		$wpdb->query( $sql );
		if ( $wpdb->last_error !== '' ) {
			learn_press_add_message( $wpdb->last_error, 'error' );
		}
	}

	public static function update_metas() {
		$query = "
	";
	}


	public static function update_user_course_items() {
		global $wpdb;

		// Get all courses in user items
		$item_courses = self::_get_item_courses();
		if ( ! $item_courses ) {
			return;
		}

		if ( ! $current_item_courses = self::_get_current_item_courses( $item_courses ) ) {
			return;
		}

		/**
		 * Execute 10 courses
		 */
		while ( $course_ids = array_splice( $item_courses, 0, 10 ) ) {

			// Delete existed retaken count to preventing duplication meta
			$query_args   = $course_ids;
			$format       = array_fill( 0, sizeof( $course_ids ), '%d' );
			$query_args[] = LP_COURSE_CPT;
			$query_args[] = '_lp_retaken_count';

			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->learnpress_user_itemmeta} 
				WHERE learnpress_user_item_id IN (
					SELECT MAX(user_item_id)
					FROM {$wpdb->learnpress_user_items}
					WHERE item_id IN(" . join( ',', $format ) . ")
						AND item_type = %s
					GROUP BY user_id, item_id
				)
				AND meta_key = %s;
			", $query_args );
			$wpdb->query( $query );

			/**
			 * Re-Calculate number of retaken count and update again.
			 */
			$query_args = array( '_lp_retaken_count', LP_COURSE_CPT );
			$query_args = array_merge( $query_args, $course_ids );
			$query = $wpdb->prepare( "
				INSERT INTO {$wpdb->learnpress_user_itemmeta}( `learnpress_user_item_id`, `meta_key`, `meta_value` )
				SELECT MAX( user_item_id ), %s, COUNT(*) - 1
				FROM {$wpdb->learnpress_user_items}
				WHERE item_type = %s
					AND item_id IN(" . join( ',', $format ) . ")
				GROUP BY user_id, item_id
			", $query_args );
			$wpdb->query( $query );
		}
	}

	/**
	 * Get user course items from learnpress_user_items.
	 *
	 * @param array $course_ids
	 *
	 * @return array
	 */
	protected static function _get_current_item_courses( $course_ids ) {
		global $wpdb;
		$query_args   = $course_ids;
		$format       = array_fill( 0, sizeof( $query_args ), '%d' );
		$query_args[] = LP_COURSE_CPT;
		$query        = $wpdb->prepare( "
			SELECT user_item_id, user_id, item_id
			FROM(
				SELECT user_item_id, user_id, item_id
				FROM {$wpdb->learnpress_user_items}
				WHERE item_id IN(" . join( ',', $format ) . ")
				AND item_type = %s
				ORDER BY user_id, item_id,user_item_id DESC
			) X
			GROUP BY user_id, item_id
		", $query_args );

		return $wpdb->get_results( $query );
	}

	protected static function _get_item_courses() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT DISTINCT item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE item_type = %s AND parent_id = 0
		", LP_COURSE_CPT );

		return $wpdb->get_col( $query );
	}

	public static function update_users() {
		// create table _learnpress_users
		// insert new row
	}
}

LP_Update_30::update();

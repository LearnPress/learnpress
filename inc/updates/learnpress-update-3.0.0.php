<?php
/**
 * Todo: update emails
 */


/**
 * Class LP_Update_30
 *
 * Helper class for updating database to 3.0.0
 */
class LP_Update_30 {

	/**
	 * Entry point
	 */
	public static function update() {
		LP_Debug::startTransaction();
		try {
			self::add_column_user_items();
			self::upgrade_orders();
			self::update_user_course_items();
			self::update_option_no_require_enroll();
			self::update_post_meta();
			self::update_settings();

			LP_Install::update_db_version();
			LP_Install::update_version();

			set_transient( 'lp_upgraded_30', 'yes', DAY_IN_SECONDS );
			LP_Debug::commitTransaction();
		}
		catch ( Exception $exception ) {
			LP_Debug::rollbackTransaction();
		}
	}

	protected static function update_settings() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->options}
			WHERE option_name LIKE %s
		", $wpdb->esc_like( 'learn_press' ) . '%' );

		$settings_defaults = array();
		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				$settings_defaults[ $row->option_name ] = $row->option_value;
			}
		}

		$new_options = array(
			'learn_press_profile_avatar'    => 'yes',
			'learn_press_profile_publicity' => array( 'dashboard' => 'yes' )
		);

		foreach ( $new_options as $k => $v ) {
			if ( ! array_key_exists( $k, $settings_defaults ) ) {
				update_option( $k, $v, 'yes' );
			}
		}
	}

	/**
	 * Update/Convert post meta
	 */
	protected static function update_post_meta() {
		global $wpdb;

		// Update quiz meta _lp_review_questions = 'yes' if both _lp_show_hide_question = 'yes' and _lp_show_result = 'yes'
		$query = $wpdb->prepare( "
			SELECT p.ID, pm1.meta_value AS show_hide_question, pm2.meta_value AS show_result
			FROM {$wpdb->posts} p 
			LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
			WHERE p.post_type = %s
			ORDER BY ID
		", '_lp_show_hide_question', '_lp_show_result', 'lp_quiz' );

		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				if ( $row->show_hide_question == 'yes' && $row->show_result == 'yes' ) {
					update_post_meta( $row->ID, '_lp_review_questions', 'yes' );
				}
			}
		}

		// Update quiz passing-grade to default 80% if passing-grade-type is set to no or point
		$query = $wpdb->prepare( "
			SELECT post_id, meta_value
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s AND (meta_value = %s OR meta_value = %s)
		", '_lp_passing_grade_type', 'no', 'point' );

		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				update_post_meta( $row->post_id, '_lp_passing_grade', '80' );
			}
		}

		$query = $wpdb->prepare( "
			SELECT p.ID, pm1.meta_value AS show_check_answer, pm2.meta_value AS show_hint
			FROM {$wpdb->posts} p 
			LEFT JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = %s
			LEFT JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s
			WHERE p.post_type = %s
			ORDER BY ID
		", '_lp_show_check_answer', '_lp_show_hint', 'lp_quiz' );

		//
		if ( $rows = $wpdb->get_results( $query ) ) {
			foreach ( $rows as $row ) {
				if ( $row->show_check_answer === 'yes' ) {
					update_post_meta( $row->ID, '_lp_show_check_answer', '-1' );
				} else {
					update_post_meta( $row->ID, '_lp_show_check_answer', '0' );
				}

				if ( $row->show_hint === 'yes' ) {
					update_post_meta( $row->ID, '_lp_show_hint', '-1' );
				} else {
					update_post_meta( $row->ID, '_lp_show_hint', '0' );
				}
			}
		}
	}

	public static function update_option_no_require_enroll() {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->postmeta}
			WHERE meta_key = %s 
		",
			'_lp_required_enroll' );

		$metas = $wpdb->get_results( $query );
	}

	public static function upgrade_orders() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT p.ID 
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
			INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = %s AND pm2.meta_value <> %s
			WHERE p.post_type = %s AND p.post_parent = 0
			LIMIT 0, 100
		", '_lp_multi_users', '_order_version', '3.0.0', LP_ORDER_CPT );

		if ( ! $parent_orders = $wpdb->get_col( $query ) ) {
			return false;
		}

		foreach ( $parent_orders as $parent_id ) {

			if ( ! $parent_order = learn_press_get_order( $parent_id ) ) {
				continue;
			}

			if ( $child_orders = self::get_child_orders( $parent_id ) ) {
				continue;
			}

			if ( ! $order_users = $parent_order->get_users() ) {
				continue;
			}
			if ( ! $child_orders = self::_create_child_orders( $parent_order, $order_users ) ) {
				continue;
			}

			foreach ( $child_orders as $uid => $child_order ) {
				$wpdb->update(
					$wpdb->learnpress_user_items,
					array(
						'ref_id' => $child_order->get_id()
					),
					array(
						'user_id' => $uid,
						'ref_id'  => $parent_id
					),
					array( '%d' ),
					array( '%d', '%d' )
				);
			}

			delete_post_meta( $parent_id, '_user_id' );
			update_post_meta( $parent_id, '_user_id', $order_users );
			update_post_meta( $parent_id, '_order_version', '3.0.0' );

		}

		return true;
	}

	/**
	 * @param LP_Order $order
	 * @param array    $user_ids
	 *
	 * @return array
	 */
	protected static function _create_child_orders( $order, $user_ids ) {
		$new_orders = array();
		if ( $child_orders = $order->get_child_orders( true ) ) {
			foreach ( $child_orders as $child_id ) {
				$child_order         = learn_press_get_order( $child_id );
				$child_order_user_id = $child_order->get_user( 'id' );
				if ( ! in_array( $child_order_user_id, $user_ids ) ) {
					wp_delete_post( $child_id );
					continue;
				}
				$order->cln_items( $child_order->get_id() );
				$new_orders[ $child_order_user_id ] = $child_order;
			}
		}

		foreach ( $user_ids as $uid ) {
			if ( empty( $new_orders[ $uid ] ) ) {
				$new_order          = $order->cln();
				$new_orders[ $uid ] = $new_order;
			} else {
				$new_order = $new_orders[ $uid ];
			}

			$new_order->set_order_date( $order->get_order_date() );
			$new_order->set_parent_id( $order->get_id() );
			$new_order->set_user_id( $uid );
			$new_order->set_total( $order->get_total() );
			$new_order->set_subtotal( $order->get_subtotal() );
			$new_order->save();
		}

		return $new_orders;
	}

	public static function get_child_orders( $parent_id ) {
		global $wpdb;
		$order = new LP_Order( $parent_id );
		LP_Debug::instance()->add( $order->get_child_orders(), false, false, true );

		return $order->get_child_orders();
	}

	/**
	 * Add columns for storing the time in GMT
	 * @since 3.0.0
	 */
	public static function add_column_user_items() {
		global $wpdb;
		ob_start();

		// Add columns start_time_gmt, end_time_gmt
		$sql = $wpdb->prepare( "
			ALTER TABLE {$wpdb->learnpress_user_items}
			ADD COLUMN `start_time_gmt` DATETIME NULL DEFAULT %s AFTER `start_time`,
			ADD COLUMN `end_time_gmt` DATETIME NULL DEFAULT %s AFTER `end_time`;
		", '0000-00-00 00:00:00', '0000-00-00 00:00:00' );
		@$wpdb->query( $sql );

		// Update start_time_gmt, end_time_gmt with offset time from start_time, end_time
		$time      = new LP_Datetime();
		$offset    = $time->getOffset( true );
		$null_time = LP_Datetime::getSqlNullDate();

		$sql = $wpdb->prepare( "
			UPDATE {$wpdb->learnpress_user_items}
			SET 
				start_time_gmt = IF(start_time = %s, %s, DATE_ADD(start_time, INTERVAL %f HOUR)),
				end_time_gmt = IF(end_time = %s, %s, DATE_ADD(end_time, INTERVAL %f HOUR))
		", $null_time, $null_time, $offset, $null_time, $null_time, $offset );
		@$wpdb->query( $sql );

		$sql = $wpdb->prepare( "
			ALTER TABLE {$wpdb->learnpress_user_items}
			CHANGE COLUMN `user_id` `user_id` BIGINT(20) NOT NULL DEFAULT %d ,
			CHANGE COLUMN `item_id` `item_id` BIGINT(20) NOT NULL DEFAULT %d ;
		", - 1, - 1 );
		@$wpdb->query( $sql );

		ob_get_clean();
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
			$query      = $wpdb->prepare( "
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

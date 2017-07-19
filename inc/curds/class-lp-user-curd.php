<?php

class LP_User_CURL implements LP_Interface_CURD {
	public function update() {
		// TODO: Implement update() method.
	}

	public function delete() {
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
					'username'      => $user_object->user_login,
					'display_name'  => $user_object->display_name,
					'date_created'  => $user_object->user_registered,
					'date_modified' => get_user_meta( $user_id, 'last_update', true ),
					'role'          => ! empty( $user_object->roles[0] ) ? $user_object->roles[0] : 'student',
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
	 *
	 * @return array|mixed
	 */
	public function get_orders( $user_id ) {

		// If user does not exists
		if ( ! learn_press_get_user( $user_id ) ) {
			return false;
		}

		global $wpdb;

		//$data = LP_Cache::get_user_course_order( false, array() );

		// Get orders for the user from cache
		$orders = wp_cache_get( 'user-' . $user_id, 'lp-user-orders' );

		if ( false === $orders ) {
			$orders = array();
			$query  = $wpdb->prepare( "
				SELECT o.ID, oim.meta_value as course_id
				FROM {$wpdb->learnpress_order_items} oi
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oim.learnpress_order_item_id = oi.order_item_id AND meta_key = %s
				INNER JOIN {$wpdb->postmeta} om ON om.post_id = oi.order_id AND om.meta_key = %s AND om.meta_value = %d
				INNER JOIN {$wpdb->posts} o ON o.ID = om.post_id AND o.post_status <> %s
				WHERE o.post_type = %s ORDER BY ID ASC
			", '_course_id', '_user_id', $user_id, 'trash', LP_ORDER_CPT );

			if ( $rows = $wpdb->get_results( $query ) ) {
				foreach ( $rows as $row ) {
					if ( empty( $orders[ $row->course_id ] ) ) {
						$orders[ $row->course_id ] = array();
					}
					$orders[ $row->course_id ][] = $row->ID;
				}

				// Sort the order ids from highest to lowest
				foreach ( $orders as $course_id => $order_ids ) {
					if ( $order_ids ) {
						rsort( $orders[ $course_id ] );
					}
				}
			}

			// Store to cache
			wp_cache_set( 'user-' . $user_id, $orders, 'lp-user-orders' );
		}

		return $orders;
	}
}
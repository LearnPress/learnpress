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

	/**
	 * Read course data for an user.
	 *
	 * @param int       $user_id
	 * @param int|array $course_id
	 *
	 * @return bool
	 */
	public function read_user_course( $user_id, $course_id ) {
		// If user does not exists
		if ( ! learn_press_get_course( $course_id ) ) {
			return false;
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
			if ( false === wp_cache_get( 'course-' . $id, 'lp-user-courses' ) ) {
				$fetch_ids[] = $id;
				wp_cache_set( 'course-' . $user_id . '-' . $id, array( 'items' => array() ), 'lp-user-courses' );
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

		echo $query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_user_items}
			WHERE item_type = %s
			AND item_id IN(" . join( ',', $format ) . ")
			ORDER BY user_item_id DESC LIMIT 1
		", $args );

		if ( $results = $wpdb->get_results( $query, ARRAY_A ) ) {
			foreach ( $results as $result ) {
				$result['items'] = array();
				$this->_read_user_course_items( $result );
				wp_cache_replace( 'course-' . $user_id . '-' . $result['item_id'], $result, 'lp-user-courses' );
			}
		}

		return true;
	}

	/**
	 * Load user items by item_id of course item
	 *
	 * @param object $parent_item
	 *
	 * @return bool
	 */
	protected function _read_user_course_items( &$parent_item ) {
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
			$items = array();
			foreach ( $results as $result ) {
				$user_item_id = $result['item_id'];
				if ( empty( $items[ $user_item_id ] ) ) {
					$items[ $user_item_id ] = array();
					$parent_item['items'][] = $user_item_id;
				}

				$items[ $user_item_id ][ $result['item_id'] ][ $result['user_item_id'] ] = $result;
			}

			foreach ( $items as $user_item_id => $_items ) {
				wp_cache_set( 'course-item-' . $user_item_id, $_items, 'lp-user-course-items' );
			}
		}

		return true;
	}

}
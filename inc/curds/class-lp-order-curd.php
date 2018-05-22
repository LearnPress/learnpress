<?php

/**
 * Class LP_Order_CURD
 *
 * @since 3.0.0
 */
class LP_Order_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

	/**
	 * @var string
	 */
	protected $_meta_type = 'post';

	/**
	 * @var array
	 */
	protected $_meta_keys = array();

	/**
	 * @param LP_Order $order
	 *
	 * @return mixed
	 */
	public function create( &$order ) {

		$order->set_order_date( current_time( 'timestamp' ) );
		$order->set_order_key( learn_press_generate_order_key() );

		$order_data = array(
			'post_author'   => '1',
			'post_parent'   => $order->get_parent_id(),
			'post_type'     => LP_ORDER_CPT,
			'post_status'   => $order->get_order_status(),
			'ping_status'   => 'closed',
			'post_title'    => $order->get_title(),
			'post_date'     => $order->get_order_date( 'edit' )->toSql( true ),
			'post_date_gmt' => $order->get_order_date( 'edit' )->toSql( false ),
			'post_excerpt'  => $order->get_customer_note()
		);

		$order_data = apply_filters( 'learn-press/order/new-data', $order_data );

		$id = wp_insert_post( $order_data, true );

		if ( $id && ! is_wp_error( $id ) ) {
			$order->set_id( $id );
			$this->_updates( $order );
		}

		return $id;
	}

	/**
	 * @param LP_Order $order
	 */
	public function _updates( &$order ) {
		$this->update_post_meta( $order );
		$order->update_meta();
	}

	/**
	 * @param LP_Order $order
	 */
	public function update_post_meta( $order ) {
		$meta_data = array(
			'_order_currency'       => $order->get_currency(),
			'_prices_include_tax'   => 'no',
			'_user_id'              => $order->get_user_id(),
			'_order_subtotal'       => $order->get_subtotal(),
			'_order_total'          => $order->get_total(),
			'_order_key'            => $order->get_order_key(),
			'_payment_method'       => $order->get_payment_method_title(),
			'_payment_method_title' => $order->get_payment_method_title(),
			'_user_ip_address'      => $order->get_user_ip_address(),
			'_user_agent'           => $order->get_user_agent(),
			'_order_version'        => '3.0.0',
			'_created_via'          => $order->get_created_via()
		);

		if ( $checkout_email = $order->get_checkout_email() ) {
			$meta_data['_checkout_email'] = $checkout_email;
		}

		foreach ( $meta_data as $key => $value ) {
			update_post_meta( $order->get_id(), $key, $value );
		}
	}

	/**
	 * Read order's items
	 *
	 * @param LP_Order $order
	 *
	 * @return mixed
	 */
	public function read_items( $order ) {

		if ( false === ( $items = wp_cache_get( 'order-' . $order->get_id(), 'lp-order-items' ) ) ) {

			global $wpdb;

			$query = $wpdb->prepare( "
				SELECT order_item_id as id, order_item_name as name
				FROM {$wpdb->learnpress_order_items} oi 
				WHERE order_id = %d 
			", $order->get_id() );

			$_items = $wpdb->get_results( $query );
			$items  = array();
			// Loop items
			if ( $_items ) {
				foreach ( $_items as $item ) {
					$item = (array) $item;
					$this->get_item_meta( $item );
					if ( ! empty( $item['course_id'] ) ) {
						//$items[ $item['id'] ]['name'] = $item['name'];
						//$items[ $item['id'] ]['id']   = $item['id'];

						$items[ $item['id'] ] = $item;
					}
				}
			}

			wp_cache_set( 'order-' . $order->get_id(), $items, 'lp-order-items' );
		}

		return $items;// apply_filters( 'learn_press_order_get_items', $items, $this );
	}

	public function get_item_meta( &$item ) {
		if ( $metas = get_metadata( 'learnpress_order_item', $item['id'] ) ) {
			foreach ( $metas as $k => $v ) {
				$item[ preg_replace( '!^_!', '', $k ) ] = maybe_unserialize( $v[0] );
			}
		};
		//$item = wp_parse_args()
	}

	/**
	 * Update order.
	 *
	 * @param LP_Order $order
	 *
	 * @return int
	 */
	public function update( &$order ) {

		// If there is no items in the order then set it status to Pending
		$status = $order->get_status() ? $order->get_status() : learn_press_default_order_status();
		if ( in_array( $status, array( 'completed', 'processing' ) ) && ! $order->get_items() ) {
			$status = 'pending';
		}


		$post_data = array(
			'post_date'     => $order->get_order_date( 'edit' )->toSql(),
			'post_date_gmt' => $order->get_order_date( 'edit' )->toSql( false ),
			'post_status'   => 'lp-' . $status,
			'post_parent'   => $order->get_parent_id(),
			//'post_excerpt'      => $this->get_post_excerpt( $order ),
			//'post_modified'     => $order->get_date_modified( ),
			//'post_modified_gmt' => $order->get_date_modified( ),
		);

		$post_data = apply_filters( 'learn-press/order/update-data', $post_data, $order->get_id() );


		/**
		 * When updating this object, to prevent infinite loops, use $wpdb
		 * to update data, since wp_update_post spawns more calls to the
		 * save_post action.
		 *
		 * This ensures hooks are fired by either WP itself (admin screen save),
		 * or an update purely from CRUD.
		 */
		if ( doing_action( 'save_post' ) ) {
			$GLOBALS['wpdb']->update( $GLOBALS['wpdb']->posts, $post_data, array( 'ID' => $order->get_id() ) );
			clean_post_cache( $order->get_id() );
		} else {
			wp_update_post( array_merge( array( 'ID' => $order->get_id() ), $post_data ) );
		}
		$this->_updates( $order );

		return $order->get_id();
	}

	public function delete( &$object ) {
		// TODO: Implement delete() method.
	}

	/**
	 * Delete all data related to the order is being deleted.
	 * Including:
	 *      + Order item metas  - learnpress_order_itemmeta
	 *      + Order items       - learnpress_order_items
	 *      + User item metas   - learnpress_user_itemmeta
	 *      + User items        - learnpress_user_items
	 *
	 * @param LP_Order $order
	 *
	 * @return mixed
	 */
	public function delete_order_data( $order ) {
		global $wpdb;

		// Get order items
		$query = $wpdb->prepare( "
			SELECT order_item_id FROM {$wpdb->prefix}learnpress_order_items
			WHERE order_id = %d
		", $order->get_id() );

		if ( ! $item_ids = $wpdb->get_col( $query ) ) {
			return false;
		}

		// If order is for multi-users then delete all child orders first.
		if ( $order->is_multi_users() ) {
			if ( $child_orders = $order->get_child_orders() ) {
				foreach ( $child_orders as $child_order ) {
					if ( $child_order = learn_press_get_order( $child_order ) ) {
						$this->delete_order_data( $child_order );
					}
				}
			}
		}

		// Get user order
		$user_id     = intval( get_post_meta( $order->get_id(), '_user_id', true ) );
		$order_items = $order->get_item_ids();

		// Delete rows from order item meta data
		$query = "
			DELETE FROM {$wpdb->prefix}learnpress_order_itemmeta
			WHERE learnpress_order_item_id IN(" . join( ',', $item_ids ) . ")
		";
		$wpdb->query( $query );

		// Delete order items
		$query = $wpdb->prepare( "
			DELETE FROM {$wpdb->prefix}learnpress_order_items
			WHERE order_id = %d
		", $order->get_id() );
		$wpdb->query( $query );

		/**
		 * Get all user item id related to user with current order
		 */
		$user_item_ids = array();
		if ( $user_data = get_post_meta( $order->get_id(), '_lp_user_data', true ) ) {
			foreach ( $user_data as $user_item_id => $data ) {
				$user_item_ids[] = $user_item_id;
			}
		}

		// Get all courses in this order that user has already enrolled (existed in learnpress_user_items)
		$order_items_format = array_fill( 0, sizeof( $order_items ), '%d' );
		$query_args         = array_merge( $order_items, array( $order->get_id(), $user_id, LP_ORDER_CPT ) );

		$query = $wpdb->prepare( "
			SELECT user_item_id
			FROM {$wpdb->prefix}learnpress_user_items
			WHERE item_id IN(" . join( ',', $order_items_format ) . ")
				AND ref_id = %d 
				AND user_id = %d 
				AND ref_type = %s
		", $query_args );

		if ( $_user_item_ids = $wpdb->get_col( $query ) ) {
			$user_item_ids = array_merge( $user_item_ids, $_user_item_ids );
		}

		if ( $user_item_ids ) {
			$user_factory = LP_Factory::get_user_factory();
			foreach ( $user_item_ids as $user_item_id ) {
				$user_factory->delete_by_user_item_id( $user_item_id );
			}
		}

		if ( 1 == 0 ) {
			print_r( $user_item_id );

			// Delete user course items
			echo $query = $wpdb->prepare( "
				DELETE
				FROM ui, uim
				USING {$wpdb->prefix}learnpress_user_items AS ui
				LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta AS uim ON ui.user_item_id = uim.learnpress_user_item_id
				WHERE ref_id = %d AND user_id = %d AND ref_type = %s
			", $order->get_id(), $user_id, LP_ORDER_CPT );
			$wpdb->query( $query );

			// Delete other items
			$format = array_fill( 0, sizeof( $course_ids ), '%d' );
			$args   = array_merge( $course_ids, array( $user_id ) );
			echo $query = $wpdb->prepare( "
						DELETE
						FROM ui, uim
						USING {$wpdb->prefix}learnpress_user_items AS ui
						LEFT JOIN {$wpdb->prefix}learnpress_user_itemmeta AS uim ON ui.user_item_id = uim.learnpress_user_item_id
						WHERE ref_id IN(" . join( ',', $format ) . ") AND user_id = %d
					", $args );
			$wpdb->query( $query );

		}

		// delete all data related user order
		if ( $user_id ) {
			//learn_press_delete_user_data( $user_id );
		}

		return true;
	}

	/**
	 * @param LP_Order $order
	 *
	 * @return LP_Order
	 */
	public function cln( $order ) {

		$cloned = clone $order;

		$cloned->set_id( 0 );
		$cloned->save();

		$meta_keys = array(
			'_order_currency',
			'_prices_include_tax',
			'_user_ip_address',
			'_user_agent',
			'_order_subtotal',
			'_order_total',
			'_order_key',
			'_payment_method',
			'_payment_method_title',
			'_order_version',
			'_created_via'
		);

		foreach ( $meta_keys as $key ) {
			update_post_meta( $cloned->get_id(), $key, get_post_meta( $order->get_id(), $key, true ) );
		}

		$this->cln_items( $order->get_id(), $cloned->get_id() );

		return $cloned;
	}

	/**
	 * Clone items from an order to another
	 *
	 * @param int $from
	 * @param int $to
	 *
	 * @return mixed
	 */
	public function cln_items( $from, $to ) {
		$order = learn_press_get_order( $from );
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_order_items} oi 
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oi.order_item_id = oim.learnpress_order_item_id
			WHERE order_id = %d 
		", $from );
		if ( $items = $order->get_items() ) {
//
//
//			$sql = $wpdb->prepare( "
//				SELECT order_item_id, oim.meta_value AS course_id
//				FROM {$wpdb->learnpress_order_itemmeta} oim
//				INNER JOIN {$wpdb->learnpress_order_items} oi ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key = %s
//			", '_course_id' );

			if ( ! $to_order = learn_press_get_order( $to ) ) {
				return false;
			}
			$to_order->remove_order_items();

			foreach ( $items as $item ) {
				$wpdb->insert(
					$wpdb->learnpress_order_items,
					array(
						'order_item_name' => $item['name'],
						'order_id'        => $to
					)
				);
				$learnpress_order_item_id = $wpdb->insert_id;

				$query = $wpdb->prepare( "
					SELECT *
					FROM {$wpdb->learnpress_order_itemmeta}
					WHERE learnpress_order_item_id = %d
				", $item['id'] );

				if ( $metas = $wpdb->get_results( $query ) ) {
					foreach ( $metas as $meta ) {
						$wpdb->insert(
							$wpdb->learnpress_order_itemmeta,
							array(
								'learnpress_order_item_id' => $learnpress_order_item_id,
								'meta_key'                 => $meta->meta_key,
								'meta_value'               => $meta->meta_value
							)
						);
					}
				}
			}
		}
		$this->read_items( $order );

		return true;
	}

	/**
	 * Load order data
	 *
	 * @param LP_Order $order
	 *
	 * @throws Exception
	 *
	 * @return mixed
	 */
	public function load( &$order ) {
		$the_id = $order->get_id();
		if ( ! $the_id || LP_ORDER_CPT !== get_post_type( $the_id ) ) {
			if ( learn_press_is_debug() ) {
				throw new Exception( sprintf( __( 'Invalid order with ID "%d".', 'learnpress' ), $the_id ) );
			}
		}
		if ( $post = get_post( $the_id ) ) {
			if ( $_users = get_post_meta( $order->get_id(), '_user_id' ) ) {
				settype( $_users, 'array' );
			} else {
				$_users = array();
			}
			if ( sizeof( $_users ) > 1 ) {
				$users = $_users;
			} elseif ( sizeof( $_users ) == 1 ) {
				$users = $_users[0];
			} else {
				$users = 0;
			}

			$order->set_data_via_methods(
				array(
					'user_id'         => $users,//get_post_meta( $order->get_id(), '_user_id', true ),
					'order_date'      => new LP_Datetime( $post->post_date ),
					'date_modified'   => new LP_Datetime( $post->post_modified ),
					'status'          => str_replace( 'lp-', '', $post->post_status ),
					'parent_id'       => $post->post_parent,
					'created_via'     => get_post_meta( $post->ID, '_created_via', true ),
					'total'           => get_post_meta( $post->ID, '_order_total', true ),
					'subtotal'        => get_post_meta( $post->ID, '_order_subtotal', true ),
					'order_key'       => get_post_meta( $post->ID, '_order_key', true ),
					'user_ip_address' => get_post_meta( $post->ID, '_user_ip_address', true ),
					'user_agent'      => get_post_meta( $post->ID, '_user_agent', true ),
					'checkout_email'  => get_post_meta( $post->ID, '_checkout_email', true ),
					'currency'        => get_post_meta( $post->ID, '_order_currency', true )
				)
			);
			$this->read_items( $order );
			$order->read_meta();
		}

		return true;
	}

	/**
	 * Recover an order checked out by Guest for an user.
	 *
	 * @param string $order_key
	 * @param int    $user_id
	 *
	 * @return bool|LP_Order|WP_Error
	 */
	public function recover( $order_key, $user_id ) {
		try {
			$order = $this->get_order_by_key( $order_key );

			// Validations
			if ( ! $order ) {
				throw new Exception( __( 'Invalid order.', 'learnpress' ), 1000 );
			}

			if ( ! $order->is_guest() ) {
				throw new Exception( __( 'Order is already assigned.', 'learnpress' ), 1010 );
			}

			$user = learn_press_get_user( $user_id );

			if ( ! $user ) {
				throw new Exception( __( 'User does not exist.', 'learnpress' ), 1020 );
			}

			global $wpdb;

			// Set user to order and update
			$order->set_user_id( $user_id );
			$order->save();

			// Trigger action
			do_action( 'learn-press/order/recovered-successful', $order->get_id(), $user_id );
		}
		catch ( Exception $ex ) {
			return new WP_Error( $ex->getCode(), $ex->getMessage() );
		}

		return $order;
	}

	/**
	 * Retrieve an order by order key.
	 *
	 * @param string $order_key
	 *
	 * @return bool|LP_Order
	 */
	public function get_order_by_key( $order_key ) {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT ID
			FROM {$wpdb->posts} p 
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %s
		", '_order_key', $order_key );

		$order = false;
		if ( $order_id = $wpdb->get_var( $query ) ) {
			$order = learn_press_get_order( $order_id );
		}

		return $order;
	}

	/**
	 * Get all child orders of an order by id
	 *
	 * @param int $order_id
	 *
	 * @return array|bool|mixed
	 */
	public function get_child_orders( $order_id ) {
		global $wpdb;

		if ( false === ( $orders = wp_cache_get( 'order-' . $order_id, 'lp-child-orders' ) ) ) {
			$query = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->posts}
				WHERE post_parent = %d
			", $order_id );
			if ( $posts = $wpdb->get_results( $query ) ) {
				foreach ( $posts as $order ) {
					new WP_Post( $order );
					$orders[] = $order->ID;
				}
			} else {
				$orders = array();
			}
			wp_cache_set( 'order-' . $order_id, $orders, 'lp-child-orders' );
		}

		return $orders;
	}

	public function duplicate( &$order, $args = array() ) {
		// TODO: Implement duplicate() method.
	}
}
<?php
/**
 * Class LP_Order_CURD
 *
 * @since 3.0.0
 */

defined( 'ABSPATH' ) || exit();

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
		if ( ! $order instanceof LP_Order ) {
			return false;
		}

		$order->set_order_date( current_time( 'mysql' ) );
		$order->set_order_key( learn_press_generate_order_key() );

		$order_data = array(
			'post_author'   => get_current_user_id(),
			'post_parent'   => $order->get_parent_id(),
			'post_type'     => LP_ORDER_CPT,
			'post_status'   => $order->get_order_status(),
			'ping_status'   => 'closed',
			'post_title'    => $order->get_title(),
			'post_date'     => $order->get_order_date( 'edit' )->toSql( true ),
			'post_date_gmt' => $order->get_order_date( 'edit' )->toSql( false ),
			'post_excerpt'  => $order->get_customer_note(),
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
			'_created_via'          => $order->get_created_via(),
		);

		$checkout_email = $order->get_checkout_email();
		if ( $checkout_email ) {
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
	/*
	 old function - comment by tungnx
	public function read_items( $order ) {
		global $wpdb;
		$screen = function_exists('get_current_screen')? get_current_screen():null;
		$query = $wpdb->prepare( "
			SELECT order_item_id as id, order_item_name as name
				, oim.meta_value as `course_id`
				# , oim2.meta_value as `quantity`
				# , oim3.meta_value as `total`
			FROM {$wpdb->learnpress_order_items} oi
				INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oi.order_item_id = oim.learnpress_order_item_id AND oim.meta_key='_course_id'
				# INNER JOIN {$wpdb->learnpress_order_itemmeta} oim2 ON oi.order_item_id = oim2.learnpress_order_item_id AND oim2.meta_key='_quantity'
				# INNER JOIN {$wpdb->learnpress_order_itemmeta} oim3 ON oi.order_item_id = oim3.learnpress_order_item_id AND oim3.meta_key='_total'
			WHERE order_id = %d
		", $order->get_id() );

		$_items = $wpdb->get_results( $query );
		$items  = array();
		// Loop items
		if ( $_items ) {
			foreach ( $_items as $item ) {
				$item = (array) $item;
				$items[$item['id']] = $item;
				if(!$screen || $screen->id !== 'edit-lp_order'){
					$this->get_item_meta( $item );
				}
				if ( ! empty( $item['course_id'] ) ) {
					$items[ $item['id'] ] = $item;
				}
			}
		}

		return $items;// apply_filters( 'learn_press_order_get_items', $items, $this );
	} */

	/**
	 * Get order's items
	 *
	 * @param LP_Order $order
	 *
	 * @return mixed
	 * @author tungnx
	 */
	public function read_items( $order ) {
		global $wpdb;

		$table_order_items     = $wpdb->learnpress_order_items;
		$table_order_item_meta = $wpdb->learnpress_order_itemmeta;

		$query = $wpdb->prepare(
			"SELECT o_item.order_item_id, o_item.order_item_name, o_itemmeta.meta_value, o_itemmeta.meta_key
					From $table_order_items as o_item
					INNER JOIN $table_order_item_meta as o_itemmeta
					ON o_item.order_item_id = o_itemmeta.learnpress_order_item_id
					WHERE o_item.order_id = %d
					LIMIT %d",
			$order->get_id(),
			4 * 50
		);

		// Todo: write query to function and call ajax load more
		$order_item_metas = $wpdb->get_results( $query, OBJECT );

		$items = array();

		foreach ( $order_item_metas as $order_item_meta ) {
			if ( ! array_key_exists( $order_item_meta->order_item_id, $items ) ) {
				$items[ $order_item_meta->order_item_id ]         = array();
				$items[ $order_item_meta->order_item_id ]['id']   = $order_item_meta->order_item_id;
				$items[ $order_item_meta->order_item_id ]['name'] = isset( $order_item_meta->order_item_name ) ? $order_item_meta->order_item_name : '';
			}

			switch ( $order_item_meta->meta_key ) {
				case '_course_id':
					$items[ $order_item_meta->order_item_id ]['course_id'] = $order_item_meta->meta_value;
					break;
				case '_quantity':
					$items[ $order_item_meta->order_item_id ]['quantity'] = $order_item_meta->meta_value;
					break;
				case '_subtotal':
					$items[ $order_item_meta->order_item_id ]['subtotal'] = $order_item_meta->meta_value;
					break;
				case '_total':
					$items[ $order_item_meta->order_item_id ]['total'] = $order_item_meta->meta_value;
					break;
				default:
					$items[ $order_item_meta->order_item_id ][ $order_item_meta->meta_key ] = $order_item_meta->meta_value;
			}
		}

		return $items; // apply_filters( 'learn_press_order_get_items', $items, $this );
	}

	/**
	 * Get order's items
	 *
	 * @param LP_Order $order
	 * @param array    $filter
	 *
	 * @return mixed
	 * @author tungnx
	 */
	public function read_items_filter( $order, $filter = array() ) {
		global $wpdb;

		$p      = $filter['p'];
		$limit  = $filter['limit'] * 4; // 4 fields on 1 item
		$offset = $p * $limit;

		$table_order_items     = $wpdb->learnpress_order_items;
		$table_order_item_meta = $wpdb->learnpress_order_itemmeta;

		$query = $wpdb->prepare(
			"SELECT o_item.order_item_id, o_item.order_item_name, o_itemmeta.meta_value, o_itemmeta.meta_key
					From $table_order_items as o_item
					INNER JOIN $table_order_item_meta as o_itemmeta
					ON o_item.order_item_id = o_itemmeta.learnpress_order_item_id
					WHERE o_item.order_id = %d
					LIMIT %d, %d",
			$order->get_id(),
			$offset,
			$limit
		);

		// Todo: write query to function and call ajax load more
		$order_item_metas = $wpdb->get_results( $query, OBJECT );

		$items = array();

		foreach ( $order_item_metas as $order_item_meta ) {
			if ( ! array_key_exists( $order_item_meta->order_item_id, $items ) ) {
				$items[ $order_item_meta->order_item_id ]         = array();
				$items[ $order_item_meta->order_item_id ]['id']   = $order_item_meta->order_item_id;
				$items[ $order_item_meta->order_item_id ]['name'] = isset( $order_item_meta->order_item_name ) ? $order_item_meta->order_item_name : '';
			}

			switch ( $order_item_meta->meta_key ) {
				case '_course_id':
					$items[ $order_item_meta->order_item_id ]['course_id'] = $order_item_meta->meta_value;
					break;
				case '_quantity':
					$items[ $order_item_meta->order_item_id ]['quantity'] = $order_item_meta->meta_value;
					break;
				case '_subtotal':
					$items[ $order_item_meta->order_item_id ]['subtotal'] = $order_item_meta->meta_value;
					break;
				case '_total':
					$items[ $order_item_meta->order_item_id ]['total'] = $order_item_meta->meta_value;
					break;
				default:
					$items[ $order_item_meta->order_item_id ][ $order_item_meta->meta_key ] = $order_item_meta->meta_value;
			}
		}

		return $items; // apply_filters( 'learn_press_order_get_items', $items, $this );
	}

	public function getTotalItem() {

	}

	public function get_item_meta( &$item ) {
		$metas = get_metadata( 'learnpress_order_item', $item['id'] );
		if ( $metas ) {
			foreach ( $metas as $k => $v ) {
				$item[ preg_replace( '!^_!', '', $k ) ] = LP_Helper::maybe_unserialize( $v[0] );
			}
		};
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
		/*if ( in_array( $status, array( 'completed', 'processing' ) ) && ! $order->get_items() ) {
			$status = 'pending';
		}*/

		$post_data = array(
			'post_date'     => $order->get_order_date( 'edit' )->toSql(),
			'post_date_gmt' => $order->get_order_date( 'edit' )->toSql( false ),
			'post_status'   => 'lp-' . $status,
			'post_parent'   => $order->get_parent_id(),
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
			'_created_via',
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

		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->learnpress_order_items} oi
			INNER JOIN {$wpdb->learnpress_order_itemmeta} oim ON oi.order_item_id = oim.learnpress_order_item_id
			WHERE order_id = %d
		",
			$from
		);

		$items = $order->get_items();
		if ( $items ) {
			$to_order = learn_press_get_order( $to );
			if ( ! $to_order ) {
				return false;
			}

			$to_order->remove_order_items();

			foreach ( $items as $item ) {
				$wpdb->insert(
					$wpdb->learnpress_order_items,
					array(
						'order_item_name' => $item['name'],
						'order_id'        => $to,
					)
				);
				$learnpress_order_item_id = $wpdb->insert_id;

				$query = $wpdb->prepare(
					"
					SELECT *
					FROM {$wpdb->learnpress_order_itemmeta}
					WHERE learnpress_order_item_id = %d
				",
					$item['id']
				);

				if ( $metas = $wpdb->get_results( $query ) ) {
					foreach ( $metas as $meta ) {
						$wpdb->insert(
							$wpdb->learnpress_order_itemmeta,
							array(
								'learnpress_order_item_id' => $learnpress_order_item_id,
								'meta_key'                 => $meta->meta_key,
								'meta_value'               => $meta->meta_value,
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
	 * @return mixed
	 * @throws Exception
	 */
	public function load( &$order ) {
		$the_id = $order->get_id();
		if ( ! $the_id || LP_ORDER_CPT !== learn_press_get_post_type( $the_id ) ) {
			if ( LP_Debug::is_debug() ) {
				throw new Exception( sprintf( __( 'Invalid order with ID "%d".', 'learnpress' ), $the_id ) );
			}
		}

		$post = get_post( $the_id );
		if ( $post ) {
			$_users = get_post_meta( $order->get_id(), '_user_id' );
			if ( $_users ) {
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
					'user_id'         => $users,
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
					'currency'        => get_post_meta( $post->ID, '_order_currency', true ),
				)
			);

			$order->get_items();
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
				throw new Exception( esc_html__( 'Invalid order.', 'learnpress' ) );
			}

			if ( ! $order->is_guest() ) {
				throw new Exception( esc_html__( 'Order is already assigned.', 'learnpress' ) );
			}

			$user = learn_press_get_user( $user_id );

			if ( ! $user || $user instanceof LP_User_Guest ) {
				throw new Exception( esc_html__( 'User invalid!.', 'learnpress' ) );
			}

			if ( $order->get_checkout_email() !== $user->get_email() ) {
				throw new Exception( esc_html__( 'Order key invalid with Email!', 'learnpress' ) );
			}

			// Set user to order and update
			$order->set_user_id( $user_id );
			$order->save();

			// Update user_id of lp_user_item
			if ( $order->is_completed() ) {
				$lp_user_items_db = LP_User_Items_DB::getInstance();
				$filter           = new LP_User_Items_Filter();
				$filter->user_id  = $user_id;
				$filter->ref_id   = $order->get_id();
				$lp_user_items_db->update_user_id_by_order( $filter );
			}

			// Trigger action
			do_action( 'learn-press/order/recovered-successful', $order->get_id(), $user_id );
		} catch ( Exception $ex ) {
			return new WP_Error( 'lp_order_recover_error', $ex->getMessage() );
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
		$query = $wpdb->prepare(
			"
			SELECT ID
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s AND pm.meta_value = %s
		",
			'_order_key',
			$order_key
		);

		$order    = false;
		$order_id = $wpdb->get_var( $query );

		if ( $order_id ) {
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

		$orders = LP_Object_Cache::get( 'order-' . $order_id, 'lp-child-orders' );

		if ( false === $orders ) {
			$query = $wpdb->prepare(
				"
				SELECT *
				FROM {$wpdb->posts}
				WHERE post_parent = %d
			",
				$order_id
			);

			$posts = $wpdb->get_results( $query );
			if ( $posts ) {
				foreach ( $posts as $order ) {
					new WP_Post( $order );
					$orders[] = $order->ID;
				}
			} else {
				$orders = array();
			}
			LP_Object_Cache::set( 'order-' . $order_id, $orders, 'lp-child-orders' );
		}

		return $orders;
	}

	public function duplicate( &$order, $args = array() ) {
		// TODO: Implement duplicate() method.
	}
}

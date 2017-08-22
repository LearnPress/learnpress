<?php

/**
 * Class LP_Order_CURD
 *
 * @since 3.x.x
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
		$order_data = array(
			'post_author' => '1',
			'post_parent' => $order->get_parent_id(),
			'post_type'   => LP_ORDER_CPT,
			'post_status' => 'lp-' . apply_filters( 'learn_press_default_order_status', 'pending' ),
			'ping_status' => 'closed',
			'post_title'  => $order->get_title(),
			'post_date'   => $order->get_order_date()
		);

		$order_data = apply_filters( 'learn-press/new-order-data', $order_data );

		$id = wp_insert_post( $order_data );

		if ( $id && ! is_wp_error( $id ) ) {
			$order->set_id( $id );
			$this->_updates( $order );
		}

		/*array(
			'_order_currency'       => learn_press_get_currency(),
			'_prices_include_tax'   => 'no',
			'_user_ip_address'      => learn_press_get_ip(),
			'_user_agent'           => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
			'_user_id'              => get_current_user_id(),
			'_order_subtotal'       => $cart ? $cart->subtotal : 0,
			'_order_total'          => $cart ? $cart->total : 0,
			'_order_key'            => learn_press_generate_order_key(),
			'_payment_method'       => '',
			'_payment_method_title' => '',
			'_order_version'        => '1.0',
			'_created_via'          => 'checkout'
		);*/

		return $id;
	}

	/**
	 * @param LP_Order $order
	 */
	public function _updates( &$order ) {
		update_post_meta( $order->get_id(), '_user_id', $order->get_user_id() );
		$order->update_meta();
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
	 */
	public function update( &$order ) {
		$post_data = array(
			'post_date'     => $order->get_order_date()->toSql( true ),
			'post_date_gmt' => $order->get_order_date()->toSql(),
			'post_status'   => 'lp-' . ( $order->get_status() ? $order->get_status() : apply_filters( 'learn-press/default-order-status', 'pending' ) ),
			'post_parent'   => $order->get_parent_id(),
			//'post_excerpt'      => $this->get_post_excerpt( $order ),
			//'post_modified'     => $order->get_date_modified( ),
			//'post_modified_gmt' => $order->get_date_modified( ),
		);


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

		//$order->read_meta_data( true ); // Refresh internal meta data, in case things were hooked into `save_post` or another WP hook.
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

		$cloned = function_exists( 'clone' ) ? clone ( $order ) : clone $order;

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
			//echo $cloned->get_id(),',', $key,',', get_post_meta( $order->get_id(), $key, true );
		}


//die();
		$this->cln_items( $order->get_id(), $cloned->get_id() );

		return $cloned;
	}

	/**
	 * Clone items from an order to another
	 *
	 * @param int $from
	 * @param int $to
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
			$order->set_data_via_methods(
				array(
					'user_id'       => get_post_meta( $order->get_id(), '_user_id', true ),
					'order_date'    => new LP_Datetime( $post->post_date ),
					'date_modified' => new LP_Datetime( $post->post_modified ),
					'status'        => str_replace( 'lp-', '', $post->post_status ),
					'parent_id'     => $post->post_parent
				)
			);

			$this->read_items( $order );
			$order->read_meta();
		}

		return true;
	}

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
}
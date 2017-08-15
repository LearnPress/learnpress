<?php

/**
 * Class LP_Order_CURD
 *
 * @since 3.x.x
 */
class LP_Order_CURD implements LP_Interface_CURD {

	/**
	 * @param LP_Order $order
	 *
	 * @return mixed
	 */
	public function create( &$order ) {
		$order_data = array(
			'post_author' => '1',
			'post_parent' => $order->get_parent(),
			'post_type'   => LP_ORDER_CPT,
			'post_status' => 'lp-' . apply_filters( 'learn_press_default_order_status', 'pending' ),
			'ping_status' => 'closed',
			'post_title'  => $order->get_title()
		);

		$order_data = apply_filters( 'learn-press/new-order-data', $order_data );

		$id = wp_insert_post( $order_data );

		if ( $id && ! is_wp_error( $id ) ) {
			$order->set_id( $id );
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
				SELECT order_item_id, order_item_name
				FROM {$wpdb->learnpress_order_items}
				WHERE order_id = %d 
			", $order->get_id() );

			$_items = $wpdb->get_results( $query );
			$items  = array();
			// Loop items
			if ( $_items ) {
				foreach ( $_items as $item ) {
					$items[ $item->order_item_id ]['name'] = $item->order_item_name;
					$items[ $item->order_item_id ]['id']   = $item->order_item_id;

					$this->get_item_meta( $items[ $item->order_item_id ] );
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

	public function update( &$order ) {
		// TODO: Implement update() method.
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
		$cloned = $order;
		$cloned->set_id( 0 );
		$cloned->save();

		$meta_keys = array(
			'_order_currency',
			'_prices_include_tax',
			'_user_ip_address',
			'_user_agent',
			'_user_id',
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

	public function cln_items( $from, $to ) {
		$order = learn_press_get_order( $from );
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT *
			FROM {$wpdb->learnpress_order_items}
			WHERE order_id = %d 
		", $from );
		if ( $items = $wpdb->get_results( $query ) ) {
			foreach ( $items as $item ) {
				$wpdb->insert(
					$wpdb->learnpress_order_items,
					array(
						'order_item_name' => $item->order_item_name,
						'order_id'        => $to
					)
				);
				$query = $wpdb->prepare( "
					SELECT *
					FROM {$wpdb->learnpress_order_itemmeta}
					WHERE learnpress_order_item_id = %d
				", $item->order_item_id );

				if ( $metas = $wpdb->get_results( $query ) ) {
					foreach ( $metas as $meta ) {
						$wpdb->insert(
							$wpdb->learnpress_order_itemmeta,
							array(
								'learnpress_order_item_id' => $wpdb->insert_id,
								'meta_key'                 => $meta->meta_key,
								'meta_value'               => $metas->meta_value
							)
						);
					}
				}
			}
		}
		$this->read_items( $order );
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
			$order->set_data(
				array(
					'user_id'          => get_post_meta( $order->get_id(), '_user_id', true ),
					'order_date'       => $post->post_date,
					'modified_date'    => $post->post_modified,
					'customer_message' => $post->post_excerpt,
					'customer_note'    => $post->post_excerpt,
					'post_status'      => $post->post_status,
					'user_ip'          => get_post_meta( $order->get_id(), '_user_ip_address', true )
				)
			);

			$this->read_items( $order );
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
<?php

/**
 * Class LP_Order
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Order {

	/**
	 * Store post object
	 *
	 * @var null|WP_Post object
	 */
	public $post = null;

	/**
	 * @var null|LP_Order object
	 */
	protected static $_instance = null;

	/**
	 * @param $order
	 */
	public function __construct( $order ) {
		$this->init( $order );
	}

	/**
	 * Init/load the order object. Called from the constructor.
	 *
	 * @param  int|object|LP_Order $order Order to init
	 */
	protected function init( $order ) {
		if ( is_numeric( $order ) ) {
			$this->id   = absint( $order );
			$this->post = get_post( $order );
			$this->get_order( $this->id );
		} elseif ( $order instanceof LP_Order ) {
			$this->id   = absint( $order->id );
			$this->post = $order->post;
			$this->get_order( $this->id );
		} elseif ( isset( $order->ID ) ) {
			$this->id   = absint( $order->ID );
			$this->post = $order;
			$this->get_order( $this->id );
		} else {
			$this->id   = 0;
			$this->post = new WP_Post( new stdClass() );
		}
	}

	/**
	 * Get order by it's ID
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function get_order( $id ) {
		if ( !$id ) {
			return false;
		}

		if ( $result = get_post( $id ) ) {

			$this->id               = $result->ID;
			$this->order_date       = $result->post_date;
			$this->modified_date    = $result->post_modified;
			$this->customer_message = $result->post_excerpt;
			$this->customer_note    = $result->post_excerpt;
			$this->post_status      = $result->post_status;

			return true;
		}

		return false;
	}

	/**
	 * Get current status of order
	 *
	 * @return mixed
	 */
	public function get_status() {
		$this->post->post_status = get_post_status( $this->id );
		return apply_filters( 'lp_order_get_status', 'lp-' === substr( $this->post->post_status, 0, 3 ) ? substr( $this->post->post_status, 3 ) : $this->post->post_status, $this );
	}

	/**
	 * Checks to see if current order has status as passed
	 *
	 * @param $status
	 *
	 * @return mixed
	 */
	public function has_status( $status ) {
		return apply_filters( 'lp_order_has_status', ( is_array( $status ) && in_array( $this->get_status(), $status ) ) || $this->get_status() === $status ? true : false, $this, $status );
	}

	/**
	 * Updates order to new status if needed
	 *
	 * @param mixed $new_status
	 * @param bool  $force Force to update/trigger action even the status is not changed
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function update_status( $new_status = 'pending', $force = false ) {
		global $post;
		$new_status = 'lp-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
		$old_status = $this->get_status();

		if ( ( ( $new_status !== $old_status ) || $force ) && in_array( $new_status, array_keys( learn_press_get_order_statuses( false ) ) ) ) {
			// Update the order
			global $wpdb;
			$updated = $wpdb->update( $wpdb->posts, array( 'post_status' => 'lp-' . $new_status ), array( 'ID' => $this->id ), array( '%s' ) );

			if ( $updated === false ) {
				throw new Exception( __( 'Error! Update order failed', 'learnpress' ) );
				return false;
			}
			$this->post_status       = 'lp-' . $new_status;
			$this->post->post_status = 'lp-' . $new_status;
			// update post cache
			wp_cache_set( $this->id, $this->post, 'posts' );
			// Status was changed
			do_action( 'learn_press_order_status_' . $new_status, $this->id );
			do_action( 'learn_press_order_status_' . $old_status . '_to_' . $new_status, $this->id );
			do_action( 'learn_press_order_status_changed', $this->id, $old_status, $new_status );

			switch ( $new_status ) {

				case 'completed' :

					break;

				case 'processing' :

					break;
			}
			// backward compatible
			do_action( 'learn_press_update_order_status', $new_status, $this->id );
		}
	}

	public function set_payment_method( $payment_method ) {
		if ( is_object( $payment_method ) ) {
			update_post_meta( $this->id, '_payment_method', $payment_method->id );
			update_post_meta( $this->id, '_payment_method_title', $payment_method->get_title() );
		}
		$this->payment_method = $payment_method;
	}

	/**
	 * Format order number id
	 *
	 * @return string
	 */
	public function get_order_number() {
		return apply_filters( 'learn_press_get_order_number', '#' . sprintf( "%'.010d", $this->id ), $this );
	}

	/**
	 * Get status of the order
	 *
	 * @return mixed|void
	 */
	public function get_order_status() {
		$statuses = learn_press_get_order_statuses();
		$status   = '';
		if ( !empty( $statuses[$this->post_status] ) ) {
			$status = str_replace( 'lp-', '', $this->post_status );
		} else {
			$status = $this->post_status;
		}
		return apply_filters( 'learn_press_get_order_status', $status, $this );
	}

	public function get_order_status_html() {
		$statuses = learn_press_get_order_statuses();
		$status   = '';
		if ( !empty( $statuses[$this->post_status] ) ) {
			$status = $statuses[$this->post_status];
		} elseif ( $this->post_status == 'trash' ) {
			$status = __( 'Removed', 'learnpress' );
		} else {
			$status = ucfirst( $this->post_status );
		}
		$class = 'order-status order-status-' . sanitize_title( $status );
		$html  = sprintf( '<span class="%s">%s</span>', apply_filters( 'learn_press_order_status_class', $class, $status, $this ), $status, $this );
		return apply_filters( 'learn_press_order_status_html', $html, $this );
	}

	/**
	 * Mark order as complete
	 *
	 * @param string - transaction ID provided payment gateway
	 *
	 * @return bool
	 */
	public function payment_complete( $transaction_id = '' ) {
		do_action( 'learn_press_pre_payment_complete', $this->id );

		LP()->session->order_awaiting_payment = null;

		$valid_order_statuses = apply_filters( 'learn_press_valid_order_statuses_for_payment_complete', array( 'pending', 'processing', 'on-hold' ), $this );

		if ( $this->id && $this->has_status( $valid_order_statuses ) ) {

			$this->update_status( 'completed' );

			if ( !empty( $transaction_id ) ) {
				add_post_meta( $this->id, '_transaction_id', $transaction_id, true );
			}

			do_action( 'learn_press_payment_complete', $this->id );
		} else {
			do_action( 'learn_press_payment_complete_order_status_' . $this->get_status(), $this->id );
		}

		return true;
	}

	/**
	 * Get checkout order success url
	 *
	 * @return mixed
	 */
	public function get_checkout_order_received_url() {
		$received_url = learn_press_get_endpoint_url( 'lp-order-received', $this->id, learn_press_get_page_link( 'checkout' ) );

		$received_url = add_query_arg( 'key', $this->order_key, $received_url );

		return apply_filters( 'learn_press_get_checkout_order_received_url', $received_url, $this );
	}

	/*********************************/

	/**
	 * Get customer name of the order
	 */
	public function get_customer_name() {
		$user_id       = $this->user_id;
		$user          = learn_press_get_user( $user_id );
		$customer_name = '';
		if ( !$user->is_exists() ) {
			$customer_name = apply_filters( 'learn_press_order_customer_name', __( '[Guest]', 'learnpress' ) );
		} else {
			if ( $user->user->data->display_name ) {
				$customer_name = $user->user->data->display_name;
			} elseif ( $user->user->data->user_nicename ) {
				$customer_name = $user->user->data->user_nicename;
			} elseif ( $user->user->data->user_login ) {
				$customer_name = $user->user->data->user_login;
			}
		}
		return $customer_name;
	}

	public function customer_exists() {
		$user_id = $this->user_id;
		return false !== get_userdata( $user_id );
	}

	/**
	 * Get items of the order
	 *
	 * @return mixed|void
	 */
	public function get_items() {
		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT order_item_id, order_item_name
			FROM {$wpdb->learnpress_order_items}
			WHERE order_id = %d ", $this->id );

		$_items = $wpdb->get_results( $query );
		$items  = array();
		// Loop items
		if ( $_items ) foreach ( $_items as $item ) {
			$items[$item->order_item_id]['name'] = $item->order_item_name;
			$items[$item->order_item_id]['id']   = $item->order_item_id;

			$this->get_item_meta( $items[$item->order_item_id] );
			//$items[$item->order_item_id]['item_meta_array'] = $this->get_item_meta_array( $item->order_item_id );
			//$items[$item->order_item_id]                    = $this->expand_item_meta( $items[$item->order_item_id] );
		}

		return apply_filters( 'learn_press_order_get_items', $items, $this );
	}

	public function get_item_meta( &$item ) {
		if ( $metas = get_metadata( 'learnpress_order_item', $item['id'] ) ) {
			foreach ( $metas as $k => $v ) {
				$item[preg_replace( '!^_!', '', $k )] = maybe_unserialize( $v[0] );
			}
		};
	}

	/**
	 * Remove all items from an order
	 */
	public function remove_order_items() {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare( "
				DELETE FROM itemmeta
				USING {$wpdb->learnpress_order_itemmeta} itemmeta
				INNER JOIN {$wpdb->learnpress_order_items} items
				WHERE itemmeta.learnpress_order_item_id = items.order_item_id
				AND items.order_id = %d",
				$this->id
			)
		);
		$wpdb->query(
			$wpdb->prepare( "
				DELETE FROM {$wpdb->learnpress_order_items}
				WHERE order_id = %d",
				$this->id
			)
		);
		$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->learnpress_order_itemmeta} AUTO_INCREMENT = %d", 1 ) );
		$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->learnpress_order_items} AUTO_INCREMENT = %d", 1 ) );
	}

	/**
	 * Add a new item to order
	 *
	 * @param       $item
	 * @param int   $quantity
	 * @param array $meta
	 *
	 * @return bool
	 */
	public function add_item( $item, $quantity = 1, $meta = array() ) {

		$item_id = learn_press_add_order_item( $this->id, $item );
		if ( !$item_id ) {
			return false;
		}
		learn_press_add_order_item_meta( $item_id, '_course_id', $item['item_id'] );
		learn_press_add_order_item_meta( $item_id, '_quantity', $item['quantity'] );
		learn_press_add_order_item_meta( $item_id, '_subtotal', $item['subtotal'] );
		learn_press_add_order_item_meta( $item_id, '_total', $item['total'] );
		learn_press_add_order_item_meta( $item_id, '_data', $item['data'] );

		return $item_id;
	}

	public function get_user( $field = '' ) {
		$user = learn_press_get_user( $this->user_id );
		if ( $field ) {
			return $user->{$field};
		}
		return $user;
	}

	public function get_users() {
		$users = false;
		if ( $this->is_multi_users() ) {
			$users = get_post_meta( $this->id, '_user_id' );
		}
		return $users;
	}

	public function dropdown_users() {
		$order_users = $this->get_users();
		$users       = get_users( array() );
		echo '<select name="order-customer[]" id="order-customer" multiple="multiple">';
		foreach ( (array) $users as $user ) {
			$user->ID = (int) $user->ID;
			if ( in_array( $user->ID, $order_users ) ) {
				$found_selected = true;
			} else {
				$found_selected = false;
			}
			echo sprintf( '<option value="%d"%s>%s</option>', $user->ID, selected( $found_selected, true, false ), $user->user_login );
		}
		echo '</select>';
	}

	public function __get( $key ) {
		$value = null;
		if ( !property_exists( $this, $key ) ) {
			$value = get_post_meta( $this->id, '_' . $key, true );
		}
		return $value;
	}

	public function get_checkout_payment_url() {

	}

	public function get_formatted_order_subtotal() {
		$currency_symbol = learn_press_get_currency_symbol( $this->order_currency );
		return learn_press_format_price( $this->order_subtotal, $currency_symbol );
	}

	public function get_formatted_order_total() {
		$currency_symbol = learn_press_get_currency_symbol( $this->order_currency );
		return learn_press_format_price( $this->order_total, $currency_symbol );
	}

	public function get_payment_method_title() {
		if ( $this->order_total == 0 ) {
			$title = __( 'Free Payment', 'learnpress' );
		} else {
			$title = $this->payment_method_title;
		}
		return apply_filters( 'learn_press_display_payment_method_title', $title, $this->payment_method );
	}

	public function get_view_order_url() {
		global $wp_query;
		$view_order_url = learn_press_get_endpoint_url( 'view-order', $this->id, learn_press_get_page_link( 'profile' ) );
		//
		$user                = learn_press_get_current_user();
		$view_order_endpoint = LP()->settings->get( 'profile_endpoints.profile-order-details' );
		if ( !$view_order_endpoint ) {
			$view_order_endpoint = 'order-details';
		}

		$view_order_endpoint = urlencode( $view_order_endpoint );
		if ( get_option( 'permalink_structure' ) ) {
			$view_order_url = learn_press_get_page_link( 'profile' ) . $user->user_login . '/' . $view_order_endpoint . '/' . $this->id . '/';
		} else {
			$args         = array(
				'user' => $user->user_login
			);
			$args['view'] = $view_order_endpoint;
			if ( $view_order_endpoint ) {
				$args['id'] = $this->id;
			}
			$view_order_url = add_query_arg(
				$args,
				learn_press_get_page_link( 'profile' )
			);
		}

		return apply_filters( 'learn_press_view_order_url', $view_order_url, $this );
	}

	public function get_cancel_order_url( $force = false ) {
		$user = learn_press_get_current_user();
		$url  = learn_press_user_profile_link( $user->id, LP()->settings->get( 'profile_endpoints.profile-orders' ) );
		if ( !$force ) {
			$url = add_query_arg( 'cancel-order', $this->id, $url );
		} else {
			$url = add_query_arg( 'cancelled-order', $this->id, $url );
		}
		return apply_filters( 'learn_press_cancel_order_url', wp_nonce_url( $url, 'cancel-order', 'lp-nonce' ) );
	}

	public function add_note( $note = null ) {
		if ( is_user_logged_in() ) {
			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
			$comment_post_ID      = $this->id;
			$comment_author_url   = '';
			$comment_content      = $note;
			$comment_agent        = 'LearnPress';
			$comment_type         = 'lp_order_note';
			$comment_parent       = 0;
			$comment_approved     = 1;

			$commentdata = apply_filters(
				'learn_press_new_order_note_data',
				compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ),
				$this->id
			);

			$comment_id = wp_insert_comment( $commentdata );
			return $comment_id;
		}
		return false;
	}

	public function get_user_name() {
		return apply_filters( 'learn_press_order_user_name', sprintf( _x( '%1$s', 'full name', 'learnpress' ), $this->get_user( 'user_login' ) ) );
	}

	/**
	 * Check to see if this order is for multi users
	 *
	 * @since 2.1.5
	 *
	 * @return bool
	 */
	public function is_multi_users() {
		$multiple = $this->get_status() == 'auto-draft' && 'yes' == learn_press_get_request( 'multi-users' );
		$multiple = $multiple || ( 'yes' == get_post_meta( $this->id, '_lp_multi_users', true ) );
		return $multiple;
	}

	/**
	 * Print the list of all users has assigned to this order
	 * in case this order is for multi users
	 *
	 * @since 2.1.5
	 */
	public function print_users() {
		if ( $user_ids = get_post_meta( $this->id, '_user_id' ) ) {
			global $wpdb;
			$format_ids = array_fill( 0, sizeof( $user_ids ), '%d' );
			$users      = $wpdb->get_results( $wpdb->prepare( "SELECT user_login, user_email FROM {$wpdb->users} WHERE ID IN(" . join( ',', $format_ids ) . ")", $user_ids ) );
			$size       = sizeof( $users );
			foreach ( $users as $i => $user ) {
				printf( '<strong>%s</strong> ( %s )', $user->user_login, $user->user_email );
				if ( $i < $size - 1 ) {
					echo ', ';
				}
			}
		} else {
			_e( 'No user assigned', 'learnpress' );
		}
	}

	/**
	 * Get email of user has bought this order.
	 * In case this order is for multi users return an array with multi email addresses.
	 *
	 * @since 2.1.5
	 *
	 * @return mixed|array
	 */
	public function get_user_data() {
		$data = false;
		if ( $user_ids = get_post_meta( $this->id, '_user_id' ) ) {
			global $wpdb;
			$format = array_fill( 0, sizeof( $user_ids ), '%d' );
			$sql    = "
				SELECT ID, user_email as email, display_name as name
				FROM {$wpdb->users} u
				WHERE ID IN(" . join( ', ', $format ) . ")
			";
			$data   = $wpdb->get_results( $wpdb->prepare( $sql, $user_ids ), OBJECT_K );
		}
		return $data;
	}

	public function get_user_email() {
		$email = false;
		if ( $user = learn_press_get_user( $this->user_id ) ) {
			$email = $user->user_email;
		}
		return $email;
	}

	/**
	 * Get an instance of LP_Order by post ID or WP_Post object
	 *
	 * @param $order
	 * @param $force
	 *
	 * @return LP_Order
	 */
	public static function instance( $order, $force = true ) {
		$post = $order;
		if ( $order instanceof WP_Post ) {
			$id = $order->ID;
		} elseif ( is_object( $order ) && isset( $order->ID ) ) {
			$id = $order->ID;
		} else {
			$id = $order;
		}
		if ( empty( self::$_instance[$id] ) || $force ) {
			self::$_instance[$id] = new self( $post );
		}
		return self::$_instance[$id];
	}
}
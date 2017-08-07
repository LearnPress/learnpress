<?php

/**
 * Class LP_Order
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_Order extends LP_Abstract_Post_Data {

	/**
	 * @var array
	 */
	protected $_data = array(
		'order_date'       => '',
		'modified_date'    => '',
		'customer_message' => '',
		'customer_note'    => '',
		'status'           => ''
	);

	/**
	 * @param mixed $order_id
	 */
	public function __construct( $order_id = false ) {
		if ( is_numeric( $order_id ) && $order_id > 0 ) {
			$this->set_id( $order_id );
		} elseif ( $order_id instanceof self ) {
			$this->set_id( absint( $order_id->get_id() ) );
		} elseif ( ! empty( $order_id->ID ) ) {
			$this->set_id( absint( $order_id->ID ) );
		}

		//parent::__construct($order_id);

		if ( $this->get_id() > 0 ) {
			$this->load();
		}
	}



	/**
	 * Load the order data.
	 * Check if the id is not zero but it's post type does not exists.
	 *
	 * @throws Exception
	 */
	public function load() {
		$the_id = $this->get_id();
		if ( ! $the_id || LP_ORDER_CPT !== get_post_type( $the_id ) ) {
			if ( learn_press_is_debug() ) {
				throw new Exception( sprintf( __( 'Invalid order with ID "%d".', 'learnpress' ), $the_id ) );
			}
		}
		if ( $post = get_post( $the_id ) ) {
			$this->set_data(
				array(
					'order_date'       => $post->post_date,
					'modified_date'    => $post->post_modified,
					'customer_message' => $post->post_excerpt,
					'customer_note'    => $post->post_excerpt,
					'post_status'      => $post->post_status
				)
			);
		}
	}

	/**
	 * Get confirm received text
	 *
	 * @since 3.x.x
	 *
	 * @return string
	 */
	public function get_confirm_order_received_text() {
		$text = apply_filters( 'learn-press/confirm-order-received-text', __( 'Thank you. Your order has been received.', 'learnpress' ), $this->get_id() );

		// deprecated
		$text = apply_filters( 'learn_press_confirm_order_received_text', $text, $this->get_id() );

		return $text;
	}

	/**
	 * Get thank you message after the order is placed.
	 *
	 * @since 3.x.x
	 *
	 * @return mixed
	 */
	public function get_thankyou_message() {
		/**
		 * @since 3.x.x
		 */
		$message = apply_filters( 'learn-press/', __( 'Thank you. Your order has been received.', 'learnpress' ), $this->get_id() );

		// @deprecated
		$message = apply_filters( 'learn_press_confirm_order_received_text', $message, $this->get_id() );

		return $message;
	}

	/**
	 * Magic function for getting object property dynamic.
	 *
	 * @param string $prop
	 *
	 * @return int|mixed|null
	 * @deprecated
	 */
	public function __get( $prop ) {
		if ( $prop == 'post' ) {
			//print_r( debug_backtrace() );
			//die( '$post is deprecated' );
		} elseif ( $prop == 'id' ) {
			return $this->get_id();
		}
		$value = null;
		if ( ! property_exists( $this, $prop ) ) {
			$value = get_post_meta( $this->get_id(), '_' . $prop, true );
		}

		return $value;
	}

	/**
	 * Get current status of order
	 *
	 * @return mixed
	 */
	public function get_status() {
		$the_id      = $this->get_id();
		$post_status = get_post_status( $the_id );
		$status      = preg_replace( '~^lp-~', '', $post_status );

		// Deprecated filter
		$status = apply_filters( 'learn_press_order_status', $status, $this );

		return apply_filters( 'learn-press/order-status', $status, $the_id );
	}

	/**
	 * Checks to see if current order has status as passed.
	 *
	 * @param string|array $status String or an array of statuses
	 *
	 * @return mixed
	 */
	public function has_status( $status ) {
		settype( $status, 'array' );
		$has = in_array( $this->get_status(), $status );

		return apply_filters( 'learn-press/has-order-status', $has, $status, $this->get_id() );
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
		$new_status = preg_replace( '~^lp-~', '', $new_status );
		$old_status = $this->get_status();
		$the_id     = $this->get_id();
		if ( ( ( $new_status !== $old_status ) || $force ) && in_array( $new_status, array_keys( learn_press_get_order_statuses( false ) ) ) ) {
			// Update post's status using wpdb to preventing loop
			global $wpdb;
			$updated = $wpdb->update( $wpdb->posts, array( 'post_status' => 'lp-' . $new_status ), array( 'ID' => $this->get_id() ), array( '%s' ) );
			if ( $updated === false ) {
				if ( learn_press_is_debug() ) {
					throw new Exception( __( 'Error! Update order status failed.', 'learnpress' ) );
				}

				return false;
			}

			// Update post cache
			$the_post              = get_post( $the_id );
			$the_post->post_status = 'lp-' . $new_status;
			wp_cache_set( $this->get_id(), $this->post, 'posts' );

			// Update order data
			$this->set_data( 'status', 'lp-' . $new_status );

			// Trigger actions after status was changed
			do_action( 'learn_press_order_status_' . $new_status, $the_id );
			do_action( 'learn_press_order_status_' . $old_status . '_to_' . $new_status, $the_id );
			do_action( 'learn_press_order_status_changed', $the_id, $old_status, $new_status );
			// backward compatible
			do_action( 'learn_press_update_order_status', $new_status, $the_id );

			// Trigger actions after status was changed
			do_action( 'learn-press/order-status-' . $new_status, $the_id );
			do_action( 'learn-press/order-status-' . $old_status . '-to-' . $new_status, $the_id );
			do_action( 'learn-press/order-status-changed', $the_id, $old_status, $new_status );
			// backward compatible
			do_action( 'learn_press/update-order-status', $new_status, $the_id );

			return true;
		}

		return false;
	}

	/**
	 * Set payment method for this order.
	 * If payment method is an instance of LP_Gateway_Abstract then
	 * update it to database.
	 *
	 * @param LP_Gateway_Abstract $payment_method
	 */
	public function set_payment_method( $payment_method ) {
		if ( is_object( $payment_method ) ) {
			update_post_meta( $this->get_id(), '_payment_method', $payment_method->get_id() );
			update_post_meta( $this->get_id(), '_payment_method_title', $payment_method->get_title() );
		}
		$this->payment_method = $payment_method;
	}

	/**
	 * Format order number id
	 *
	 * @return string
	 */
	public function get_order_number() {
		$order_number = apply_filters( 'learn_press_get_order_number', '#' . sprintf( "%'.010d", $this->get_id() ), $this );

		return apply_filters( 'learn-press/order-number', $order_number, $this->get_id() );
	}

	/**
	 * Get status of the order
	 *
	 * @return mixed
	 */
	public function get_order_status() {
		$statuses    = learn_press_get_order_statuses();
		$the_id      = $this->get_id();
		$post_status = get_post_status( $the_id );
		if ( ! empty( $statuses[ $post_status ] ) ) {
			$status = str_replace( 'lp-', '', $post_status );
		}

		return apply_filters( 'learn_press_get_order_status', $status, $this );
	}

	public function get_order_status_html() {
		$statuses = learn_press_get_order_statuses();
		$status   = '';
		if ( ! empty( $statuses[ $this->post_status ] ) ) {
			$status = $statuses[ $this->post_status ];
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
		do_action( 'learn_press_pre_payment_complete', $this->get_id() );

		LP()->session->order_awaiting_payment = null;

		$valid_order_statuses = apply_filters( 'learn_press_valid_order_statuses_for_payment_complete', array(
			'pending',
			'processing',
			'on-hold'
		), $this );

		if ( $this->get_id() && $this->has_status( $valid_order_statuses ) ) {

			$this->update_status( 'completed' );

			if ( ! empty( $transaction_id ) ) {
				add_post_meta( $this->get_id(), '_transaction_id', $transaction_id, true );
			}

			do_action( 'learn_press_payment_complete', $this->get_id() );
		} else {
			do_action( 'learn_press_payment_complete_order_status_' . $this->get_status(), $this->get_id() );
		}

		return true;
	}

	/**
	 * Get checkout order success url
	 *
	 * @return mixed
	 */
	public function get_checkout_order_received_url() {
		$received_url = learn_press_get_endpoint_url( 'lp-order-received', $this->get_id(), learn_press_get_page_link( 'checkout' ) );

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
		if ( ! $user->is_exists() ) {
			$customer_name = apply_filters( 'learn_press_order_customer_name', __( '[Guest]', 'learnpress' ) );
		} else {
			if ( $user->get_data( 'display_name' ) ) {
				$customer_name = $user->get_data( 'display_name' );
			} elseif ( $user->get_data( 'user_nicename' ) ) {
				$customer_name = $user->get_data( 'user_nicename' );
			} elseif ( $user->get_data( 'user_login' ) ) {
				$customer_name = $user->get_data( 'user_login' );
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
			WHERE order_id = %d ", $this->get_id() );

		$_items = $wpdb->get_results( $query );
		$items  = array();
		// Loop items
		if ( $_items ) {
			foreach ( $_items as $item ) {
				$items[ $item->order_item_id ]['name'] = $item->order_item_name;
				$items[ $item->order_item_id ]['id']   = $item->order_item_id;

				$this->get_item_meta( $items[ $item->order_item_id ] );
				//$items[$item->order_item_id]['item_meta_array'] = $this->get_item_meta_array( $item->order_item_id );
				//$items[$item->order_item_id]                    = $this->expand_item_meta( $items[$item->order_item_id] );
			}
		}

		return apply_filters( 'learn_press_order_get_items', $items, $this );
	}

	public function get_item_meta( &$item ) {
		if ( $metas = get_metadata( 'learnpress_order_item', $item['id'] ) ) {
			foreach ( $metas as $k => $v ) {
				$item[ preg_replace( '!^_!', '', $k ) ] = maybe_unserialize( $v[0] );
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
				$this->get_id()
			)
		);
		$wpdb->query(
			$wpdb->prepare( "
				DELETE FROM {$wpdb->learnpress_order_items}
				WHERE order_id = %d",
				$this->get_id()
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

		$item_id = learn_press_add_order_item( $this->get_id(), $item );
		if ( ! $item_id ) {
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
			$users = get_post_meta( $this->get_id(), '_user_id' );
		}

		return $users;
	}

	public function dropdown_users() {
		$order_users = $this->get_users();
		$users       = get_users( array() );
		echo '<select name="order-customer[]" id="order-customer" multiple="multiple">';
		foreach ( (array) $users as $user ) {
			//$user->get_id() = (int) $user->get_id();
			if ( in_array( $user->get_id(), $order_users ) ) {
				$found_selected = true;
			} else {
				$found_selected = false;
			}
			echo sprintf( '<option value="%d"%s>%s</option>', $user->get_id(), selected( $found_selected, true, false ), $user->user_login );
		}
		echo '</select>';
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
		$view_order_url = learn_press_get_endpoint_url( 'view-order', $this->get_id(), learn_press_get_page_link( 'profile' ) );
		//
		$user                = learn_press_get_current_user();
		$view_order_endpoint = LP()->settings->get( 'profile_endpoints.profile-order-details' );
		if ( ! $view_order_endpoint ) {
			$view_order_endpoint = 'order-details';
		}

		$view_order_endpoint = urlencode( $view_order_endpoint );
		if ( get_option( 'permalink_structure' ) ) {
			$view_order_url = learn_press_get_page_link( 'profile' ) . $user->user_login . '/' . $view_order_endpoint . '/' . $this->get_id() . '/';
		} else {
			$args         = array(
				'user' => $user->user_login
			);
			$args['view'] = $view_order_endpoint;
			if ( $view_order_endpoint ) {
				$args['id'] = $this->get_id();
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
		$url  = learn_press_user_profile_link( $user->get_id(), LP()->settings->get( 'profile_endpoints.profile-orders' ) );
		if ( ! $force ) {
			$url = add_query_arg( 'cancel-order', $this->get_id(), $url );
		} else {
			$url = add_query_arg( 'cancelled-order', $this->get_id(), $url );
		}

		return apply_filters( 'learn_press_cancel_order_url', wp_nonce_url( $url, 'cancel-order', 'lp-nonce' ) );
	}

	public function add_note( $note = null ) {
		if ( is_user_logged_in() ) {
			$user                 = get_user_by( 'id', get_current_user_id() );
			$comment_author       = $user->display_name;
			$comment_author_email = $user->user_email;
			$comment_post_ID      = $this->get_id();
			$comment_author_url   = '';
			$comment_content      = $note;
			$comment_agent        = 'LearnPress';
			$comment_type         = 'lp_order_note';
			$comment_parent       = 0;
			$comment_approved     = 1;

			$commentdata = apply_filters(
				'learn_press_new_order_note_data',
				compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ),
				$this->get_id()
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
		$multiple = $multiple || ( 'yes' == get_post_meta( $this->get_id(), '_lp_multi_users', true ) );

		return $multiple;
	}

	/**
	 * Print the list of all users has assigned to this order
	 * in case this order is for multi users
	 *
	 * @since 2.1.5
	 */
	public function print_users() {
		if ( $user_ids = get_post_meta( $this->get_id(), '_user_id' ) ) {
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
		if ( $user_ids = get_post_meta( $this->get_id(), '_user_id' ) ) {
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
		learn_press_deprecated_function( 'new LP_Order', '3.0', 'learn_press_get_order' );

		return learn_press_get_order( $order );
	}
}
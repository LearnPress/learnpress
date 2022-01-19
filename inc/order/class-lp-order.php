<?php
/**
 * Class LP_Order
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Order' ) ) {

	/**
	 * Class LP_Order
	 */
	class LP_Order extends LP_Abstract_Post_Data {

		/**
		 * @var string
		 */
		protected $_post_type = LP_ORDER_CPT;

		/**
		 * @var array
		 */
		protected $_data = array(
			'user_id'          => '',
			'order_date'       => '',
			'date_modified'    => '',
			'customer_message' => '',
			'customer_note'    => '',
			'status'           => '',
			'order_key'        => '',
			'total'            => 0,
			'subtotal'         => 0,
			'created_via'      => '',
			'checkout_email'   => '',
		);

		/**
		 * @var array
		 */
		protected $_meta_keys = array(
			'_user_id'              => 'user',
			'_order_currency'       => 'currency',
			'_order_subtotal'       => 'subtotal',
			'_order_total'          => 'total',
			'_payment_method'       => 'payment_method',
			'_payment_method_title' => 'payment_method_title',
			'_order_version'        => 'order_version',
			'_edit_last'            => '',
			'_edit_lock'            => '',
			'_prices_include_tax'   => '',
			'_order_key'            => '',
			'_user_ip'              => '',
			'_checkout_email'       => '',
		);

		/**
		 * Store order status in transactions.
		 *
		 * @var array
		 */
		protected $_status = array();

		/**
		 * LP_Order constructor.
		 *
		 * @param bool $order_id
		 *
		 * @throws Exception
		 */
		public function __construct( $order_id = false ) {
			$this->_curd = new LP_Order_CURD();
			if ( is_numeric( $order_id ) && $order_id > 0 ) {
				$this->set_id( $order_id );
			} elseif ( $order_id instanceof self ) {
				$this->set_id( absint( $order_id->get_id() ) );
			} elseif ( ! empty( $order_id->ID ) ) {
				$this->set_id( absint( $order_id->ID ) );
			}

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
			$this->_curd->load( $this );
		}

		/**
		 * Set order date.
		 *
		 * @param int|string $date
		 */
		public function set_order_date( $date ) {
			if ( is_numeric( $date ) ) {
				$date = date( 'Y-m-d H:i:s', $date );
			}
			$this->_set_data_date( 'order_date', $date );

		}

		/**
		 * Get date of this order.
		 *
		 * @param string $context
		 *
		 * @return string|LP_Datetime
		 */
		public function get_order_date( $context = '' ) {
			$date = $this->get_data( 'order_date' );

			if ( 'edit' !== $context ) {

				if ( $date instanceof LP_Datetime ) {
					$strtime = strtotime( $date->toSql() );

					switch ( $context ) {
						case 'd':
							$date = date_i18n( 'Y-m-d', $strtime );
							break;
						case 'h':
							$date = date_i18n( 'H', $strtime );
							break;
						case 'm':
							$date = date_i18n( 'i', $strtime );
							break;
						case 'timestamp':
							$date = $strtime;
							break;
						default:
							$post      = get_post( $this->get_id() );
							$m_time    = $post->post_date;
							$time      = get_post_time( 'G', true, $post );
							$time_diff = time() - $time;

							if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
								$date = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time ) );
							} else {
								$date = mysql2date( get_option( 'date_format' ), $m_time );
							}
					}
				}
			} elseif ( ! $date instanceof LP_Datetime ) {
				$date = new LP_Datetime( $date );
			}

			return $date;
		}

		/**
		 * Set order key
		 *
		 * @param string $order_key
		 */
		public function set_order_key( $order_key ) {
			$this->_set_data( 'order_key', $order_key );
		}

		/**
		 * Get order key.
		 *
		 * @return array|mixed
		 */
		public function get_order_key() {
			return $this->get_data( 'order_key', '' );
		}

		/**
		 * Get confirm received text
		 *
		 * @return string
		 * @since 3.0.0
		 */
		public function get_confirm_order_received_text() {
			return apply_filters( 'learn-press/confirm-order-received-text', __( 'Thank you. Your order has been received.', 'learnpress' ), $this->get_id() );
		}

		/**
		 * Get thank you message after the order is placed.
		 *
		 * @return mixed
		 * @since 3.0.0
		 */
		public function get_thankyou_message() {
			return apply_filters( 'learn-press/confirm-order-received-text', __( 'Thank you. Your order has been received.', 'learnpress' ), $this->get_id() );
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
				// print_r( debug_backtrace() );
				// die( '$post is deprecated' );
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
		 * Check if order has a status as wp default.
		 *
		 * @return bool
		 */
		public function has_invalid_status() {
			return ! $this->has_status( learn_press_get_order_statuses( false, true ) );
		}

		/**
		 * Updates order to new status if needed
		 *
		 * @param mixed $new_status
		 * @param bool  $manual Is this a manual order status change?.
		 *
		 * @return bool
		 * @throws Exception
		 */
		public function update_status( $new_status = 'pending', $manual = false ) {
			$old_status = $this->get_status();

			do_action( 'learn-press/before-update-status-lp-order', $new_status, $old_status, $this, $manual );

			$this->set_status( $new_status );
			$result = $this->save();

			do_action( 'learn-press/after-update-status-lp-order', $new_status, $old_status, $this, $manual );

			return $result;
		}

		/**
		 * Set payment method for this order.
		 * If payment method is an instance of LP_Gateway_Abstract then
		 * update it to database.
		 *
		 * @param LP_Gateway_Abstract|string $payment_method
		 */
		public function set_payment_method( $payment_method ) {
			if ( $payment_method instanceof LP_Gateway_Abstract ) {
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
		public function get_order_number(): string {
			return learn_press_transaction_order_number( $this->get_id() );
		}

		/**
		 * Get status of the order
		 *
		 * @return mixed
		 */
		public function get_order_status() {
			return $this->get_status();
		}

		public function get_user_ip_address() {
			return $this->get_data( 'user_ip_address' );
		}

		/**
		 * Get current status of order
		 *
		 * @return mixed
		 */
		public function get_status() {
			$status = $this->get_data( 'status', '' );
			$status = apply_filters( 'learn_press_order_status', $status, $this );

			apply_filters( 'learn-press/order/status', $status, $this->get_id() );

			return $status;
		}

		/**
		 * Set order status.
		 *
		 * @param string $new_status
		 * @param string $note - Optional. Note for changing status.
		 */
		public function set_status( string $new_status, string $note = '' ) {
			$new_status     = 'lp-' === substr( $new_status, 0, 3 ) ? substr( $new_status, 3 ) : $new_status;
			$valid_statuses = learn_press_get_order_statuses( false, true );

			if ( ! in_array( $new_status, $valid_statuses ) && 'trash' !== $new_status ) {
				$new_status = 'pending';
			}

			$this->_set_data( 'status', $new_status );
		}

		public function get_order_status_html() {
			$statuses     = learn_press_get_order_statuses();
			$order_status = $this->get_status();

			if ( ! empty( $statuses[ $order_status ] ) ) {
				$status = $statuses[ $order_status ];
			} elseif ( ! empty( $statuses[ 'lp-' . $order_status ] ) ) {
				$status = $statuses[ 'lp-' . $order_status ];
			} elseif ( $order_status == 'trash' ) {
				$status = __( 'Removed', 'learnpress' );
			} else {
				$status = ucfirst( $order_status );
			}

			$class = 'order-status order-status-' . sanitize_title( $status );
			$html  = sprintf( '<span class="%s">%s</span>', apply_filters( 'learn_press_order_status_class', $class, $status, $this ), $status );

			return apply_filters( 'learn_press_order_status_html', $html, $this );
		}

		/**
		 * Mark order as complete
		 *
		 * @param string - transaction ID provided payment gateway
		 *
		 * @return bool
		 * @throws Exception
		 */
		public function payment_complete( $transaction_id = '' ): bool {
			do_action( 'learn-press/payment-pre-complete', $this->get_id() );

			//TODO: tungnx - check to change code below - use LP()->session->set()
			LP()->session->order_awaiting_payment = null;

			$valid_order_statuses = apply_filters(
				'learn-press/valid-order-statuses-for-payment-complete',
				array(
					'pending',
					'processing',
				),
				$this
			);

			if ( $this->get_id() && $this->has_status( $valid_order_statuses ) ) {

				$this->update_status( 'completed' );

				if ( ! empty( $transaction_id ) ) {
					add_post_meta( $this->get_id(), '_transaction_id', $transaction_id, true );
				}

				do_action( 'learn-press/payment-complete', $this->get_id() );
			} else {
				do_action( 'learn-press/payment-complete-order-status-' . $this->get_status(), $this->get_id() );
			}

			return true;
		}

		/**
		 * Get checkout order successful url.
		 *
		 * @return string
		 */
		public function get_checkout_order_received_url() {
			$received_url = learn_press_get_endpoint_url( 'lp-order-received', $this->get_id(), learn_press_get_page_link( 'checkout' ) );

			$received_url = add_query_arg( 'key', $this->get_order_key(), $received_url );

			$received_url = apply_filters( 'learn_press_get_checkout_order_received_url', $received_url, $this );

			/**
			 * @since 3.0.0
			 */
			return apply_filters( 'learn-press/checkout-order-received-url', $received_url, $this );
		}

		/*********************************/

		/**
		 * Get customer name of the order
		 */
		public function get_customer_name() {
			$customer_name = '';

			if ( 'auto-draft' !== get_post_status( $this->get_id() ) ) {
				$user_ids = $this->get_users();
				if ( ! empty( $user_ids ) ) {
					$customer_name = array();
					foreach ( $user_ids as $uid ) {
						$customer = learn_press_get_user( $uid );
						if ( $customer && $customer->is_exists() ) {
							if ( $customer->get_data( 'display_name' ) ) {
								$customer_name[] = $customer->get_data( 'display_name' );
							} elseif ( $customer->get_data( 'user_nicename' ) ) {
								$customer_name[] = $customer->get_data( 'user_nicename' );
							} elseif ( $customer->get_data( 'user_login' ) ) {
								$customer_name[] = $customer->get_data( 'user_login' );
							}
						} else {
							$customer_name[] = $this->get_guest_customer_name();
						}
					}

					$customer_name = join( ', ', $customer_name );
				}
			}

			if ( ! $customer_name ) {
				$customer_name = $this->get_guest_customer_name();
			}

			return $customer_name;
		}

		public function get_guest_customer_name() {
			$checkout_email = $this->get_checkout_email();
			if ( $checkout_email ) {
				$customer_name = sprintf( __( '%s (Guest)', 'learnpress' ), $checkout_email );
			} else {
				$customer_name = sprintf( __( '(Guest)', 'learnpress' ), $checkout_email );
			}

			return apply_filters( 'learn-press/order/guest-customer-name', $customer_name );
		}

		/*public function customer_exists() {
			return false !== get_userdata( $this->get_data( 'user_id' ) );
		}*/

		public $order_items_loaded = false;

		/**
		 * Get items of the order
		 *
		 * @return mixed
		 */
		public function get_items() {
			if ( $this->order_items_loaded ) {
				return $this->order_items_loaded;
			}

			$items = $this->_curd->read_items( $this );

			$this->order_items_loaded = $items;

			return apply_filters( 'learn-press/order-items', $items );
		}

		/**
		 * Get items of the order by filter
		 *
		 * @param array $filter
		 *
		 * @return mixed
		 * @author tungnx
		 */
		public function get_items_filter( $filter = array() ) {
			$items = $this->_curd->read_items_filter( $this, $filter );

			return apply_filters( 'learn-press/order-items', $items );
		}

		public function is_child() {
			return $this->get_parent_id();
		}

		public function get_parent() {
			return $this->get_parent_id() ? learn_press_get_order( $this->get_parent_id() ) : false;
		}

		/**
		 * Get list of course ids from order.
		 *
		 * @return array|bool
		 * @editor tungnx
		 */
		public function get_item_ids() {
			$items = $this->get_items();

			if ( $items ) {
				$course_ids = array();
				foreach ( $items as $item ) {
					if ( isset( $item['course_id'] ) ) {
						$course_ids[] = $item['course_id'];
					}
				}

				return $course_ids;
				/**
				 * Comment by tungnx. Because it will error if item didn't have key 'course_id'.
				 * Ex: case buy certificate, will not have that key
				 */
				//return @wp_list_pluck( $items, 'course_id' );
			}

			return false;
		}

		/**
		 * Check is order of Guest (user not login)
		 *
		 * @return bool
		 */
		public function is_guest(): bool {
			return ! get_user_by( 'ID', $this->get_user_id() );
		}

		public function get_item_meta( &$item ) {
			$metas = get_metadata( 'learnpress_order_item', $item['id'] );
			if ( $metas ) {
				foreach ( $metas as $k => $v ) {
					$item[ preg_replace( '!^_!', '', $k ) ] = LP_Helper::maybe_unserialize( $v[0] );
				}
			}
		}

		/**
		 * Remove all items from an order
		 */
		public function remove_order_items() {
			global $wpdb;
			$wpdb->query(
				$wpdb->prepare(
					"
				DELETE FROM itemmeta
				USING $wpdb->learnpress_order_itemmeta itemmeta
				INNER JOIN $wpdb->learnpress_order_items items
				WHERE itemmeta.learnpress_order_item_id = items.order_item_id
				AND items.order_id = %d",
					$this->get_id()
				)
			);
			$wpdb->query(
				$wpdb->prepare(
					"
				DELETE FROM {$wpdb->learnpress_order_items}
				WHERE order_id = %d",
					$this->get_id()
				)
			);
			$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->learnpress_order_itemmeta} AUTO_INCREMENT = %d", 1 ) );
			$wpdb->query( $wpdb->prepare( "ALTER TABLE {$wpdb->learnpress_order_items} AUTO_INCREMENT = %d", 1 ) );
		}

		/**
		 * Add a new item to order.
		 *
		 * @param array|int $item
		 *
		 * @return int
		 * @throws Exception
		 * @editor tungnx
		 * @modify 4.1.5
		 */
		public function add_item( $item ): int {
			global $wpdb;
			$lp_user_items_db = LP_User_Items_DB::getInstance();

			try {
				if ( is_numeric( $item ) ) {
					$item = array(
						'item_id'         => absint( $item ),
						'order_item_name' => get_the_title( $item ),
					);
				}

				$item_type = get_post_type( $item['item_id'] );
				if ( ! in_array( $item_type, learn_press_get_item_types_can_purchase() ) ) {
					return false;
				}

				$item = wp_parse_args(
					$item,
					array(
						'item_id'         => 0,
						'item_type'       => '',
						'order_item_name' => '',
						'quantity'        => 1,
						'subtotal'        => 0,
						'total'           => 0,
						'meta'            => array(),
					)
				);

				switch ( $item_type ) {
					case LP_COURSE_CPT:
						$course                  = learn_press_get_course( $item['item_id'] );
						$item['subtotal']        = apply_filters( 'learnpress/order/item/subtotal', $course->get_price() * $item['quantity'], $course, $item );
						$item['total']           = apply_filters( 'learnpress/order/item/total', $course->get_price() * $item['quantity'], $course, $item );
						$item['order_item_name'] = apply_filters( 'learnpress/order/item/title', $course->get_title(), $course, $item );

						if ( $this->check_can_delete_item_old( $course ) ) {
							// Delete lp_user_items old
							$user_ids = $this->get_users();
							foreach ( $user_ids as $user_id ) {
								$lp_user_items_db->delete_user_items_old( $user_id, $course->get_id() );
							}
							// End
						}

						//learn_press_add_order_item_meta( $order_item_id, '_course_id', $item['item_id'] );
						$item['meta']['_course_id'] = $item['item_id'];
						break;
					default:
						$item = apply_filters( 'learnpress/order/add-item/item_type_' . $item_type, $item );
						break;
				}

				// Insert new order item
				$wpdb->insert(
					$wpdb->learnpress_order_items,
					array(
						'order_item_name' => $item['order_item_name'],
						'order_id'        => $this->get_id(),
						'item_id'         => $item['item_id'],
						'item_type'       => $item_type,
					),
					array(
						'%s',
						'%d',
						'%d',
						'%s',
					)
				);
				$order_item_id = absint( $wpdb->insert_id );
				// End insert new order item

				// Add learnpress_order_itemmeta
				$item['meta']['_quantity'] = $item['quantity'];
				$item['meta']['_subtotal'] = $item['subtotal'] ?? 0;
				$item['meta']['_total']    = $item['total'] ?? 0;

				if ( is_array( $item['meta'] ) ) {
					foreach ( $item['meta'] as $k => $v ) {
						learn_press_add_order_item_meta( $order_item_id, $k, $v );
					}
				}

				do_action( 'learn-press/added-order-item-data', $order_item_id, $item, $this->get_id() );
			} catch ( Throwable $e ) {
				error_log( __FUNCTION__ . ': ' . $e->getMessage() );
			}

			return $order_item_id;
		}

		/**
		 * Set total
		 *
		 * @param int|float $total
		 */
		public function set_total( $total = 0 ) {
			$this->_set_data( 'total', $total );
		}

		/**
		 * Get total
		 *
		 * @return int|float
		 */
		public function get_total() {
			return $this->get_data( 'total' );
		}

		/**
		 * @param float|int $subtotal
		 */
		public function set_subtotal( $subtotal = 0 ) {
			$this->_set_data( 'subtotal', $subtotal );
		}

		/**
		 * Get subtotal
		 *
		 * @return float
		 */
		public function get_subtotal() {
			return $this->get_data( 'subtotal' );
		}

		public function cln() {
			return $this->_curd->cln( $this );
		}

		public function cln_items( $to ) {
			return $this->_curd->cln_items( $this->get_id(), $to );
		}

		/**
		 * Remove an item from database and it's data.
		 *
		 * @param int $item_id
		 *
		 * @return bool
		 */
		public function remove_item( $item_id ) {
			global $wpdb;

			$item_id = absint( $item_id );

			if ( ! $item_id ) {
				return false;
			}

			do_action( 'learn_press_before_delete_order_item', $item_id );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/before-delete-order-item', $item_id, $this->get_id() );

			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_order_items WHERE order_item_id = %d", $item_id ) );
			$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_order_itemmeta WHERE learnpress_order_item_id = %d", $item_id ) );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/deleted-order-item', $item_id, $this->get_id() );

			do_action( 'learn_press_delete_order_item', $item_id );

			return true;
		}

		/**
		 * Add multiple items.
		 *
		 * @param array $items
		 *
		 * @return array
		 */
		public function add_items( $items ) {
			settype( $items, 'array' );
			$item_ids = array();
			foreach ( $items as $item ) {
				$item_id = $this->add_item( $item );
				if ( $item_id ) {
					$item_ids[] = $item_id;
				}
			}

			return $item_ids;
		}

		/**
		 * @param string $field
		 *
		 * @return array|bool|int|LP_User|mixed
		 */
		public function get_user( $field = '' ) {

			$users = $this->get_users();
			$uid   = reset( $users );

			$user = learn_press_get_user( $uid );
			if ( false === $user ) {
				return false;
			}

			if ( strtolower( $field ) == 'id' ) {
				return $user->get_id();
			}

			if ( $field && $user ) {
				return $user->get_data( $field );
			}

			return $user;
		}

		/**
		 * Get user id in array.
		 * user_id = 0 -> User type Guest
		 * @return int[]
		 * @editor tungnx
		 * @modify 4.1.4
		 * @version 1.0.1
		 */
		public function get_users(): array {
			$users = $this->get_user_id();
			if ( $users !== -1 ) {
				settype( $users, 'array' );
				$users = array_unique( $users );
			} else {
				$users = array();
			}

			return $users;
		}

		public function dropdown_users() {
			$order_users = $this->get_users();
			$users       = get_users( array() );
			echo '<select name="order-customer[]" id="order-customer" multiple="multiple">';
			foreach ( (array) $users as $user ) {
				// $user->get_id() = (int) $user->get_id();
				if ( in_array( $user->get_id(), $order_users ) ) {
					$found_selected = true;
				} else {
					$found_selected = false;
				}
				echo sprintf( '<option value="%d" %s>%s</option>', esc_attr( $user->get_id() ), selected( $found_selected, true, false ), esc_html( $user->user_login ) );
			}
			echo '</select>';
		}


		public function get_checkout_payment_url() {

		}

		public function get_formatted_order_subtotal() {
			$currency_symbol = learn_press_get_currency_symbol( $this->get_currency() );

			return learn_press_format_price( $this->get_subtotal(), $currency_symbol );
		}

		public function get_formatted_order_total() {
			$currency_symbol = learn_press_get_currency_symbol( $this->get_currency() );

			return learn_press_format_price( $this->get_total(), $currency_symbol );
		}

		public function get_currency() {
			return $this->get_data( 'currency' ) ? $this->get_data( 'currency' ) : learn_press_get_currency();
		}

		public function set_currency( $value ) {
			$this->_set_data( 'currency', $value );
		}

		public function get_payment_method_title() {
			if ( $this->order_total == 0 ) {
				$title = '';
			} else {
				$title = $this->payment_method_title;
			}

			return $title;
		}

		public function get_view_order_url() {
			global $wp_query;

			$view_order_url      = learn_press_get_endpoint_url( 'view-order', $this->get_id(), learn_press_get_page_link( 'profile' ) );
			$user                = learn_press_get_current_user();
			$view_order_endpoint = LP()->settings->get( 'profile_endpoints.order-details' );

			if ( ! $view_order_endpoint ) {
				$view_order_endpoint = 'order-details';
			}

			$view_order_endpoint = urlencode( $view_order_endpoint );
			if ( get_option( 'permalink_structure' ) ) {
				$view_order_url = learn_press_get_page_link( 'profile' ) . $user->get_data( 'user_login' ) . '/' . $view_order_endpoint . '/' . $this->get_id() . '/';
			} else {
				$args         = array(
					'user' => $user->get_data( 'user_login' ),
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

		/**
		 * Get cancel url if it's status is pending.
		 *
		 * @param bool $force
		 *
		 * @return mixed
		 */
		public function get_cancel_order_url( $force = false ) {

			$url = false;
			if ( $this->has_status( 'pending' ) ) {
				$user = learn_press_get_current_user();
				$url  = learn_press_user_profile_link( $user->get_id(), LP()->settings->get( 'profile_endpoints.profile-orders' ) );
				if ( ! $force ) {
					$url = add_query_arg( 'cancel-order', $this->get_id(), $url );
				} else {
					$url = add_query_arg( 'cancelled-order', $this->get_id(), $url );
				}

				$url = wp_nonce_url( $url, 'cancel-order', 'lp-nonce' );
			}

			return apply_filters( 'learn-press/order-cancel-url', $url, $this->get_id() );
		}

		/**
		 * Get profile order's actions.
		 *
		 * @return array|mixed
		 */
		public function get_profile_order_actions() {
			$actions = array(
				'view' => array(
					'url'  => $this->get_view_order_url(),
					'text' => __( 'View', 'learnpress' ),
				),
			);

			$cancel_url = $this->get_cancel_order_url();
			if ( $cancel_url ) {
				$actions['cancel'] = array(
					'url'  => $this->get_cancel_order_url(),
					'text' => __( 'Cancel', 'learnpress' ),
				);
			}
			$actions = apply_filters( 'learn-press/profile-order-actions', $actions, $this->get_id() );

			return $actions;
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

		/**
		 * Get user_name
		 *
		 * @param int $user_id
		 * @return string
		 */
		public function get_user_name( int $user_id = 0 ): string {
			$user = learn_press_get_user( $user_id );

			if ( $user && ! $user instanceof LP_User_Guest ) {
				$user_name = $user->get_display_name();
			} else {
				$user_name = $this->get_checkout_email();
			}

			return apply_filters( 'learn-press/order/user-name', sprintf( _x( '%1$s', 'full name', 'learnpress' ), $user_name ) );
		}

		/**
		 * Check to see if this order is for multi users
		 *
		 * @return bool
		 * @since 2.1.5
		 */
		public function is_multi_users(): bool {
			return is_array( $this->get_user_id() );
		}

		/**
		 * Print the list of all users has assigned to this order
		 * in case this order is for multi users
		 *
		 * @since 2.1.5
		 */
		public function print_users() {
			/**
			 * Get meta as multiple keys for backward compatibility with older version
			 */
			$user_ids = get_post_meta( $this->get_id(), '_user_id' );
			if ( $user_ids ) {
				global $wpdb;
				if ( is_array( $user_ids[0] ) ) {
					$user_ids = reset( $user_ids );
				}
				$format_ids = array_fill( 0, sizeof( $user_ids ), '%d' );
				$sql        = $wpdb->prepare( "SELECT user_login, user_email FROM {$wpdb->users} WHERE ID IN(" . join( ',', $format_ids ) . ')', $user_ids );
				$users      = $wpdb->get_results( $sql );
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
		 * @return array
		 * @since 2.1.5
		 */
		public function get_user_data(): array {
			$data = array();

			$user_ids = $this->get_users();
			if ( ! empty( $user_ids ) ) {
				foreach ( $user_ids as $user_id ) {
					$user = learn_press_get_user( $user_id );
					if ( $user->is_exists() ) {
						$data[ $user_id ] = $user->get_data(
							array(
								'id',
								'email',
								'user_login',
								'description',
								'first_name',
								'last_name',
								'nickname',
								'display_name',
							)
						);
					}
				}
			}

			return $data;
		}

		/**
		 * Get email's user by user_id
		 *
		 * @param int $user_id
		 * @return string
		 * @editor tungnx
		 * @modify 4.1.3
		 */
		public function get_user_email( int $user_id = 0 ): string {
			$user = learn_press_get_user( $user_id );
			if ( $user && ! $user instanceof LP_User_Guest ) {
				$email = $user->get_email();
			} else {
				$email = $this->get_checkout_email();
			}

			return $email;
		}

		public function get_child_orders( $force = false ) {
			if ( $force ) {
				wp_cache_delete( 'order-' . $this->get_id(), 'learn-press/child-orders' );
			}

			return apply_filters( 'learn-press/child-orders', $this->_curd->get_child_orders( $this->get_id() ), $this->get_id() );
		}

		/**
		 * Order title
		 *
		 * @param string $context
		 *
		 * @return array|mixed
		 */
		public function get_title( $context = '' ) {
			return $this->get_data( 'order_title', __( 'Order on', 'learnpress' ) . ' ' . current_time( 'l jS F Y h:i:s A' ) );
		}

		public function get_parent_id() {
			return $this->get_data( 'parent_id', 0 );
		}

		public function set_parent_id( $parent_id ) {
			$this->_set_data( 'parent_id', $parent_id );
		}

		public function set_user_id( $user_id ) {
			$this->_set_data( 'user_id', $user_id );
		}

		/**
		 * Set 1 for is user guest
		 *
		 * @param int $user_type_guest
		 */
		public function set_user_type_guest( int $user_type_guest ) {
			$this->_set_data( 'user_type_guest', $user_type_guest );
		}

		/**
		 * Get user's ids of order.
		 *
		 * @return array|int
		 */
		public function get_user_id() {
			return $this->get_data( 'user_id', -1 );
		}

		/**
		 * Get date modified of order.
		 *
		 * @return LP_Datetime
		 */
		public function get_date_modified() {
			return $this->get_data( 'date_modified' );
		}

		/**
		 * Set date modified of order.
		 *
		 * @param mixed $date
		 */
		public function set_date_modified( $date ) {
			$this->_set_data( 'date_modified', $date );
		}

		/**
		 * Set method for creating the order, such as: checkout
		 *
		 * @param string $created_via
		 */
		public function set_created_via( $created_via ) {
			$this->_set_data( 'created_via', $created_via );
		}

		/**
		 * Get method which order is created, such as: checkout
		 *
		 * @return string
		 */
		public function get_created_via(): string {
			return $this->get_data( 'created_via', '' );
		}

		/**
		 * Update order status if changed and trigger actions.
		 *
		 * @return bool
		 * @throws Exception
		 *
		 * @editor tungnx
		 * @modify 4.1.3 - comment - not use
		 */
		//      public function _save_status(): bool {
		//
		//          // Nothing changed
		//          if ( ! $this->_status ) {
		//              return false;
		//          }
		//
		//          $order_id   = $this->get_id();
		//          $old_status = $this->_status['from'] ?? '';
		//          $new_status = $this->_status['to'] ?? '';
		//
		//          // Only update if new status is difference with old status.
		//          if ( $new_status !== $old_status ) {
		//
		//              if ( ! $this->get_user_id() ) {
		//                  // $new_status = 'pending';
		//              }
		//
		//              if ( doing_action( 'save_post' ) ) {
		//                  // Update post's status using wpdb to preventing loop
		//                  global $wpdb;
		//                  $updated = $wpdb->update( $wpdb->posts, array( 'post_status' => 'lp-' . $new_status ), array( 'ID' => $order_id ), array( '%s' ) );
		//              } else {
		//                  $updated = wp_update_post(
		//                      array(
		//                          'post_status' => 'lp-' . $new_status,
		//                          'ID'          => $order_id,
		//                      )
		//                  );
		//              }
		//
		//              // Clear cache
		//              wp_cache_delete( $order_id, 'posts' );
		//
		//              /**
		//               * Trigger actions after status was changed
		//               *
		//               * @deprecated
		//               */
		//              //do_action( 'learn_press_order_status_' . $new_status, $order_id );
		//              //do_action( 'learn_press_order_status_' . $old_status . '_to_' . $new_status, $order_id );
		//              //do_action( 'learn_press_order_status_changed', $order_id, $old_status, $new_status );
		//              // backward compatible
		//              do_action( 'learn_press_update_order_status', $new_status, $order_id );
		//
		//              /**
		//               * @since 3.0.0
		//               */
		//              do_action( 'learn-press/order/status-' . $new_status, $order_id, $old_status );
		//              do_action( 'learn-press/order/status-' . $old_status . '-to-' . $new_status, $order_id );
		//              do_action( 'learn-press/order/status-changed', $order_id, $old_status, $new_status );
		//
		//              return true;
		//          }
		//
		//          return false;
		//      }

		/**
		 * Save order data.
		 *
		 * @return mixed
		 *
		 * @throws Exception
		 */
		public function save() {
			$old_status = '';

			if ( $this->get_id() ) {
				$old_status = str_replace( 'lp-', '', get_post_status( $this->get_id() ) );
				$return     = $this->_curd->update( $this );
			} else {
				$return = $this->_curd->create( $this );
			}

			$new_status = str_replace( 'lp-', '', get_post_status( $this->get_id() ) );

			$order_id = $this->get_id();

			if ( $new_status !== $old_status ) {
				do_action( 'learn-press/order/status-' . $new_status, $order_id, $old_status );
				do_action( 'learn-press/order/status-' . $old_status . '-to-' . $new_status, $order_id );
				do_action( 'learn-press/order/status-changed', $order_id, $old_status, $new_status );
				//do_action( 'learn_press_update_order_status', $new_status, $order_id );
			}

			//$this->_save_status();

			return $return;
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

		/** Getter/Setter  **/

		public function set_customer_message( $message ) {
			$this->_set_data( 'customer_message', $message );
		}

		public function set_customer_note( $note ) {
			$this->_set_data( 'customer_note', $note );
		}

		public function get_customer_note() {
			return $this->get_data( 'customer_note' ) . '';
		}

		public function set_user_ip_address( $value ) {
			$this->_set_data( 'user_ip_address', $value );
		}

		public function set_user_agent( $value ) {
			$this->_set_data( 'user_agent', $value );
		}

		public function get_user_agent() {
			return $this->get_data( 'user_agent' );
		}

		public function set_checkout_email( $email ) {
			$this->_set_data( 'checkout_email', $email );
		}

		/**
		 * Get email checkout for case Guest
		 *
		 * @return string
		 */
		public function get_checkout_email(): string {
			return $this->get_data( 'checkout_email', '' );
		}

		/**
		 * Short function to check order is completed.
		 *
		 * @return bool
		 */
		public function is_completed(): bool {
			return $this->get_order_status() === 'completed';
		}

		/**
		 * Check Order is created manual.
		 *
		 * @return bool
		 * @since 4.1.3
		 * @author tungnx
		 * @version 1.0.0
		 */
		public function is_manual(): bool {
			return $this->get_created_via() === 'manual';
		}

		/**
		 * Check can delete lp_user_items old
		 *
		 * @param LP_Course $course
		 *
		 * @return bool
		 * @throws Exception
		 * @author tungnx
		 * @since 4.1.4
		 * @version 1.0.0
		 */
		public function check_can_delete_item_old( LP_Course $course ): bool {
			$can_delete = false;

			$lp_user_items_db = LP_User_Items_DB::getInstance();

			/**
			 * For case user buy on frontend (not LP Order manual)
			 * And course enable repurchase and repurchase_type is keep
			 */
			$allow_repurchase_type = '';

			$filter          = new LP_User_Items_Filter();
			$filter->user_id = get_current_user_id();
			$filter->item_id = $course->get_id();
			$user_course     = $lp_user_items_db->get_last_user_course( $filter );

			if ( $user_course && isset( $user_course->user_item_id ) ) {
				$latest_user_item_id = $user_course->user_item_id;

				/** Get allow_repurchase_type for reset or update. Add in: rest-api/v1/frontend/class-lp-courses-controller.php: purchase_course */
				$allow_repurchase_type = learn_press_get_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type' );
			}

			/**
			 * Course is free
			 * Or not allow repurchase
			 * Or repurchase not type 'Keep'
			 * And else
			 * Will deleted lp_user_items old
			 */
			if ( $course->is_free() || empty( $allow_repurchase_type ) || ! $course->allow_repurchase() || $allow_repurchase_type != 'keep' ) {
				$can_delete = true;
			}

			return apply_filters( 'learnpress/order/can_delete_old_item', $can_delete, $course );
		}
	}
}

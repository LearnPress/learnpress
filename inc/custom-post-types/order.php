<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
if ( ! class_exists( 'LP_Order_Post_Type' ) ) {

	// class LP_Order_Post_Type
	final class LP_Order_Post_Type extends LP_Abstract_Post_Type {

		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * LP_Order_Post_Type constructor.
		 *
		 * @param $post_type
		 */
		public function __construct( $post_type ) {

			add_action( 'init', array( $this, 'register_post_statues' ) );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_action( 'admin_init', array( $this, 'remove_box' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'trashed_post', array( $this, 'trashed_order' ) );
			add_action( 'transition_post_status', array( $this, 'restore_order' ), 10, 3 );

			add_filter( 'admin_footer', array( $this, 'admin_footer' ) );
			//add_action( 'add_meta_boxes', array( $this, 'post_new' ) );

			$this
				->add_map_method( 'before_delete', 'delete_order_data' )
				->add_map_method( 'save', 'save_order' );


			add_filter( 'wp_count_posts', array( $this, 'filter_count_posts' ), 100, 3 );
			add_filter( 'views_edit-lp_order', array( $this, 'filter_views' ) );
			add_filter( 'posts_where_paged', array( $this, 'filter_orders' ) );

			parent::__construct( $post_type );

		}

		/**
		 * Filter the counts of posts when wp counting orders by statuses.
		 * Maybe there are some orders are created for multiple users,
		 * and each user in main order will be assigned to a separated
		 * order with post_parent is ID of main order. And, we do not
		 * want to show these orders in the list.
		 *
		 * @param array  $counts
		 * @param string $type
		 * @param string $perm
		 *
		 * @return array|object
		 */
		public function filter_count_posts( $counts, $type, $perm ) {

			if ( LP_ORDER_CPT === $type ) {
				$cache_key = 'lp-' . _count_posts_cache_key( $type, $perm );

				$counts = wp_cache_get( $cache_key, 'counts' );

				if ( false !== $counts ) {
					return $counts;
				}

				global $wpdb;
				$query = "
				        SELECT post_status, COUNT( * ) AS num_posts 
                        FROM {$wpdb->posts}
                        WHERE post_type = %s
                        AND post_parent = %d
				    ";

				if ( 'readable' == $perm && is_user_logged_in() ) {
					$post_type_object = get_post_type_object( $type );
					if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
						$query .= $wpdb->prepare( " AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
							get_current_user_id()
						);
					}
				}
				$query .= ' GROUP BY post_status';

				$results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type, 0 ), ARRAY_A );
				$counts  = array_fill_keys( get_post_stati(), 0 );

				foreach ( $results as $row ) {
					$counts[ $row['post_status'] ] = $row['num_posts'];
				}

				$counts = (object) $counts;
				wp_cache_set( $cache_key, $counts, 'counts' );
			}

			return $counts;
		}

		/**
		 * Unset value in 'mine' key in views of LP Orders.
		 * The 'mine' is present in some case when 'user_posts_count'
		 * is not the same with total posts then wp add it to the views
		 * of WP Posts List table.
		 *
		 * @param array $views
		 *
		 * @return mixed
		 */
		public function filter_views( $views ) {
			if ( isset( $views['mine'] ) ) {
				unset( $views['mine'] );
			}

			return $views;
		}

		/**
		 * Filter to hide orders are created by one order for multiple users.
		 *
		 * @param string $where
		 *
		 * @return string
		 */
		public function filter_orders( $where ) {
			if ( ! $this->_is_archive() ) {
				return $where;
			}

			if ( isset( $_REQUEST['parent'] ) ) {
				$where .= sprintf( " AND post_parent = %d ", absint( $_REQUEST['parent'] ) );
			} else {
				$where .= " AND post_parent = 0 ";
			}

			return $where;
		}

//		public function post_new() {
//			global $post;
//			if ( $post && $post->post_type == 'lp_order' && $post->post_status == 'auto-draft' && learn_press_get_request( 'multi-users' ) == 'yes' ) {
//				update_post_meta( $post->ID, '_lp_multi_users', 'yes' );
//			}
//		}

		public function enqueue_scripts() {
			if ( get_post_type() != 'lp_order' ) {
				return;
			}
			wp_enqueue_script( 'user-suggest' );
		}

		/**
		 * Disable accessing course if the order is trashed.
		 *
		 * @param int $order_id
		 */
		public function trashed_order( $order_id ) {

			if ( ! $order = learn_press_get_order( $order_id ) ) {
				return;
			}

			if ( ! $items = $order->get_items() ) {
				return;
			}

			if ( ! $users = $order->get_users() ) {
				return;
			}

			// Also trash child orders
			if ( $order->is_multi_users() && ( $child_orders = $order->get_child_orders() ) ) {
				foreach ( $child_orders as $child_order ) {
					wp_trash_post( $child_order );
				}
			}

			return;

			$user_curd  = new LP_User_CURD();
			$order_data = array();
			foreach ( $users as $user_id ) {
				$user = learn_press_get_user( $user_id );
				if ( ! $user ) {
					continue;
				}

				foreach ( $items as $item ) {
					$item_course = $user->get_course_data( $item['course_id'] );

					if ( ! $item_course ) {
						continue;
					}

					// Store user_id and item_id of current user item into the order
					$order_data[ $item_course->get_user_item_id() ] = array(
						'user_id' => $item_course->get_user_id(),
						'item_id' => $item_course->get_item_id()
					);

					// And remove it from user item
					$user_curd->update_user_item_by_id(
						$item_course->get_user_item_id(),
						array(
							'user_id' => - 1,
							'item_id' => - 1
						)
					);
				}
			}

			// Store all to the order itself
			update_post_meta( $order_id, '_lp_user_data', $order_data );
		}

		/**
		 * Restore user course item when the order is stored (usually from trash).
		 *
		 * @param string  $new
		 * @param string  $old
		 * @param WP_Post $post
		 */
		public function restore_order( $new, $old, $post ) {

			if ( ! ( 'trash' === $old ) ) {
				return;
			}

			if ( ! $order = learn_press_get_order( $post->ID ) ) {
				return;
			}

			if ( ! $user_item_data = get_post_meta( $post->ID, '_lp_user_data', true ) ) {
				return;
			}

			if ( ! $items = $order->get_items() ) {
				return;
			}

			if ( ! $users = $order->get_users() ) {
				return;
			}

			// Restore child order if current order is for multi users
			if ( $order->is_multi_users() && ( $child_orders = $order->get_child_orders() ) ) {
				foreach ( $child_orders as $child_order ) {
					wp_untrash_post( $child_order );
				}
			}

			$user_curd = new LP_User_CURD();

			foreach ( $user_item_data as $user_item_id => $data ) {

				if ( ! $item_course = $user_curd->get_user_item_by_id( $user_item_id ) ) {
					continue;
				}

				// Restore data
				$user_curd->update_user_item_by_id(
					$user_item_id,
					$data
				);
			}

			// Delete data
			delete_post_meta( $post->ID, '_lp_user_data' );
		}

		/**
		 * Delete all records related to order being deleted.
		 *
		 * @since 3.0.0
		 *
		 * @param int $post_id
		 *
		 * @return mixed
		 */
		public function delete_order_data( $post_id ) {

			if ( get_post_type( $post_id ) != 'lp_order' ) {
				return false;
			}

			if ( $order = learn_press_get_order( $post_id ) ) {
				return LP_Factory::get_order_factory()->delete_order_data( $order );
			}

			return false;
		}

		/**
		 * Process when saving order with multi users
		 *
		 * @param $post_id
		 * @param $user_id
		 */
		private function _save_order_multi_users( $post_id, $user_id ) {
			global $wpdb;
			settype( $user_id, 'array' );

			update_post_meta( $post_id, '_user_id', $user_id );

//			return;
//
//			$sql = "
//				SELECT meta_id, meta_value
//				FROM {$wpdb->postmeta}
//				WHERE post_id = %d
//				AND meta_key = %s
//			";
//			$sql = $wpdb->prepare( $sql, $post_id, '_user_id' );
//			/**
//			 * A simpler way is remove all meta_key are _user_id and then
//			 * add new user_id as new meta_key but this maybe make our database
//			 * increase the auto-increment each time order is updated
//			 * in case the user_id is not changed
//			 */
//			if ( $existed = $wpdb->get_results( $sql ) ) {
//				$cases      = array();
//				$edited     = array();
//				$meta_ids   = array();
//				$remove_ids = array( 0 );
//				foreach ( $existed as $k => $r ) {
//					if ( empty( $user_id[ $k ] ) ) {
//						$remove_ids[] = $r->meta_id;
//						continue;
//					}
//					$cases[]    = $wpdb->prepare( "WHEN meta_id = %d THEN %d", $r->meta_id, $user_id[ $k ] );
//					$edited[]   = $user_id[ $k ];
//					$meta_ids[] = $r->meta_id;
//				}
//				$sql = "
//					UPDATE {$wpdb->postmeta}
//					SET meta_value = CASE
//					" . join( "\n", $cases ) . "
//					ELSE meta_value
//					END
//					WHERE meta_id IN(" . join( ', ', $meta_ids ) . ")
//					AND post_id = %d
//					AND meta_key = %s
//				";
//				$sql = $wpdb->prepare( $sql, $post_id, '_user_id' );
//				$wpdb->query( $sql );
//				$user_id = array_diff( $user_id, $edited );
//			}
//			if ( $user_id ) {
//				$values = array();
//				foreach ( $user_id as $id ) {
//					$values[] = sprintf( "(%d, '%s', %d)", $post_id, '_user_id', $id );
//				}
//				$sql = "INSERT INTO {$wpdb->postmeta}(post_id, meta_key, meta_value) VALUES" . join( ',', $values );
//				$wpdb->query( $sql );
//			}
//			$sql        = "
//				SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_id NOT IN(" . join( ',', $remove_ids ) . ") AND post_id = %d AND meta_key = %s GROUP BY meta_value
//			";
//			$sql        = $wpdb->prepare( $sql, $post_id, '_user_id' );
//			$keep_users = $wpdb->get_col( $sql );
//			if ( $keep_users ) {
//				$sql = "
//					DELETE
//					FROM {$wpdb->postmeta}
//					WHERE post_id = %d
//					AND meta_key = %s
//					AND ( meta_id NOT IN(" . join( ',', $keep_users ) . ") OR meta_value = 0)
//				";
//				$sql = $wpdb->prepare( $sql, $post_id, '_user_id' );
//				$wpdb->query( $sql );
//			}
//			update_post_meta( $post_id, '_lp_multi_users', 'yes', 'yes' );
//			learn_press_reset_auto_increment( 'postmeta' );
		}

		/**
		 * @param LP_Order $order
		 * @param array    $user_ids
		 * @param bool     $trigger_action
		 */
		protected function _update_child( $order, $user_ids, $trigger_action = false ) {
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

				$old_status = get_post_status( $new_order->get_id() );
				$new_order->set_order_date( $order->get_order_date() );
				$new_order->set_parent_id( $order->get_id() );
				$new_order->set_user_id( $uid );
				$new_order->set_total( $order->get_total() );
				$new_order->set_subtotal( $order->get_subtotal() );

				$new_order->set_status( learn_press_get_request( 'order-status' ) );
				$new_order->save();
				$new_status = get_post_status( $new_order->get_id() );

				if ( ( $new_status === $old_status ) && $trigger_action ) {
					$status = str_replace( 'lp-', '', $new_status );
					do_action( 'learn-press/order/status-' . $status, $new_order->get_id(), $status );
					do_action( 'learn-press/order/status-' . $status . '-to-' . $status, $new_order->get_id() );
					do_action( 'learn-press/order/status-changed', $new_order->get_id(), $status, $status );
				}
			}
		}

		/**
		 * Save order data
		 *
		 * @param int $post_id
		 */
		public function save_order( $post_id ) {
			global $action, $wpdb;
			if ( wp_is_post_revision( $post_id ) ) {
				return;
			}
			if ( $action == 'editpost' && get_post_type( $post_id ) == 'lp_order' ) {
				remove_action( 'save_post', array( $this, 'save_order' ) );
				remove_action( 'learn_press_order_status_completed', 'learn_press_auto_enroll_user_to_courses' );


				$user_id        = learn_press_get_request( 'order-customer' );
				$order          = learn_press_get_order( $post_id );
				$old_status     = get_post_status( $order->get_id() );
				$trigger_action = LP_Request::get_string( 'trigger-order-action' ) == 'current-status';

				if ( is_array( $user_id ) ) {
					$this->_update_child( $order, $user_id, $trigger_action );
					$order->set_user_id( $user_id );
				} else {
					$order->set_user_id( absint( $user_id ) );
				}
				$order->set_status( learn_press_get_request( 'order-status' ) );
				$order->save();

				$new_status = get_post_status( $order->get_id() );

				/**
				 * If the status is not changed and force to trigger action is set
				 * then trigger action for current status if this order is for singular
				 * user. If the order is for multi users then it will trigger in
				 * each child order
				 */
				if ( ! is_array( $user_id ) && ( $new_status === $old_status ) && $trigger_action ) {
					$status = str_replace( 'lp-', '', $new_status );
					do_action( 'learn-press/order/status-' . $status, $order->get_id(), $status );
					do_action( 'learn-press/order/status-' . $status . '-to-' . $status, $order->get_id() );
					do_action( 'learn-press/order/status-changed', $order->get_id(), $status, $status );
				}
			}
		}

		/**
		 * Remove unused boxes
		 */
		public function remove_box() {
			remove_post_type_support( LP_ORDER_CPT, 'title' );
			remove_post_type_support( LP_ORDER_CPT, 'editor' );
		}

		public function admin_footer() {
			if ( ! $this->_is_archive() ) {
				return;
			}
			?>
            <script type="text/javascript">
                jQuery(function ($) {
                    $('#post-search-input').prop('placeholder', '<?php esc_attr_e( 'Order number, user name, user email, course name etc...', 'learnpress' ); ?>').css('width', 400)
                });
            </script>
			<?php
		}

		/**
		 * Hook to filter LP orders by some conditions.
		 *
		 * @param string $where
		 *
		 * @return mixed
		 */
		public function posts_where_paged( $where ) {
			global $wpdb, $wp_query;

			if ( ! $this->_is_archive() || ! $this->_is_search() ) {
				return $where;
			}

			$s      = '%' . $wpdb->esc_like( $wp_query->get( 's' ) ) . '%';
			$append = $wpdb->prepare( " (uu.user_login LIKE %s
					OR uu.user_nicename LIKE %s
					OR uu.user_email LIKE %s
					OR uu.display_name LIKE %s
					OR {$wpdb->posts}.ID LIKE %s
                                        OR orderItem.order_item_name LIKE %s
				) OR ", $s, $s, $s, $s, $s, $s );
			$where  = preg_replace( "/({$wpdb->posts}\.post_title LIKE)/", $append . '$1', $where );

			return $where;
		}

		public function posts_fields( $fields ) {
			if ( ! $this->_is_archive() || ! $this->_is_search() ) {
				return $fields;
			}
			$fields .= ", uu.ID as user_ID, uu.display_name as user_display_name";

			return $fields;
		}

		public function posts_orderby( $orderby ) {
			if ( ! $this->_is_archive() || ! $this->_is_search() ) {
				return $orderby;
			}

			return $orderby;
		}

		public function posts_join_paged( $join ) {
			if ( ! $this->_is_archive() || ! $this->_is_search() ) {
				return $join;
			}
			global $wpdb;
			$join .= " INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id";
			$join .= " INNER JOIN {$wpdb->users} uu ON uu.ID = {$wpdb->postmeta}.meta_value";
			$join .= " INNER JOIN {$wpdb->learnpress_order_items} AS orderItem ON orderItem.order_id = {$wpdb->posts}.ID";

			return $join;
		}

		/**
		 * Make our custom columns can be sortable
		 *
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns['order_student'] = 'student';

			return $columns;
		}

		public function admin_head() {

			global $post, $wp_query;

			if ( LP_ORDER_CPT != get_post_type() ) {
				return;
			}
			ob_start();
			?>
            <script>
                $('#update-order-status').click(function () {
                    var $button = $(this).attr('disabled', 'disabled').html('<?php _e( 'Processing...', 'learnpress' ); ?>');
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'update_order_status',
                            order_id: '<?php echo $post->ID; ?>',
                            status: $('select[name="learn_press_order_status"]').val()
                        },
                        success: function (res) {
                            if (res.status) {
                                $('.order-data-status')
                                    .removeClass('pending completed')
                                    .html(res.status)
                                    .addClass(res.class);
                            }
                            $button.removeAttr('disabled').html('<?php _e( 'Apply', 'learnpress' ); ?>');
                        },
                        error: function () {
                            $button.removeAttr('disabled').html('<?php _e( 'Apply', 'learnpress' ); ?>');
                        }
                    });
                })
            </script>
			<?php
			$js = preg_replace( '!</?script>!', '', ob_get_clean() );
			learn_press_enqueue_script( $js );
		}

		public function update_status() {
			$order_id = ! empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : 0;
			$status   = ! empty( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'Pending';
			learn_press_update_order_status( $order_id, $status );

			wp_send_json(
				array(
					'status' => $status,
					'class'  => sanitize_title( $status )
				)
			);
		}

		/**
		 * Custom row's actions.
		 *
		 * @param array   $actions
		 * @param WP_Post $post
		 *
		 * @since 2.1.7
		 *
		 * @return mixed
		 */
		public function row_actions( $actions, $post ) {
			if ( ! empty( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			if ( ! empty( $actions['edit'] ) ) {
				$actions['edit'] = preg_replace( '/>(.*?)<\/a>/', ">" . __( 'View Order', 'learnpress' ) . "</a>", $actions['edit'] );
			}

			$order = learn_press_get_order( $post->ID );
			if ( $order->is_multi_users() ) {
				$actions['child-orders'] = sprintf( '<a href="%s">%s</a>', add_query_arg( array(
					'post_type' => LP_ORDER_CPT,
					'parent'    => $post->ID
				), admin_url( 'edit.php' ) ), __( 'View child orders', 'learnpress' ) );
			}

			return $actions;
		}

		/**
		 * re-order the orders by newest
		 *
		 * @param $wp_query
		 *
		 * @return mixed
		 */
		public function pre_get_posts( $wp_query ) {
			if ( is_admin() ) {
				if ( ! empty( $wp_query->query['post_type'] ) && ( $wp_query->query['post_type'] == LP_ORDER_CPT ) ) {
					$wp_query->set( 'orderby', 'date' );
					$wp_query->set( 'order', 'desc' );
				}
			}

			return $wp_query;
		}

		/**
		 *
		 */
		public function columns_head( $existing ) {

			// Remove Checkbox - adding it back below
			if ( isset( $existing['cb'] ) ) {
				$check = $existing['cb'];
				unset( $existing['cb'] );
			}

			// Remove Title - adding it back below
			if ( isset( $existing['title'] ) ) {
				unset( $existing['title'] );
			}

			// Remove Format
			if ( isset( $existing['format'] ) ) {
				unset( $existing['format'] );
			}

			// Remove Author
			if ( isset( $existing['author'] ) ) {
				unset( $existing['author'] );
			}

			// Remove Comments
			if ( isset( $existing['comments'] ) ) {
				unset( $existing['comments'] );
			}

			// Remove Date
			if ( isset( $existing['date'] ) ) {
				unset( $existing['date'] );
			}

			// Remove Builder
			if ( isset( $existing['builder_layout'] ) ) {
				unset( $existing['builder_layout'] );
			}

			add_filter( 'the_title', array( $this, 'order_title' ), 5, 2 );

			$columns['cb']            = '<input type="checkbox" />';
			$columns['title']         = __( 'Order', 'learnpress' );
			$columns['order_student'] = __( 'Student', 'learnpress' );
			$columns['order_items']   = __( 'Purchased', 'learnpress' );
			$columns['order_date']    = __( 'Date', 'learnpress' );
			$columns['order_total']   = __( 'Total', 'learnpress' );
			$columns['order_status']  = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'learnpress' ) . '">' . esc_attr__( 'Status', 'learnpress' ) . '</span>';

			$columns = array_merge( $columns, $existing );

			//

			return $columns;
		}

		public function order_title( $title, $post_id ) {
			if ( $order = learn_press_get_order( $post_id ) ) {
				$title = $order->get_order_number();
			}

			return $title;
		}

		/**
		 * Render column data
		 *
		 * @param string
		 * @param int
		 */
		public function columns_content( $column, $post_id = 0 ) {
			global $post;
			$the_order = learn_press_get_order( $post->ID );
			switch ( $column ) {
				case 'order_student':
					if ( $user_ids = $the_order->get_users() ) {
						$outputs = array();
						foreach ( $user_ids as $user_id ) {
							if ( get_user_by( 'id', $user_id ) ) {
								$user      = learn_press_get_user( $user_id );
								$outputs[] = sprintf(
									'<a href="user-edit.php?user_id=%d">%s (%s)</a><span>%s</span>',
									$user_id,
									$user->get_data( 'user_login' ),
									$user->get_data( 'display_name' ),
									$user->get_data( 'user_email' )
								);
							} else {
								if ( sizeof( $user_ids ) == 1 ) {
									$outputs[] = $the_order->get_customer_name();
								}
							}
						}
						echo join( ', ', $outputs );
					} else {
						echo __( '(Guest)', 'learnpress' );
					}
					break;
				case
				'order_status' :

					echo sprintf( '<span class="learn-press-tooltip %s" data-tooltip="%s">%s</span>', $the_order->get_status(), learn_press_get_order_status_label( $the_order->get_id() ), '' );
					break;
				case 'order_date' :

					$t_time = get_the_time( 'Y/m/d g:i:s a' );
					$m_time = $post->post_date;
					$time   = get_post_time( 'G', true, $post );

					$time_diff = current_time( 'timestamp' ) - $time;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
						$h_time = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time ) );
					} else {
						$h_time = mysql2date( 'Y/m/d', $m_time );
					}

					echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'learn_press_order_column_time', $h_time, $the_order ) ) . '</abbr>';

					break;
				case 'order_items' :
					$links = array();
					$items = $the_order->get_items();
					$count = sizeof( $items );
					foreach ( $items as $item ) {
						if ( empty( $item['course_id'] ) || get_post_type( $item['course_id'] ) !== LP_COURSE_CPT ) {
							$links[] = __( 'Course does not exist', 'learnpress' );
						} else {
							$link = '<a href="' . get_the_permalink( $item['course_id'] ) . '">' . get_the_title( $item['course_id'] ) . ' (#' . $item['course_id'] . ')' . '</a>';
							if ( $count > 1 ) {
								$link = sprintf( '<li>%s</li>', $link );
							}
							$links[] = $link;
						}
					}
					if ( $count > 1 ) {
						echo sprintf( '<ol>%s</ol>', join( "", $links ) );
					} elseif ( 1 == $count ) {
						echo join( "", $links );
					} else {
						echo __( '(No item)', 'learnpress' );
					}
					break;
				case 'order_total' :
					echo $the_order->get_formatted_order_total();// learn_press_format_price( $the_order->order_total, learn_press_get_currency_symbol( $the_order->order_currency ) );
					if ( $title = $the_order->get_payment_method_title() ) {
						?>
                        <div class="payment-method-title">
							<?php echo $the_order->order_total == 0 ? $title : sprintf( __( 'Pay via <strong>%s</strong>', 'learnpress' ), $title ); ?>
                        </div>
						<?php
					}
					break;
			}
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( 'lp_order' != $post_type ) ) {
				return false;
			}

			return true;
		}

		private function _is_search() {
			return is_search();
		}

		/**
		 * Register post type
		 */
		public function register() {
			return array(
				'labels'             => array(
					'name'               => __( 'Orders', 'learnpress' ),
					'menu_name'          => __( 'Orders', 'learnpress' ),
					'singular_name'      => __( 'Order', 'learnpress' ),
					'add_new_item'       => __( 'Add New Order', 'learnpress' ),
					'edit_item'          => __( 'Order Details', 'learnpress' ),
					'all_items'          => __( 'Orders', 'learnpress' ),
					'view_item'          => __( 'View Order', 'learnpress' ),
					'add_new'            => __( 'Add New', 'learnpress' ),
					'update_item'        => __( 'Update Order', 'learnpress' ),
					'search_items'       => __( 'Search Orders', 'learnpress' ),
					'not_found'          => __( 'No order found', 'learnpress' ),
					'not_found_in_trash' => __( 'No order found in Trash', 'learnpress' )
				),
				'public'             => false,
				'show_ui'            => true,
				'show_in_nav_menus'  => false,
				'show_in_admin_bar'  => false,
				'publicly_queryable' => false,
				'show_in_menu'       => 'learn_press',
				'map_meta_cap'       => true,
				'capability_type'    => LP_ORDER_CPT,
				'hierarchical'       => true,
				'rewrite'            => array( 'slug' => LP_ORDER_CPT, 'hierarchical' => true, 'with_front' => true ),
				'supports'           => array(
					'title',
					'custom-fields'
				)
			);
		}

		/**
		 * Remove some unwanted metaboxes
		 */
		public static function register_metabox() {
			// Remove Publish metabox
			remove_meta_box( 'submitdiv', LP_ORDER_CPT, 'side' );
			remove_meta_box( 'commentstatusdiv', LP_ORDER_CPT, 'normal' );
		}

		/**
		 * Order details view.
		 *
		 * @param WP_Post $post
		 */
		public static function order_details( $post ) {
			learn_press_admin_view( 'meta-boxes/order/details.php', array( 'order' => new LP_Order( $post ) ) );
		}

		/**
		 * Order actions view.
		 *
		 * @param WP_Post $post
		 */
		public static function order_actions( $post ) {
			learn_press_admin_view( 'meta-boxes/order/actions.php', array( 'order' => new LP_Order( $post ) ) );
		}

		public function preparing_to_trash_order( $post_id ) {
			if ( LP_ORDER_CPT != get_post_type( $post_id ) ) {
				return;
			}
		}

		/**
		 * Register new post status for order
		 */
		public function register_post_statues() {
			$statuses = learn_press_get_register_order_statuses();
			foreach ( $statuses as $status => $args ) {
				register_post_status( $status, $args );
			}
		}

		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self( LP_ORDER_CPT );
			}

			return self::$_instance;
		}

	}

	// end LP_Order_Post_Type

	$order_post_type = LP_Order_Post_Type::instance();
	$order_post_type
		->add_meta_box( 'order_details', __( 'Order Details', 'learnpress' ), 'order_details', 'normal', 'high' )
		->add_meta_box( 'submitdiv', __( 'Order Actions', 'learnpress' ), 'order_actions', 'side', 'high' );
}

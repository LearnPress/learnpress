<?php
/**
 * @class LP_Order_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0.2
 */

if ( ! class_exists( 'LP_Order_Post_Type' ) ) {
	final class LP_Order_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * Type of post
		 *
		 * @var string
		 */
		protected $_post_type = LP_ORDER_CPT;
		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * LP_Order_Post_Type constructor.
		 *
		 */
		public function __construct() {
			add_action( 'admin_init', array( $this, 'register_post_statues' ) );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
			add_action( 'admin_init', array( $this, 'remove_box' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_filter( 'wp_untrash_post_status', array( $this, 'restore_status_order' ), 11, 3 );
			add_filter( 'admin_footer', array( $this, 'admin_footer' ) );
			add_filter( 'views_edit-lp_order', array( $this, 'filter_views' ) );
			// LP Order title

			// Override title of LP Order on Admin
			if ( is_admin() ) {
				$can_override_title_order = false;
				$current_url              = LP_Helper::getUrlCurrent();
				$post_id                  = LP_Helper::sanitize_params_submitted( $_GET['post'] ?? '' );
				if ( ! empty( $post_id ) ) {
					$post = get_post( $post_id );
					if ( $post && $post->post_type == LP_ORDER_CPT ) {
						$can_override_title_order = true;
					}
				} elseif ( strpos( $current_url, 'post_type=lp_order' ) !== false ) {
					$can_override_title_order = true;
				}

				if ( $can_override_title_order ) {
					add_filter( 'the_title', array( $this, 'order_title' ), 5, 2 );
				}
			}

			parent::__construct();
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
		public function filter_orders( string $where ): string {
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $where;
			}

			global $wpdb;

			if ( isset( $_REQUEST['parent'] ) ) {
				$where .= sprintf( ' AND post_parent = %d ', absint( $_REQUEST['parent'] ) );
			} else {
				// $where .= $wpdb->prepare( " AND (post_parent = 0 OR {$wpdb->posts}.ID IN( SELECT post_parent FROM {$wpdb->posts} X WHERE X.post_parent <> 0 AND X.post_type = %s) )", LP_ORDER_CPT );
				$where .= ' AND post_parent = 0 ';
			}

			return $where;
		}

		public function enqueue_scripts() {
			if ( get_post_type() != $this->_post_type ) {
				return;
			}
			wp_enqueue_script( 'user-suggest' );
		}

		/**
		 * Restore user course item when the order is stored (usually from trash).
		 *
		 * @param string $new_status
		 * @param int $post_id
		 * @param string $previous_status
		 *
		 * @return string
		 */
		public function restore_status_order( string $new_status, int $post_id, string $previous_status ): string {
			if ( LP_ORDER_CPT != get_post_type( $post_id ) ) {
				return $new_status;
			}

			return $previous_status;
		}

		/**
		 * Save order post.
		 *
		 * @param int $post_id
		 * @param WP_Post $post
		 * @param bool $is_update
		 *
		 * @editor tungnx
		 * @version 1.0.5
		 */
		public function save_post( int $post_id, WP_Post $post = null, bool $is_update = false ) {
			try {
				$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
				if ( isset( $backtrace[6]['class'] ) && $backtrace[6]['class'] === LP_Order_CURD::class ) {
					return;
				}

				if ( wp_is_post_revision( $post_id ) ) {
					return;
				}

				// For create LP Order manual on Backend
				$order = learn_press_get_order( $post_id );
				if ( ! $order ) {
					return;
				}

				$created_via = $order->get_created_via();
				if ( empty( $created_via ) ) {
					$created_via = LP_ORDER_CREATED_VIA_MANUAL;
					$order->set_created_via( $created_via );
				}

				if ( isset( $_POST['order-customer'] ) && $order->is_manual() ) {
					$user_id = LP_Request::get_param( 'order-customer' );
					$order->set_user_id( $user_id );
				}

				if ( isset( $_POST['order-date'] ) ) {
					$order_date = LP_Request::get_param( 'order-date' );
					$order_hour = LP_Request::get_param( 'order-hour', '00' );
					$order_min  = LP_Request::get_param( 'order-minute', '00' );
					$order->set_order_date( $order_date . ' ' . $order_hour . ':' . $order_min . ':00' );
				}

				$status = LP_Request::get_param( 'order-status' );
				if ( ! empty( $status ) ) {
					$order->update_status( $status );
				} elseif ( $post->post_status === 'auto-draft' ) {
					$order->update_status( 'pending' );
				}

				$order->save();
			} catch ( Throwable $e ) {
				error_log( __METHOD__ . ':' . $e->getMessage() );
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
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return;
			}
			?>
			<script type="text/javascript">
				jQuery(function ($) {
					$('#post-search-input').prop('placeholder',
						'<?php esc_attr_e( 'Order number, course name, etc.', 'learnpress' ); ?>').css('width', 300)
				})
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
			// Code temporary, when release about 1 week, will remove it.
			$lp_filter_post              = new LP_Post_Type_Filter();
			$lp_filter_post->post_type   = LP_ORDER_CPT;
			$lp_filter_post->post_status = [ 'lp-trash' ];
			$orders_trash                = LP_Post_DB::getInstance()->get_posts( $lp_filter_post );
			if ( $orders_trash ) {
				foreach ( $orders_trash as $order_trash ) {
					$order = learn_press_get_order( $order_trash->ID );
					if ( $order ) {
						$order->update_status( 'trash' );
					}
				}
			}
			// Change status course from lp_trash to trash.

			// End code temporary
			global $wpdb, $wp_query;
			$lp_db = LP_Database::getInstance();
			if ( is_admin() && $this->is_page_list_posts_on_backend() ) {
				$where  = ' ';
				$where .= $wpdb->prepare( " AND {$lp_db->tb_posts}.post_type = %s", LP_ORDER_CPT );
			}

			// Search by keyword
			if ( ! empty( $wp_query->get( 's' ) ) ) {
				$s = $wp_query->get( 's' );

				// Check search LP Order ID with format #000[ID] or 000[ID]
				$pattern = '/^#\d+$/';
				if ( preg_match( $pattern, $s ) ) {
					$s = str_replace( '#', '', $s );
				}

				$pattern2 = '#^0+.*\d+$#';
				if ( preg_match( $pattern2, $s ) ) {
					$s = (int) $s;
				}

				$s = trim( $s );

				$where .= $wpdb->prepare( " AND {$lp_db->tb_posts}.ID = %d", $s );
				$where .= $wpdb->prepare( ' OR lpori.order_item_name like %s', '%' . $wpdb->esc_like( $s ) . '%' );
			}

			// Search by author id
			if ( ! empty( $wp_query->get( 'author' ) ) ) {
				$user_id = absint( $wp_query->get( 'author' ) );
				//$where   .= $wpdb->prepare( ' AND uu.ID like %s ', $user_id );
				$where .= " AND ( pm1.meta_value like '%\"$user_id\"%' OR pm1.meta_value = $user_id ) ";
			}

			if ( ! empty( $wp_query->get( 'm' ) ) ) {
				$month  = $wp_query->get( 'm' );
				$where .= " AND YEAR({$lp_db->tb_posts}.post_date)=" . substr( $month, 0, 4 );
				if ( strlen( $month ) > 5 ) {
					$where .= " AND MONTH({$lp_db->tb_posts}.post_date)=" . substr( $month, 4, 2 );
				}
			}

			// Filter by order status
			if ( ! empty( $wp_query->get( 'post_status' ) ) ) {
				$status = $wp_query->get( 'post_status' );
				$where .= $wpdb->prepare( " AND {$lp_db->tb_posts}.post_status = %s", $status );
			} else {
				$where .= $wpdb->prepare( " AND {$lp_db->tb_posts}.post_status NOT IN (%s, %s, %s)", LP_ORDER_TRASH_DB, LP_ORDER_TRASH, 'auto-draft' );
			}

			return $where;
		}

		/*public function posts_fields( $fields ) {
			global $wp_query;

			if ( ! $this->_is_search() ) {
				return $fields;
			}

			if ( empty( $wp_query->get( 'author' ) ) ) {
				return $fields;
			}

			//$fields .= ', uu.ID, uu.display_name as user_display_name';

			return $fields;
		}*/

		public function posts_orderby( $orderby ) {
			global $wpdb;

			$order = $this->get_order_sort();

			switch ( $this->get_order_by() ) {
				case 'title':
					$orderby = "{$wpdb->posts}.ID {$order}";
					break;
				/*case 'student':
					$orderby = "uu.user_login {$order}";
					break;*/
				case 'date':
					$orderby = "{$wpdb->posts}.post_date {$order}";
					break;
				case 'order_total':
					$orderby = "CAST(pm2.meta_value AS UNSIGNED) {$order}";
					break;
			}

			return $orderby;
		}

		public function posts_join_paged( $join ) {
			global $wpdb, $wp_query;
			$lp_db = LP_Database::getInstance();

			// Search by keyword
			if ( ! empty( $wp_query->get( 's' ) ) ) {
				$join .= " INNER JOIN {$lp_db->tb_lp_order_items} lpori ON {$wpdb->posts}.ID = lpori.order_id";
			}

			if ( ! empty( $wp_query->get( 'author' ) ) ) {
				$author_id = $wp_query->get( 'author' );
				$join     .= " INNER JOIN {$lp_db->tb_postmeta} pm1 ON {$wpdb->posts}.ID = pm1.post_id AND pm1.meta_key = '_user_id'";
				$join     .= " LEFT JOIN {$lp_db->tb_users} uu ON uu.ID = $author_id";
			}

			if ( $this->get_order_by() === 'order_total' ) {
				$join .= " INNER JOIN {$lp_db->tb_postmeta} pm2 ON {$wpdb->posts}.ID = pm2.post_id AND pm2.meta_key = '_order_total'";
			}

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
			$columns['order_date']  = 'date';
			$columns['order_total'] = 'order_total';

			return $columns;
		}

		public function update_status() {
			$order_id = ! empty( $_REQUEST['order_id'] ) ? absint( $_REQUEST['order_id'] ) : 0;
			$status   = ! empty( $_REQUEST['status'] ) ? LP_Helper::sanitize_params_submitted( $_REQUEST['status'] ) : 'Pending';

			learn_press_update_order_status( $order_id, $status );

			wp_send_json(
				array(
					'status' => $status,
					'class'  => sanitize_title( $status ),
				)
			);
		}

		/**
		 * Custom row's actions.
		 *
		 * @param array $actions
		 * @param WP_Post $post
		 *
		 * @return mixed
		 * @since 2.1.7
		 * @deprecated 4.2.6.4
		 */
		/*public function row_actions( $actions, $post ) {
			if ( ! empty( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			if ( ! empty( $actions['edit'] ) ) {
				$actions['edit'] = preg_replace( '/>(.*?)<\/a>/', '>' . __( 'View Order', 'learnpress' ) . '</a>', $actions['edit'] );
			}

			return $actions;
		}*/

		/**
		 * re-order the orders by newest
		 *
		 * @param $wp_query
		 *
		 * @editor tungnx
		 * @reason comment this function - because default sort by id
		 *
		 * @return mixed
		 */
		public function pre_get_posts( $wp_query ) {
			if ( is_admin() && isset( $wp_query->query['post_type'] ) && LP_ORDER_CPT == $wp_query->query['post_type'] ) {
				$wp_query->set( 'orderby', 'date' );
				$wp_query->set( 'order', 'desc' );
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

			$columns['cb']            = '<input type="checkbox" />';
			$columns['title']         = esc_html__( 'Order', 'learnpress' );
			$columns['order_student'] = esc_html__( 'Student', 'learnpress' );
			$columns['order_items']   = esc_html__( 'Purchased', 'learnpress' );
			$columns['order_date']    = esc_html__( 'Date', 'learnpress' );
			$columns['order_total']   = esc_html__( 'Total', 'learnpress' );
			$columns['order_status']  = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'learnpress' ) . '">' . esc_attr__( 'Status', 'learnpress' ) . '</span>';

			$columns = array_merge( $columns, $existing );

			return $columns;
		}

		public function order_title( $title, $post_id ) {
			$order = learn_press_get_order( $post_id );
			if ( $order ) {
				$title = $order->get_order_number();
			}

			return $title;
		}

		/**
		 * Render column data
		 *
		 * @since 3.0.0
		 * @version 1.0.1
		 */
		public function columns_content( $column, $post_id = 0 ) {
			global $post;
			$lp_order = learn_press_get_order( $post->ID );

			switch ( $column ) {
				case 'order_student':
					$user_ids = $lp_order->get_users();
					if ( $user_ids ) {
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
									$outputs[] = $lp_order->get_customer_name();
								}
							}
						}
						echo join( ', ', $outputs );
					} else {
						echo esc_html__( '(Guest)', 'learnpress' );
					}
					break;
				case 'order_status':
					$lp_order_icons = LP_Order::get_icons_status();
					$icon           = $lp_order_icons[ $lp_order->get_status() ] ?? '';
					echo sprintf(
						'<span class="lp-order-status %s">%s%s</span>',
						$lp_order->get_status(),
						$icon,
						LP_Order::get_status_label( $lp_order->get_status() )
					);
					break;
				case 'order_date':
					$t_time    = get_the_time( 'Y/m/d g:i:s a' );
					$m_time    = $post->post_date;
					$time      = get_post_time( 'G', true, $post );
					$time_diff = time() - $time;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
						$h_time = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time ) );
					} else {
						$h_time = mysql2date( 'Y/m/d', $m_time );
					}

					echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'learn_press_order_column_time', $h_time, $lp_order ) ) . '</abbr>';

					break;
				case 'order_items':
					$links = array();
					$items = $lp_order->get_items();
					$count = sizeof( $items );

					foreach ( $items as $item ) {
						if ( empty( $item['course_id'] ) || get_post_type( $item['course_id'] ) !== LP_COURSE_CPT ) {
							$links[] = apply_filters( 'learn-press/order-item-not-course-id', esc_html__( 'The course does not exist', 'learnpress' ), $item, $lp_order );
						} elseif ( get_post_status( $item['course_id'] ) !== 'publish' ) {
							$links[] = get_the_title( $item['course_id'] ) . sprintf( ' (#%d - %s)', $item['course_id'], esc_html__( 'Deleted', 'learnpress' ) );
						} else {
							$link = '<a href="' . get_the_permalink( $item['course_id'] ) . '">' . get_the_title( $item['course_id'] ) . ' (#' . $item['course_id'] . ')' . '</a>';
							if ( $count > 1 ) {
								$link = sprintf( '<li>%s</li>', $link );
							}
							$links[] = apply_filters( 'learn-press/order-item-link', $link, $item, $lp_order );

						}
					}

					if ( $count > 1 ) {
						echo sprintf( '<ol>%s</ol>', join( '', $links ) );
					} elseif ( 1 == $count ) {
						echo join( '', $links );
					} else {
						echo esc_html__( '(No item)', 'learnpress' );
					}
					break;
				case 'order_total':
					echo wp_kses_post( $lp_order->get_formatted_order_total() );
					$method_title = $lp_order->get_payment_method_title();
					$method_title = apply_filters( 'learn-press/order-payment-method-title', $method_title, $lp_order );

					if ( ! empty( $method_title ) ) {
						$method_title_html = sprintf(
							__( 'Pay via <strong>%s</strong>', 'learnpress' ),
							$method_title
						);
						?>
						<div class="payment-method-title">
							<?php echo wp_kses_post( $method_title_html ); ?>
						</div>
						<?php
					}
					break;
			}
		}

		private function _is_search() {
			return is_search();
		}

		/**
		 * Register order post type
		 */
		public function args_register_post_type(): array {
			return array(
				'labels'              => array(
					'name'               => __( 'Orders', 'learnpress' ),
					'menu_name'          => __( 'Orders', 'learnpress' ),
					'singular_name'      => __( 'Order', 'learnpress' ),
					'add_new_item'       => __( 'Add A New Order', 'learnpress' ),
					'edit_item'          => __( 'Order Details', 'learnpress' ),
					'all_items'          => __( 'Orders', 'learnpress' ),
					'view_item'          => __( 'View Order', 'learnpress' ),
					'add_new'            => __( 'Add New', 'learnpress' ),
					'update_item'        => __( 'Update Order', 'learnpress' ),
					'search_items'       => __( 'Search Orders', 'learnpress' ),
					'not_found'          => __( 'No order found', 'learnpress' ),
					'not_found_in_trash' => __( 'There was no order found in the trash', 'learnpress' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'publicly_queryable'  => false,
				'show_in_menu'        => 'learn_press',
				'map_meta_cap'        => true,
				'capability_type'     => LP_ORDER_CPT,
				'hierarchical'        => true,
				'rewrite'             => array(
					'slug'         => LP_ORDER_CPT,
					'hierarchical' => true,
					'with_front'   => true,
				),
				'supports'            => array(
					'title',
					'custom-fields',
				),
				'exclude_from_search' => true,
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
		 * Register new post status for order
		 *
		 * @Todo when rewrite API, will remove this function
		 * will be not use learn_press_get_register_order_statuses
		 */
		public function register_post_statues() {
			$statuses = learn_press_get_register_order_statuses();
			foreach ( $statuses as $status => $args ) {
				register_post_status( $status, $args );
			}
		}

		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/**
		 * Before post deleted
		 *
		 * @author tungnx
		 * @since 4.1.4
		 * @version 1.0.0
		 */
		public function before_delete( int $order_id ) {
			$lp_order_db      = LP_Order_DB::getInstance();
			$lp_user_items_db = LP_User_Items_DB::getInstance();

			try {
				$order = learn_press_get_order( $order_id );

				if ( ! $order ) {
					return;
				}

				$order_item_ids = $lp_order_db->get_order_item_ids( $order_id );

				if ( empty( $order_item_ids ) ) {
					return;
				}

				$user_ids = $order->get_users();

				foreach ( $user_ids as $user_id ) {
					delete_user_meta( $user_id, 'orders' );
					$item_ids = $order->get_item_ids();
					if ( ! empty( $item_ids ) ) {
						foreach ( $order->get_item_ids() as $course_id ) {
							// Check this order is the latest by user and course_id
							$last_order_id = $lp_order_db->get_last_lp_order_id_of_user_course( $user_id, $course_id );
							if ( $last_order_id && $last_order_id != $order->get_id() ) {
								continue;
							}

							$lp_user_items_db->delete_user_items_old( $user_id, $course_id );
						}
					}

					do_action( 'learn-press/order/before-delete', $order, $user_id );
				}

				// Delete lp_order_item, lp_order_itemmeta
				$filter_delete                 = new LP_Order_Filter();
				$filter_delete->order_item_ids = $order_item_ids;
				$lp_order_db->delete_order_item( $filter_delete );
				$lp_order_db->delete_order_itemmeta( $filter_delete );
				// End
			} catch ( Throwable $e ) {
				error_log( $e->getMessage() . '>' . __FILE__ );
			}
		}

		/**
		 * Action delete Order
		 *
		 * @param int $order_id
		 *
		 * @author tungnx
		 * @since 4.1.4
		 * @version 1.0.0
		 */
		public function deleted_post( int $order_id ) {
		}

		public function meta_boxes() {
			return array(
				'order_details' => array(
					'title'    => esc_html__( 'Order Details', 'learnpress' ),
					'callback' => function ( $post ) {
						learn_press_admin_view( 'meta-boxes/order/details.php', array( 'order' => new LP_Order( $post ) ) );
					},
					'context'  => 'normal',
					'priority' => 'high',
				),
				'submitdiv'     => array(
					'title'    => esc_html__( 'Order Actions', 'learnpress' ),
					'callback' => function ( $post ) {
						learn_press_admin_view( 'meta-boxes/order/actions.php', array( 'order' => new LP_Order( $post ) ) );
					},
					'context'  => 'side',
					'priority' => 'high',
				),
				'order_exports' => array(
					'title'    => esc_html__( 'Order Exports', 'learnpress' ),
					'callback' => function ( $post ) {
						learn_press_admin_view( 'meta-boxes/order/exports-invoice.php', array( 'order' => new LP_Order( $post ) ) );
					},
					'context'  => 'side',
					'priority' => 'high',
				),
			);
		}
	}

	// end LP_Order_Post_Type

	$order_post_type = LP_Order_Post_Type::instance();
}

<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
if ( !class_exists( 'LP_Order_Post_Type' ) ) {

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
			add_filter( 'admin_footer', array( $this, 'admin_footer' ) );

			$this
				->add_map_method( 'before_delete', 'delete_order_items' )
				->add_map_method( 'save', 'save_order' );

			parent::__construct( $post_type );
		}

		public function enqueue_scripts() {
			if ( get_post_type() != 'lp_order' ) {
				return;
			}
			wp_enqueue_script( 'user-suggest' );
		}

		/**
		 * Delete all records related to order being deleted
		 *
		 * @param $post_id
		 */
		public function delete_order_items( $post_id ) {
			global $wpdb, $post;
			if ( get_post_type( $post_id ) != 'lp_order' ) {
				return;
			}
			// get order items
			$query = $wpdb->prepare( "
				SELECT order_item_id FROM {$wpdb->prefix}learnpress_order_items
				WHERE order_id = %d
			", $post_id );
			if ( $item_ids = $wpdb->get_col( $query ) ) {

				// get user order
				$user_id = intval( get_post_meta( $post_id, '_user_id', true ) );

				// delete order item meta data
				$query = "
					DELETE FROM {$wpdb->prefix}learnpress_order_itemmeta
					WHERE learnpress_order_item_id IN(" . join( ',', $item_ids ) . ")
				";
				$wpdb->query( $query );

				// delete order items
				$query = $wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}learnpress_order_items
					WHERE order_id = %d
				", $post_id );
				$wpdb->query( $query );

				// delete all data related user order
				if ( $user_id ) {
					learn_press_delete_user_data( $user_id );
				}
			}
		}

		public function save_order( $post_id ) {
			global $action;
			if ( wp_is_post_revision( $post_id ) )
				return;
			if ( $action == 'editpost' && get_post_type( $post_id ) == 'lp_order' ) {
				remove_action( 'save_post', array( $this, 'save_order' ) );

				$user_id = learn_press_get_request( 'order-customer' );
				//$postdata = array( 'post_status' => $status, 'ID' => $post_id );
				///wp_update_post( $postdata );

				update_post_meta( $post_id, '_user_id', $user_id > 0 ? $user_id : 0 );

				$order_statuses = learn_press_get_order_statuses();
				$order_statuses = array_keys( $order_statuses );
				$status         = learn_press_get_request( 'order-status' );

				if ( !in_array( $status, $order_statuses ) ) {
					$status = reset( $order_statuses );
				}
				$order = learn_press_get_order( $post_id );
				$order->update_status( $status );
			}
		}

		public function remove_box() {
			//remove_post_type_support( LP_ORDER_CPT, 'title' );
			remove_post_type_support( LP_ORDER_CPT, 'editor' );
		}

		public function admin_footer() {
			if ( !$this->_is_archive() ) {
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

		public function posts_where_paged( $where ) {
			global $wpdb, $wp_query;
			if ( !$this->_is_archive() || !$this->_is_search() ) {
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
			if ( !$this->_is_archive() || !$this->_is_search() ) {
				return $fields;
			}
			$fields .= ", uu.ID as user_ID, uu.display_name as user_display_name";
			return $fields;
		}

		public function posts_orderby( $orderby ) {
			if ( !$this->_is_archive() || !$this->_is_search() ) {
				return $orderby;
			}
			return $orderby;
		}

		public function posts_join_paged( $join ) {
			if ( !$this->_is_archive() || !$this->_is_search() ) {
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

			if ( LP_ORDER_CPT != get_post_type() )
				return;
			ob_start();
			?>
			<script>
				$('#update-order-status').click(function () {
					var $button = $(this).attr('disabled', 'disabled').html('<?php _e( 'Processing...', 'learnpress' ); ?>');
					$.ajax({
						url     : ajaxurl,
						type    : 'POST',
						dataType: 'json',
						data    : {
							action  : 'update_order_status',
							order_id: '<?php echo $post->ID; ?>',
							status  : $('select[name="learn_press_order_status"]').val()
						},
						success : function (res) {
							if (res.status) {
								$('.order-data-status')
									.removeClass('pending completed')
									.html(res.status)
									.addClass(res.class);
							}
							$button.removeAttr('disabled').html('<?php _e( 'Apply', 'learnpress' ); ?>');
						},
						error   : function () {
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
			$order_id = !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : 0;
			$status   = !empty( $_REQUEST['status'] ) ? $_REQUEST['status'] : 'Pending';
			learn_press_update_order_status( $order_id, $status );

			wp_send_json(
				array(
					'status' => $status,
					'class'  => sanitize_title( $status )
				)
			);
		}

		public function row_actions( $actions, $post ) {
			if ( !empty( $actions['inline hide-if-no-js'] ) ) {
				unset( $actions['inline hide-if-no-js'] );
			}
			if ( !empty( $actions['edit'] ) ) {
				$actions['edit'] = preg_replace( '/>(.*?)<\/a>/', ">" . __( 'View Order', 'learnpress' ) . "</a>", $actions['edit'] );
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
				if ( !empty( $wp_query->query['post_type'] ) && ( $wp_query->query['post_type'] == LP_ORDER_CPT ) ) {
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
			/* if ( isset( $existing['title'] ) )
			  unset( $existing['title'] );
			 */
			// Remove Format
			if ( isset( $existing['format'] ) )
				unset( $existing['format'] );

			// Remove Author
			if ( isset( $existing['author'] ) )
				unset( $existing['author'] );

			// Remove Comments
			if ( isset( $existing['comments'] ) )
				unset( $existing['comments'] );

			// Remove Date
			if ( isset( $existing['date'] ) )
				unset( $existing['date'] );

			// Remove Builder
			if ( isset( $existing['builder_layout'] ) )
				unset( $existing['builder_layout'] );

			add_filter( 'the_title', array( $this, 'order_title' ), 5, 2 );

			$columns['cb']            = '<input type="checkbox" />';
			$columns['title']         = __( 'Order', 'learnpress' );
			$columns['order_student'] = __( 'Student', 'learnpress' );
			$columns['order_items']   = __( 'Purchased', 'learnpress' );
			$columns['order_date']    = __( 'Date', 'learnpress' );
			$columns['order_total']   = __( 'Total', 'learnpress' );
			$columns['order_status']  = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'learnpress' ) . '">' . esc_attr__( 'Status', 'learnpress' ) . '</span>';

			$columns = array_merge( $columns, $existing );

			return $columns;
		}

		public function order_title( $title, $post_id ) {
			if ( LP_ORDER_CPT == get_post_type( $post_id ) )
				$title = learn_press_transaction_order_number( $post_id );
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
					if ( $the_order->customer_exists() ) {
						$user = learn_press_get_user( $the_order->user_id );
						printf( '<a href="user-edit.php?user_id=%d">%s (%s)</a>', $the_order->user_id, $user->user_login, $user->display_name );
						?><?php
						printf( '<br /><span>%s</span>', $user->user_email );
					} else {
						echo $the_order->get_customer_name();
					}
					break;
				case 'order_status' :
					echo learn_press_get_order_status_label( $post->ID );
					break;
				case 'order_date' :

					$t_time = get_the_time( 'Y/m/d g:i:s a' );
					$m_time = $post->post_date;
					$time   = get_post_time( 'G', true, $post );

					$time_diff = current_time( 'timestamp' ) - $time;

					if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS )
						$h_time = sprintf( __( '%s ago', 'learnpress' ), human_time_diff( $time ) );
					else
						$h_time = mysql2date( 'Y/m/d', $m_time );

					echo '<abbr title="' . esc_attr( $t_time ) . '">' . esc_html( apply_filters( 'learn_press_order_column_time', $h_time, $the_order ) ) . '</abbr>';

					break;
				case 'order_items' :
					$links = array();
					foreach ( $the_order->get_items() as $item ) {
						if ( empty( $item['course_id'] ) || get_post_type( $item['course_id'] ) !== LP_COURSE_CPT ) {
							$links[] = __( 'Course does not exists', 'learnpress' );
						} else {
							$links[] = '<a href="' . get_the_permalink( $item['course_id'] ) . '">' . get_the_title( $item['course_id'] ) . '</a>';
						}
					}
					echo join( "<br />", $links );
					break;
				case 'order_total' :
					echo learn_press_format_price( $the_order->order_total, learn_press_get_currency_symbol( $the_order->order_currency ) );
					if ( $title = $the_order->get_payment_method_title() ) {
						?>
						<div class="payment-method-title">
							<?php echo $the_order->order_total == 0 ? $title : sprintf( __( 'Pay via <strong>%s</strong>', 'learnpress' ), $title ); ?>
						</div>
						<?php
					}
					break;
				case 'order_title' :
					$order_number = sprintf( "%'.010d", $the_order->ID );
					?>
					<div class="tips">
						<a href="post.php?post=<?php echo $the_order->ID ?>&action=edit"><strong><?php echo learn_press_transaction_order_number( $order_number ); ?></strong></a>
					</div>
					<?php
					break;
			}
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( !is_admin() || ( $pagenow != 'edit.php' ) || ( 'lp_order' != $post_type ) ) {
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
				/* 'capabilities'       => array(
				  'create_posts' => 'do_not_allow'
				  ), */
				'map_meta_cap'       => true,
				'capability_type'    => LP_ORDER_CPT,
				'hierarchical'       => true,
				'rewrite'            => array( 'slug' => LP_ORDER_CPT, 'hierarchical' => true, 'with_front' => true ),
				'supports'           => array(
					'title',
					'comments',
					'custom-fields'
				)
			);
		}

		public static function register_metabox() {

			// Remove Publish metabox
			remove_meta_box( 'submitdiv', LP_ORDER_CPT, 'side' );

			remove_meta_box( 'commentstatusdiv', LP_ORDER_CPT, 'normal' );
		}

		public static function order_details( $post ) {
			learn_press_admin_view( 'meta-boxes/order/details.php', array( 'order' => LP_Order::instance( $post ) ) );
		}

		public static function order_actions( $post ) {
			learn_press_admin_view( 'meta-boxes/order/actions.php', array( 'order' => LP_Order::instance( $post ) ) );
		}

		public function preparing_to_trash_order( $post_id ) {
			if ( LP_ORDER_CPT != get_post_type( $post_id ) )
				return;
		}

		/**
		 * Enqueue scripts
		 *
		 * @static
		 */
		public function admin_scripts() {

		}

		public function remove_edit_post_link() {
			return '';
		}

		/**
		 * Register new post status for order
		 */
		public function register_post_statues() {
                        global $lp_order_statuses;
                        $lp_order_statuses = array();
                        $lp_order_statuses[ 'lp-completed' ] = array(
				'label'                     => _x( 'Completed', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Completed <span class="count">(%s)</span>', 'Completed <span class="count">(%s)</span>', 'learnpress' )
			);
                        $lp_order_statuses[ 'lp-processing' ] = array(
				'label'                     => _x( 'Processing', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Processing <span class="count">(%s)</span>', 'Processing <span class="count">(%s)</span>', 'learnpress' )
			);
                        $lp_order_statuses[ 'lp-pending' ] = array(
				'label'                     => _x( 'Pending Payment', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Pending Payment <span class="count">(%s)</span>', 'Pending Payment <span class="count">(%s)</span>', 'learnpress' )
			);
                        $lp_order_statuses[ 'lp-on-hold' ] = array(
				'label'                     => _x( 'On Hold', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'On Hold <span class="count">(%s)</span>', 'On Hold <span class="count">(%s)</span>', 'learnpress' )
			);
                        $lp_order_statuses[ 'lp-cancelled' ] = array(
				'label'                     => _x( 'Cancelled', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'learnpress' )
			);
                        $lp_order_statuses[ 'lp-refunded' ] = array(
				'label'                     => _x( 'Refunded', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Refunded <span class="count">(%s)</span>', 'Refunded <span class="count">(%s)</span>', 'learnpress' )
			);
                        $lp_order_statuses[ 'lp-failed' ] = array(
				'label'                     => _x( 'Failed', 'Order status', 'learnpress' ),
				'public'                    => false,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Failed <span class="count">(%s)</span>', 'Failed <span class="count">(%s)</span>', 'learnpress' )
			);
                        foreach ( $lp_order_statuses as $status => $args ) {
                            register_post_status( $status, $args );
                        }
		}

		public function submitdiv() {

		}

		public static function instance() {
			if ( !self::$_instance ) {
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
